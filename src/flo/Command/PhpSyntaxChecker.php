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


class PhpSyntaxChecker extends Command {

  protected function configure() {
    $this->setName('check-php')
      ->setDescription('runs parallel-lint against the change files.')
      ->addOption(
        'comment',
        null,
        InputOption::VALUE_NONE,
        'If set, the output will be posted to github as a comment on the relevant Pull Request'
      );
  }

  /**
   * Process the check-php command.
   *
   * {@inheritDoc}
   *
   * This command takes in environment variables for knowing what branch to target.
   * If no branch is passed in the environment variable
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $gh_status_post = FALSE;
    $parallelLink = './vendor/bin/parallel-lint -e module,php,inc,install,profile --stdin';
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

    $process = new Process("git --no-pager diff --name-status {$targetBranch} | grep -v '^D' | awk '{print $2}'  | $parallelLink");
    $process->run();
    $processOutput = $process->getOutput();

    if (!$process->isSuccessful()) {
      $output->writeln("<error>There is a syntax error.</error>");
      $gh_status_state = 'failure';
      $gh_statue_desc = 'Flo: PHP syntax failure.';
    }
    else {
      $output->writeln("<info>No syntax error found.</info>");
      $gh_status_state = 'success';
      $gh_statue_desc = 'Flo: PHP syntax success.';
    }


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
          'context' => "flo/phpsyntax",
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
        array('body' => "flo/phpsyntax failure:\n ```\n" .  $processOutput . "```")
      );
    }

    $output->writeln($processOutput);
  }
}
