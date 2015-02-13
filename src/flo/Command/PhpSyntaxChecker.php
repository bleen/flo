<?php

/**
 * Runs php parallel-lint on change files only.
 */

namespace flo\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Github;


class PhpSyntaxChecker extends Command {

  protected function configure() {
    $this->setName('check-php')
      ->setDescription('runs parallel-lint against the change files.');
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
    $parallelLink = './vendor/bin/parallel-lint -e module,php,inc,install,profile --stdin';
    $targetBranch = getenv(self::GITHUB_PULL_REQUEST_TARGET_BRANCH);

    if (empty($targetBranch)) {
      // Default to master if there is no target branch.
      // You can also change the branch to check against.
      // This checks againts the dev branch:
      // `ghprbTargetBranch=dev flo check-php`
      $targetBranch = 'master';
    }

    if ($output->getVerbosity() == OutputInterface::VERBOSITY_VERBOSE) {
      // Get the list for verbose output.
      $process = new Process("git --no-pager diff --name-only {$targetBranch}");
      $process->run();
      $output->writeln("<info>Files about to get parsed: \n" . $process->getOutput() . "</info>");
    }

    $process = new Process("git --no-pager diff --name-status {$targetBranch} | grep -v '^D' | awk '{print $2}'  | $parallelLink");
    $process->run();

    if (!$process->isSuccessful()) {
      $output->writeln("<error>There is a syntax error</error>");
      // TODO: Add github Parser here.
    }

    $output->writeln($process->getOutput());
  }
}
