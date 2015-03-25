<?php

namespace flo\Test;

use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Process\Process;


class FunctionalFramework extends \PHPUnit_Framework_TestCase {

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

    parent::setUp();
  }

  /**
   * Remove the files and directories created for this test.
   */
  public function tearDown() {
    if ($this->fs->exists($this->root)) {
      $this->fs->remove($this->root);
    }
    parent::tearDown();
  }
}
