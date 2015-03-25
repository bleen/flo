<?php

namespace flo\Test\Command\PullRequest;

use flo\Console\Application;
use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Process\Process;


class DeployCommandTest extends \PHPUnit_Framework_TestCase {

  /**
   * The root project directory.
   *
   * @var string
   */
  private $root;

  /**
   * Filesystem object.
   *
   * @var Filesystem
   */
  private $fs;

  /**
   * Set up test environment filesystem.
   */
  public function setUp() {

    $this->fs = new Filesystem();

    // Attempt to create a temporary directory for the tests and change the
    // current working directory to that directory.
    try {
      $this->root = sys_get_temp_dir() . '/' . str_replace('\\', '-', __CLASS__);
      if ($this->fs->exists($this->root)) {
        $this->fs->remove($this->root);
      }
      $this->fs->mkdir($this->root);
    }
    catch (\Exception $e) {
      $this->tearDown();
      // Throw the exception again so the tests will be skipped.
      throw $e;
    }
    chdir($this->root);

    // Setup a git repo.
    $process = new Process('git init');
    $process->run();
  }

  /**
   * Test Running pr-deploy without pr_directories set.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #You must have a pr_directory set in your flo config.#
   */
  public function testMissingPRDirectoryConfig() {
    $this->writeConfig();

    // Run the command.
    $application = new Application();
    $command_run_script = $application->find('pr-deploy');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => 1,
    ));
  }


  /**
   * Helper function to write configuration file.
   */
  private function writeConfig() {
    // Create a sample flo.yml file.
    $project_config = <<<EOT
---
organization: NBCUOTS
repository: Publisher7_nbcuflo
shortname: Publisher7_nbcuflo
github_git_uri: git@github.com:NBCUOTS/Publisher7_nbcuflo.git
pull_request:
  domain: pr.publisher7.com
  prefix: flo-test
EOT;
    $this->fs->dumpFile($this->root . "/flo.yml", $project_config);
  }

  /**
   * Remove the files and directories created for this test.
   */
  public function tearDown() {
    if ($this->fs->exists($this->root)) {
      $this->fs->remove($this->root);
    }
  }
}
