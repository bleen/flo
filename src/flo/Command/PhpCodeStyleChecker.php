<?php

/**
 * Runs php parallel-lint on change files only.
 */

namespace flo\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Github;


class PhpCodeStyleChecker extends Command {
  protected function configure() {
    $this->setName('check-php-cs')
      ->setDescription('runs phpcs against the change files.')
      ->addOption(
        'comment',
        null,
        InputOption::VALUE_NONE,
        'If set, the output will be posted to github as a comment on the relevant Pull Request'
      );
  }

  /**
   * Process the check-phpcs command.
   *
   * {@inheritDoc}
   *
   * This command takes in environment variables for knowing what branch to target.
   * If no branch is passed in the environment variable
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $gh_status_post = FALSE;
    $phpcs = './vendor/bin/phpcs --standard=./vendor/drupal/coder/coder_sniffer/Drupal --extensions=module,php,inc,install --ignore="*.features.*,*.context.inc,*.*_default.inc,*.default_permission_sets.inc,*.default_mps_tags.inc,*.field_group.inc,*.strongarm.inc,*.quicktabs.inc,*.tpl.php"';
    $targetBranch = getenv(self::GITHUB_PULL_REQUEST_TARGET_BRANCH);
    $targetRef = getenv(self::GITHUB_PULL_REQUEST_COMMIT);
    $targetURL = getenv(self::JENKINS_BUILD_URL);
    $pullRequest = getenv(self::GITHUB_PULL_REQUEST_ID);
    $github = $this->getGithub();

    if (empty($targetBranch)) {
        // Default to master if there is no target branch.
        // You can also change the branch to check against.
        // This checks againts the dev branch:
        // `ghprbTargetBranch=dev flo check-php`
        $targetBranch = 'master';
    }

    // Check if we're going to post to GH or not.
    if (!empty($targetRef) && !empty($targetURL)) {
      // Set the $gh_status_post variable to TRUE if we can post to GH.
      $gh_status_post = TRUE;
    }

    if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
        // Get the list for verbose output.
        $process = new Process("git --no-pager diff --name-only {$targetBranch}");
        $process->run();
        $output->writeln("<info>Files about to get parsed: \n" . $process->getOutput() . "</info>");
    }

    if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERY_VERBOSE) {
      $output->writeln("<info>About to run: \n  $phpcs $(git --no-pager diff --name-status {$targetBranch} | grep -v '^D' | awk '{print $2}')</info>");
    }

    $process = new Process("$phpcs $(git --no-pager diff --name-status {$targetBranch} | grep -v '^D' | awk '{print $2}')");
    $process->run();

    $processOutput = $process->getOutput();


    if (!$process->isSuccessful()) {
      $output->writeln("<error>There is a coding style error.</error>");
      $gh_status_state = 'failure';
      $gh_statue_desc = 'Flo: PHP Coding Style failure.';
    }
    else {
      $output->writeln("<info>No coding style errors.</info>");
      $gh_status_state = 'success';
      $gh_statue_desc = 'Flo: PHP Coding Style success.';
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
          'context' => "flo/phpcs",
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
        array('body' => "flo/phpcs failure:\n ```" .  $processOutput . "```")
      );
    }

    $output->writeln($processOutput);
  }
}
