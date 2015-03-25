<?php

namespace flo\Test\Command\PullRequest;

use flo\Test;
use flo\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeployCommandTest extends Test\FunctionalFramework {

  /**
   * The main flo application.
   *
   * @var string
   */
  private $application;

  /**
   * set up test environment filesystem.
   */
  public function setUp() {
    $this->application = new Application();
    parent::setUp();
  }

  /**
   * Test Running pr-deploy with an string instead of PR Number.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #PR must be a number.#
   */
  public function testStringPRDeploy() {
    $command_run_script = $this->application->find('pr-deploy');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "Not-A-Valid-PR",
    ));
  }

  /**
   * Test Running pr-deploy without pr_directories set.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #You must have a pr_directory set in your flo config.#
   */
  public function testMissingPRDirectoryConfig() {
    // Remove the pr_directories.
    $config = $this->application->getFlo()->getConfig();
    $config->set('pr_directories', NULL);
    $this->application->getFlo()->setConfig($config);

    $command_run_script = $this->application->find('pr-deploy');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => 1,
    ));
  }

  /**
   * Test Running pr-deploy outside of a git repo.
   *
   * @expectedException RuntimeException
   * @expectedExceptionMessageRegExp #Not a git repository.#
   */
  public function testNonGitRootPRDeploy() {
    chdir(sys_get_temp_dir());
    $command_run_script = $this->application->find('pr-deploy');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => 1,
    ));
  }

  /**
   * Test Running pr-deploy inside a git repo but not the root.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #You must run pr-deploy from the git root.#
   */
  public function testGitRepoNonRootPRDeploy() {
    $this->fs->mkdir('Test');
    chdir('Test');
    $command_run_script = $this->application->find('pr-deploy');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => 1,
    ));
  }
}
