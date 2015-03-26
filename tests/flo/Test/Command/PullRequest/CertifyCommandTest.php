<?php

namespace flo\Test\Command\PullRequest;

use flo\Test;
use flo\Console\Application;
use Github;
use Symfony\Component\Console\Tester\CommandTester;

class CertifyCommandTest extends Test\FunctionalFramework {

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
  public function testNANPRCertifyException() {
    $command_run_script = $this->application->find('pr-certify');
    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "Not-A-Valid-PR",
    ));
  }

  /**
   * Test Running pr-deploy with an string instead of PR Number.
   */
  public function testAddingCertifyLabel() {
    $this->writeConfig();

    //TODO: CLEAN THIS UP AND UNDERSTAND THIS!!!!!
    //I FINALLY UNDERSTAND HOW TO MOCK OBJECTS!!!!!!!!!!!

    $httpClientMock = $this->getMock('Guzzle\Http\Client', array('send'));
    $httpClientMock
      ->expects($this->any())
      ->method('send');

    $mock = $this->getMock('Github\HttpClient\HttpClient', array(), array(array(), $httpClientMock));
    $client = new Github\Client($mock);
    $client->setHttpClient($mock);

    // Mock the Issue API.
    $IssueMock = $this->getMockBuilder('Github\Api\Issue')
      ->setMethods(array('labels'))
      ->setConstructorArgs(array($client))
      ->getMock();

    // Mock the label API.
    $labelsMock = $this->getMockBuilder('Github\Api\Issue\Labels')
      ->setMethods(array('get', 'post', 'postRaw', 'patch', 'delete', 'put', 'head'))
      ->setConstructorArgs(array($client))
      ->getMock();

    // This actually runs an assert and makes sure our API call actually returns that :-O.
    $labelsMock->expects($this->once())
      ->method('post')
      ->with('repos/NBCUOTS/Publisher7_nbcuflo/issues/1/labels')
      ->will($this->returnValue('Test'));

    // Set up the Issue API to return the Label api.
    $IssueMock->expects($this->once())
      ->method('labels')
      ->willReturn($labelsMock);


    // Now after ALLLLL that set up, lets call our command
    $command_run_script = $this->application->find('pr-certify');
    $command_run_script->github = $IssueMock;

    $command_tester = new CommandTester($command_run_script);
    $command_tester->execute(array(
      'command' => $command_run_script->getName(),
      'pull-request' => "1",
    ));

    $this->assertEquals("PR #1 has been certified.\n", $command_tester->getDisplay());
  }

}
