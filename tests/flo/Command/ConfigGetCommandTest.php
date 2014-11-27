<?php


namespace flo\Test\Command;

use flo\Console\Application;
use flo\Command;
use Symfony\Component\Console\Tester\CommandTester;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;



class ConfigGetCommandTest extends \PHPUnit_Framework_TestCase {
  /**
   * @var  vfsStreamDirectory
   */
  private $root;

  /**
   * set up test environmemt filesystem.
   */
  public function setUp() {
    $this->root = vfsStream::setup('home');
    $_ENV['HOME'] = vfsStream::url('home');
  }

  /**
   * Test that flow complains about a missing flo config file.
   */
  public function testExecuteNoConfig() {
    $application = new Application();
    $command = $application->find('config-get');
    $commandTester = new CommandTester($command);
    $commandTester->execute(array('command' => $command->getName()));

    // flo should complain about missing config file 1st.
    $this->assertContains('No flo config file exist.', $commandTester->getDisplay());
  }
}
