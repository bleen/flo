<?php

namespace pub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use vierbergenlars\SemVer\version;


class NewReleaseCommand extends Command {
  const MASTER_BRANCH = 'master';

  protected function configure() {
    $this->setName('new-release')
      ->setDescription('Creates a new version of publisher and tags the release.')
      ->addArgument(
        'increment',
        InputArgument::REQUIRED,
        'major|minor|patch|x.y.z'
      )
      ->addOption(
        'push',
        'p',
        InputOption::VALUE_NONE,
        'If set we will push the new tag to github.'
      );
    }

  /**
   * Process the New Release command.
   *
   * - Update the version.php file
   * - Commit the change and tag it
   * - If the --push option is used, push the change to Acquia
   *
   * This command assumes you are running it from the root of the repository.
   * Additionally, this command makes changes to the master branch.
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Checkout the master branch.
    $process = new Process('git checkout ' . self::MASTER_BRANCH);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // @TODO: Figure out how to make this work without hard-coding the path to version.php.
    $version_filename = 'docroot/version.php';
    if (!file_exists($version_filename)) {
      throw new \Exception("The version.php file could not be found.");
    }
    require_once $version_filename;

    // Determine the new version number.
    $increment = $input->getArgument('increment');
    try {
      // This will succeed when a specific version number is provided, otherwise
      // an exception will be thrown and the "catch" is used.
      $version = new version($increment);
      $version_number = $version->getVersion();
    } catch(\Exception $e) {
      $current_version = new version(PUBLISHER_VERSION);
      $version_number = $current_version->inc($increment);
    }

    // Update version.php.
    $this->updateVersionFile($version_filename, $version_number);
    $output->writeln("<info>Successfully updated the version.php file and set the PUBLISHER_VERSION to {$version_number}</info>");

    // Commit the updated version.php.
    $process = new Process('git add ' . $version_filename . ' && git commit -m "Preparing for new tag: ' . $version_number . '"');
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Tag the commit.
    $process = new Process('git tag ' . $version_number);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Push the release to github if the --push flag is set.
    if ($input->getOption('push')) {
      $process = new Process('git push acquia ' . $version_number);
      $process->run();
      if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
      }
      $output->writeln("<info>Successfully Pushed the " . $version_number . " tag.</info>");
    }
  }

  /**
   * Overwrite the PUBLISHER_VERSION constant with its new value.
   *
   * @param string $version_file
   *   A relative path to the file defining the PUBLISHER_VERSION constant
   *   includeing the file name.
   * @param string $version_number
   *   The version number to use when setting PUBLISHER_VERSION.
   */
  protected function updateVersionFile($version_file, $version_number) {
    if (!file_exists($version_file)) {
      throw new \Exception("The version.php file could not be found. Are you running the command from the root of the repository?");
    }
    if (!is_writable($version_file)) {
      throw new \Exception("The version.php file is not writable.");
    }
    if (!$handle = fopen($version_file, 'w')) {
      throw new \Exception("The version.php file could not be opened for writing.");
    }
    $success = fwrite($handle, "<?php define('PUBLISHER_VERSION', '$version_number');");
    if (!$success) {
      throw new \Exception("Failed to write to version.php.");
    }
    fclose($handle);
  }
}
