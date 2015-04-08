<?php

namespace flo\Test\Command\Deployments;

use Github;
use flo\Test\Command\PullRequest\PullRequestTestHelper;
use Symfony\Component\Console\Tester\CommandTester;


class TagPreReleaseTest extends PullRequestTestHelper {

  /**
   * Test Running tag-release.
   */
  public function testCreatingPreRelease() {
    $this->writeConfig();

    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('tag-pre-release');
    $command_run_script->github = $this->getMockReleaseslApi();

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'tag' => "1.0.0",
    ));

    $this->assertEquals("A release was created for 1.0.0 and marked as a pre-release.\n", $command_tester->getDisplay());
  }

  /**
   * Test Running tag-release.
   */
  public function testCreatingBadPreRelease() {
    $this->writeConfig();

    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('tag-pre-release');
    $command_run_script->github = $this->getMockBadReleaseslApi();

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'tag' => "1.0.0",
    ));

    $this->assertEquals("Tag: 1.0.0 does not exists.\n", $command_tester->getDisplay());
  }

  /**
   * Test Running tag-release.
   */
  public function testCreatingDupeRelease() {
    $this->writeConfig();

    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('tag-pre-release');
    $command_run_script->github = $this->getMockReleaseslApi(array('id' => 1), 'showTag');

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'tag' => "1.0.0",
    ));

    $this->assertEquals("Tag: 1.0.0 was marked as a pre-release.\n", $command_tester->getDisplay());
  }

  /**
   * Test Running tag-release.
   */
  public function testEditingRelease() {
    $this->writeConfig();

    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('tag-pre-release');
    $command_run_script->github = $this->getMockReleaseslApi(array('id' => 1, 'prerelease' => 1), 'showTag');

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'tag' => "1.0.0",
    ));

    $this->assertEquals("Tag: 1.0.0 is already marked as pre-release.\n", $command_tester->getDisplay());
  }

}
