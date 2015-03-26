<?php

namespace flo\Test\Command\PullRequest;

use flo\Test;
use Github;
use Symfony\Component\Console\Tester\CommandTester;

class RejectCommandTest extends PullRequestTestHelper {

  /**
   * Test Running pr-reject with an string instead of PR Number.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #PR must be a number.#
   */
  public function testNANPRRejectException() {
    $command_run_script = $this->application->find('pr-reject');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "Not-A-Valid-PR",
    ));
  }

  /**
   * Test Running pr-postpone.
   */
  public function testAddingRejectedLabel() {
    $this->writeConfig();

    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('pr-reject');
    $command_run_script->github = $this->getMockLabelApi('repos/NBCUOTS/Publisher7_nbcuflo/issues/1/labels');

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "1",
    ));

    $this->assertEquals("PR #1 has been rejected.\n", $command_tester->getDisplay());
  }

}
