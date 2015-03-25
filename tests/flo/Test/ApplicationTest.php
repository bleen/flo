<?php

namespace flo\Test;

use flo\Console;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ApplicationTest extends \PHPUnit_Framework_TestCase {


  /**
   * Test that flo does not work without hub & process mocking.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessageRegExp #hub not found#
   */
  function testMissingHubApplication() {
    // Create a Mock Process Object.
    $process = $this->getMockBuilder('Symfony\Component\Process\Process')
      ->disableOriginalConstructor()
      ->getMock();

    // Make sure the isSuccessful method return FALSE so flo throws an exception.
    $process->method('isSuccessful')->willReturn(FALSE);
    $process->method('getErrorOutput')->willReturn('hub not found');

    $app = new Console\Application();
    // Set autoExit to false when testing & do not let it catch exceptions.
    $app->setAutoExit(TRUE);
    $app->setCatchExceptions(FALSE);

    // Overwrite Symfony\Component\Process\Process with our mock Process.
    $app->setProcess($process);

    // Run a command and wait for the exception.
    $appTester = new ApplicationTester($app);
    $appTester->run(array('command' => 'help'));
    $this->assertEquals(0, $appTester->getStatusCode());
  }


  /**
   * Test Succesful doRun.
   */
  function testExistingHubApplication() {
    // Create a Mock Process Object.
    $process = $this->getMockBuilder('Symfony\Component\Process\Process')
      ->disableOriginalConstructor()
      ->getMock();

    // Make sure the isSuccessful method return FALSE so flo throws an exception.
    $process->method('isSuccessful')->willReturn(TRUE);

    $app = new Console\Application();
    // Set autoExit to false when testing & do not let it catch exceptions.
    $app->setAutoExit(FALSE);

    // Overwrite Symfony\Component\Process\Process with our mock Process.
    $app->setProcess($process);

    // Run a command and wait for the exception.
    $appTester = new ApplicationTester($app);
    $appTester->run(array('command' => 'help'));
    $this->assertEquals(0, $appTester->getStatusCode());
  }
}
