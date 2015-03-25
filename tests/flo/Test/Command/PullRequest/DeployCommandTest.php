<?php

namespace flo\Test\Command\PullRequest;

use flo\Test;
use flo\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DeployCommandTest extends Test\FunctionalFramework {

  /**
   * Test Running pr-deploy without pr_directories set.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #You must have a pr_directory set in your flo config.#
   */
  public function testMissingPRDirectoryConfig() {
    // Run the command.
    $application = new Application();

    // Remove the pr_directories.
    $config = $application->getFlo()->getConfig();
    $config->set('pr_directories', NULL);
    $application->getFlo()->setConfig($config);

    $command_run_script = $application->find('pr-deploy');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => 1,
    ));
  }
}
