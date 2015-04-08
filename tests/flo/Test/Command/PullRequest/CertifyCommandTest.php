<?php

namespace flo\Test\Command\PullRequest;

use flo\Test;
use Github;
use Symfony\Component\Console\Tester\CommandTester;

class CertifyCommandTest extends PullRequestTestHelper {

  /**
   * Test Running pr-deploy with an string instead of PR Number.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #PR must be a number.#
   */
  public function testNANPRCertifyException() {
    $command_run_script = $this->application->find('pr-certify');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "Not-A-Valid-PR",
    ));
  }

  /**
   * Test Running pr-deploy.
   */
  public function testAddingCertifyLabel() {
    $this->writeConfig();

    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('pr-certify');
    $command_run_script->github = $this->getMockLabelApi('repos/NBCUOTS/Publisher7_nbcuflo/issues/1/labels');

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "1",
    ));

    $this->assertEquals("PR #1 has been certified.\n", $command_tester->getDisplay());
  }

}
