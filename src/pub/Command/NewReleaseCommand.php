<?php

namespace pub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Filesystem\Filesystem;
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
      );
    }

  /**
   * Process the New Release command.
   *
   * - Update the version.php file
   * - Commit the change and tag it
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
    $fs = new Filesystem();
    $fs->put($version_filename, "<?php define('PUBLISHER_VERSION', '$version_number');\n");
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

  }
}
