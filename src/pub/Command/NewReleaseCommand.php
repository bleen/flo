<?php

namespace pub\Command;

use pub\ProjectConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;


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

    // @TODO: Figure out how to make this work without hardocding the path to version.php.
    $version_filename = 'docroot/version.php';
    require_once $version_filename;

    // Update version.php.
    $version_number = $this->getNewVersionNumber($input, PUBLISHER_VERSION);
    $this->updateVersionFile($version_filename, $version_number);
    $output->writeln("<info>Successfully updated the version.php file and set the PUBLISHER_VERSION to " . $version_number . ".</info>");

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
   * Given the user input and the current version number, return the new version
   * number.
   *
   * @param InputInterface $input
   * @param string $current_version_number
   *   If the user input is major, minor or patch, the current version number
   *   will be incremented.
   *
   * @return string
   *   Returns a semantic version string.
   */
  protected function getNewVersionNumber(InputInterface $input, $current_version_number) {
    $increment = $input->getArgument('increment');

    $pattern = '/^(\d+)\.?(\d+)\.?(\d+)$/';
    if (preg_match($pattern, $increment)) {
      return $increment;
    }
    else {
      if (!preg_match($pattern, $current_version_number, $matches)) {
        throw new \Exception("The current version number in version.php (" . version_number_current . ") does not follow semantic versioning standards. Hence, you must specify an exact version number to use this command.");
      }

      switch ($increment) {
        case 'major':
          $matches[1]++;
          break;
        case 'minor':
          $matches[2]++;
          break;
        case 'patch':
          $matches[3]++;
          break;
        default:
          throw new \Exception("The argument must be either major, minor or patch or it must be a string that follows semantic version standards.");
      }
      unset($matches[0]);
      return implode('.', $matches);
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
