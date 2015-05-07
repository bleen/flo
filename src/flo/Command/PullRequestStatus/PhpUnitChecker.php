<?php

/**
 * Runs phpunit.
 */

namespace flo\Command\PullRequestStatus;

use flo\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Github;


class PhpUnitChecker extends Command {
  protected function configure() {
    $this->setName('check-phpunit')
      ->setDescription('runs phpunit and report to GH PR Status.')
      ->addOption(
        'comment',
        null,
        InputOption::VALUE_NONE,
        'If set, the output will be posted to github as a comment on the relevant Pull Request'
      );
  }

  /**
   * Process the check-phpunit command.
   *
   * {@inheritDoc}
   *
   * This command takes in environment variables for knowing what branch to target.
   * If no branch is passed in the environment variable
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $gh_status_post = FALSE;
    $phpunit = "./vendor/bin/phpunit";
    $targetRef = getenv(self::GITHUB_PULL_REQUEST_COMMIT);
    $targetURL = getenv(self::JENKINS_BUILD_URL);
    $pullRequest = getenv(self::GITHUB_PULL_REQUEST_ID);
    $github = $this->getGithub();

    if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
      $output->writeln("<info>target branch:{$targetBranch}</info>");
      $output->writeln("<info>target ref: {$targetRef}</info>");
      $output->writeln("<info>target URL: {$targetURL}</info>");
      $output->writeln("<info> pull request: {$pullRequest}</info>");
    }

    // Check if we're going to post to GH or not.
    if (!empty($targetRef) && !empty($targetURL)) {
      // Set the $gh_status_post variable to TRUE if we can post to GH.
      $gh_status_post = TRUE;
    }

    // Run phpunit.
    $process = new Process($phpunit);
    $process->run();
    $processOutput = $process->getOutput();

    if (!$process->isSuccessful()) {
      $output->writeln("<error>phpunit error.</error>");
      $gh_status_state = 'failure';
      $gh_statue_desc = 'Flo: PHPUnit failure.';
    }
    else {
      $output->writeln("<info>phpunit sucess.</info>");
      $gh_status_state = 'success';
      $gh_statue_desc = 'Flo: PHPUnit success.';
    }

    // Post to GH if we're allowed.
    if ($gh_status_post) {
      $output->writeln("<info>Posting to Github Status API.</info>");
      $github->api('repo')->statuses()->create(
        $this->getConfigParameter('organization'),
        $this->getConfigParameter('repository'),
        $targetRef,
        array(
          'state' => $gh_status_state,
          'target_url' => $targetURL,
          'description' => $gh_statue_desc,
          'context' => "flo/phpunit",
        )
      );
    }

    // Decide if we're going to post to Github Comment API.
    if ($input->getOption('comment') && !empty($pullRequest) && !$process->isSuccessful()) {
      $output->writeln("<info>Posting to Github Comment API.</info>");

      $github->api('issue')->comments()->create(
        $this->getConfigParameter('organization'),
        $this->getConfigParameter('repository'),
        $pullRequest,
        array('body' => "flo/phpunit failure:\n ```\n" .  $processOutput . "```")
      );
    }

    $output->writeln($processOutput);
  }

}
