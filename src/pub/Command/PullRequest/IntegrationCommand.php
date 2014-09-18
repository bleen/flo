<?php

namespace pub\Command\PullRequest;

use pub\Config;
use pub\ProjectConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Github;


class IntegrationCommand extends Command {
  const ERROR_LABEL = 'ci:error';
  public $invalid_labels = array(
    'ci:error',
    'ci:ignored',
    'ci:postponed',
    'ci:rejected',
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
    $github = new Github\Client(
      new Github\HttpClient\CachedHttpClient(array('cache_dir' => '/tmp/github-api-cache'))
    );
    $config = new Config();
    $project_config = new ProjectConfig();


    // Check if hub exists if not throw an error.
    $process = new Process('hub --version');
    $process->run();
    if (!$process->isSuccessful()) {
      // If you do not have hub we do nothing.
      throw new \RuntimeException($process->getErrorOutput());
    }

    $pub_config = $config->load();
    if (!isset($pub_config['github-oauth-token'])) {
      throw new \Exception("You must have a github-oauth-token set up. Ex. pub config-set github-oauth-token MY_TOKEN_IS_THIS.");
    }

    $github->authenticate($pub_config['github-oauth-token'], NULL, Github\Client::AUTH_URL_TOKEN);
    $project_config->load();

    // Request all open issues in created order. 1st come 1st serve.
    $paginator  = new Github\ResultPager($github);
    $issues_api = $github->api('issue');
    $pull_requests = $paginator->fetchAll($issues_api, 'all', array($project_config->settings['organization'], $project_config->settings['repository'], array(
      'state' => 'open',
      'sort' => 'created',
      'direction' => 'asc',
    )));

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
            $project_config->settings['organization'],
            $project_config->settings['repository'],
            $pr['number'],
            self::ERROR_LABEL
          );
        }

        $process = new Process('git merge --abort');
        $process->run();
      }
      else {
        $output->writeln("<info>Successfully applied PR# {$pr['number']}: {$url}.</info>");
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
  }
}
