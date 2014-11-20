<?php

namespace pub\Command\PullRequest;

use pub\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Github;


class IntegrationCommand extends Command {

  private $invalid_labels = array(
    self::GITHUB_LABEL_ERROR,
    self::GITHUB_LABEL_IGNORED,
    self::GITHUB_LABEL_POSTPONED,
    self::GITHUB_LABEL_REJECTED,
  );

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-integration')
      ->setDescription('Pull all valid PRs into the acquia integration branch.')
      ->addOption(
        'push',
        'p',
        InputOption::VALUE_NONE,
        'If set we will also force push the integration branch to Acquia.'
      )
      ->addOption(
        'am',
        'a',
        InputOption::VALUE_NONE,
        'Use am instead of merge. Useful if you don\'t have access to other developer\'s repos.'
      )
      ->addOption(
        'no-label',
        'l',
        InputOption::VALUE_NONE,
        'Do not label the PR in case of error.'
      );
  }

  /**
   * Process Integration job.
   *
   *  - Get all the Issues (We need issues since they have labels).
   *  - Figure our if they're a Pull Request or Not.
   *  - Ignore all Pull Request with ci:error based labels.
   *  - Apply each Pull Request locally to "integration" branch.
   *  - Deploy integration branch to acquia (DEV set up to track it)
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $github = $this->getGithub();

    // Request all open issues in created order. 1st come 1st serve.
    $paginator  = new Github\ResultPager($github);
    $issues_api = $github->api('issue');
    $pull_requests = $paginator->fetchAll($issues_api, 'all', array($this->getConfigParameter('organization'), $this->getConfigParameter('repository'), array(
      'state' => 'open',
      'sort' => 'created',
      'direction' => 'asc',
    )));

    // Get current branch or commit.
    $current_head = '';
    $process = new Process('git symbolic-ref --short HEAD');
    $process->run();
    if ($process->isSuccessful()) {
      $current_head = trim($process->getOutput());
    }
    else {
      $process = new Process('git rev-parse HEAD');
      $process->run();
      if ($process->isSuccessful()) {
        $current_head = trim($process->getOutput());
      }
    }

    $process = new Process('git checkout -B integration');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    foreach ($pull_requests as $pr) {

      // If the issue is not a PR we skip it.
      if (empty($pr['pull_request']['patch_url'])) {
        continue;
      }

      // If this issue has any of the following labels, we also skip it.
      foreach ($pr['labels'] as $label) {
        // TODO: Have a function check this.
        if (in_array($label['name'], $this->invalid_labels)) {
          // This continue breaks us out of the top foreach.
          continue 2;
        }
      }

      // Now try to apply the patch or else mark it as failure.
      $url = $pr['pull_request']['html_url'];
      if ($input->getOption('am')) {
        $command = "hub am --3way {$url}";
      }
      else {
        $command = "hub merge {$url}";
      }
      $output->writeln("\n" . $command);
      $process = new Process($command);
      $process->run();
      if (!$process->isSuccessful()) {
        // We reset the failed AM & we marked the PR as ci:error.
        $output->writeln("<error>Failed to applied PR# {$pr['number']}: {$url}.</error>");
        $output->writeln($process->getOutput());
        if (!$input->getOption('no-label')) {
          $labels = $github->api('issue')->labels()->add(
            $this->getConfigParameter('organization'),
            $this->getConfigParameter('repository'),
            $pr['number'],
            self::GITHUB_LABEL_ERROR
          );
        }

        $process = new Process('git merge --abort');
        $process->run();
      }
      else {
        $output->writeln("<info>Successfully applied PR #{$pr['number']}: {$url}.</info>");
      }
    }

    // Now we deploy integration to acquia always fresh.
    // We only do this if the --push flag is set.
    if ($input->getOption('push')) {
      $process = new Process('git push acquia integration --force');
      $process->run();
      if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
      }
      $output->writeln("<info>Successfully Pushed integration branch to Acquia.</info>");
    }

    // Return to the branch the user was previously on, if they were on one.
    if (!empty($current_head)) {
      $process = new Process("git checkout {$current_head}");
      $process->run();
    }
  }
}
