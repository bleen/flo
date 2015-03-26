<?php

namespace flo\Test\Command;

use flo\Console\Application;
use flo\Command;
use Symfony\Component\Console\Tester\CommandTester;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;



class ConfigGetSetDelCommandTest extends \PHPUnit_Framework_TestCase {
  /**
   * @var  vfsStreamDirectory
   */
  private $root;

  /**
   * set up test environment filesystem.
   */
  public function setUp() {
    $this->root = vfsStream::setup('home');
    $_ENV['HOME'] = vfsStream::url('home');
  }

  /**
   * Test the config-get, config-set, & config-del as a group.
   */
  public function testExecuteConfigOptions() {
    $application = new Application();

    $command_get = $application->find('config-get');
    $command_set = $application->find('config-set');
    $command_del = $application->find('config-del');

    // flo should complain about missing config file 1st.
    $commandTester = new CommandTester($command_get);
    $commandTester->execute(array('command' => $command_get->getName()));
    $this->assertContains('No flo config file exist.', $commandTester->getDisplay());

    // Lets try and delete a key without a config file
    $commandTester = new CommandTester($command_del);
    $commandTester->execute(array('command' => $command_del->getName(), 'config-name' => 'test1'));
    $this->assertContains('No flo config file exist.', $commandTester->getDisplay());


    // Lets set a couple of config value. flo should confirm it saved it.
    $commandTester = new CommandTester($command_set);
    $commandTester->execute(array('command' => $command_set->getName(), 'config-name' => 'test1', 'config-value' => 'test1'));
    $this->assertContains('test1: test1 has been saved.', $commandTester->getDisplay());

    $commandTester = new CommandTester($command_set);
    $commandTester->execute(array('command' => $command_set->getName(), 'config-name' => 'test2', 'config-value' => 'test2'));
    $this->assertContains('test2: test2 has been saved.', $commandTester->getDisplay());

    // Lets try to get the value via config-get now.
    $commandTester = new CommandTester($command_get);
    $commandTester->execute(array('command' => $command_get->getName(), 'config' => 'test1'));
    $this->assertContains('test1: test1', $commandTester->getDisplay());

    // Now lets delete the test1 key.
    $commandTester = new CommandTester($command_del);
    $commandTester->execute(array('command' => $command_del->getName(), 'config-name' => 'test1'));
    $this->assertContains('test1 has been deleted.', $commandTester->getDisplay());

    // Now lets try and delete the same key key.
    $commandTester = new CommandTester($command_del);
    $commandTester->execute(array('command' => $command_del->getName(), 'config-name' => 'test1'));
    $this->assertContains("No config key 'test1'", $commandTester->getDisplay());

    // Now lets try and get the test1 key.
    $commandTester = new CommandTester($command_get);
    $commandTester->execute(array('command' => $command_get->getName(), 'config' => 'test1'));
    $this->assertContains("No configuration set for 'test1'", $commandTester->getDisplay());

    // Lets try to get all the value via config-get now.
    $commandTester = new CommandTester($command_get);
    $commandTester->execute(array('command' => $command_get->getName()));
    $this->assertContains('test2: test2', $commandTester->getDisplay());

    // Now lets test with an array of options.
    file_put_contents($_ENV['HOME'] . '/.config/flo', "acquia: { username: eric.duran@nbcuni.com, password: TESTING }");
    $commandTester = new CommandTester($command_get);
    $commandTester->execute(array('command' => $command_get->getName()));
    $expected = "acquia:
  username: eric.duran@nbcuni.com
  password: TESTING";
    $this->assertContains($expected, $commandTester->getDisplay());
  }
}
