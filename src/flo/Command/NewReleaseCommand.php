<?php

namespace flo\Command;

use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
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
   * {@inheritDoc}
   *
   * - Update the version.php file
   * - Commit the change and tag it
   *
   * This command assumes you are running it from the root of the repository.
   * Additionally, this command makes changes to the master branch.
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    // Get the version information from the project config.
    $project_config = new ProjectConfig();
    $project_config->load();
    if (!isset($project_config->settings['vars']['version_file'])) {
      throw new \Exception("You must have a vars:version_file set up in your project-config.yml. Ex. version_file: ./version.php");
    }
    if (!isset($project_config->settings['vars']['version_constant'])) {
      throw new \Exception("You must have a vars:version_constant set up in your project-config.yml. Ex. version_constant: MY_PROJECT_VERSION");
    }
    $version_file = $project_config->settings['vars']['version_file'];
    $version_constant = $project_config->settings['vars']['version_constant'];

    // Checkout the master branch.
    $process = new Process('git checkout ' . self::MASTER_BRANCH);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Determine the new version number.
    $increment = $input->getArgument('increment');
    try {
      // This will succeed when a specific version number is provided (as
      // opposed to "major" or "minor" or "patch"), otherwise an exception will
      // be thrown and the "catch" is used.
      $version = new version($increment);
      $version_number = $version->getVersion();
    } catch(\Exception $e) {
      // Get the current version from the version file if it exists. Otherwise
      // we're starting from scratch.
      if (file_exists($version_file)) {
        include_once $version_file;
      }
      $current_version = defined($version_constant) ? new version(constant($version_constant)) : new version('0.0.0');
      $version_number = $current_version->inc($increment);
    }

    // Update version file.
    $fs = new Filesystem();
    $fs->put($version_file, "<?php define('$version_constant', '$version_number');\n");
    $output->writeln("<info>Successfully updated the $version_file file and set the $version_constant to {$version_number}</info>");

    // Commit the updated version file.
    $process = new Process('git add ' . $version_file . ' && git commit -m "Preparing for new tag: ' . $version_number . '"');
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
