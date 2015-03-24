<?php

namespace flo\Test;

use flo\SymfonyOverwrite\Filesystem;

/**
 * Class FilesystemTest.
 *
 * @package flo\Test
 */
class FilesystemTest extends \PHPUnit_Framework_TestCase {

  /**
   * File
   */
  private $file;

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
  }

  /**
   * Test that invalid folder throws an Exception.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #Unable to write to the "/dev" directory.#
   *
   */
  public function testUnWriteableDirException() {
    $fs = new Filesystem();
    $fs->dumpFile("/dev/null", "Test Invalid Dir");
  }


  /**
   * Test that it can actually dump files.
   *
   * @expectedException Exception
   * @expectedExceptionMessageRegExp #Failed to write file#
   */
  public function testWriteableDirException() {
    $fs = new Filesystem();
    $this->file = sys_get_temp_dir() . '/' . str_replace('\\', '-', __CLASS__);

    // Write the file once with 0555, so we can't write it again.
    $fs->dumpFile($this->file, "Test Valid File", 0555);

    // Now lets assert our Exception is thrown.
    $fs->dumpFile($this->file, "Test Valid File");

    if ($this->fs->exists($this->file)) {
      $this->fs->remove($this->file);
    }
  }


  /**
   * Test a valid dump of the file.
   *
   */
  public function testDumpFile() {
    $fs = new Filesystem();
    $this->file = sys_get_temp_dir() . '/FilesystemTest-TEST';
    $fs->dumpFile($this->file, "Test Invalid Dir");
    $this->assertEquals("Test Invalid Dir", file_get_contents($this->file));
  }


  /**
   * Remove the files and directories created for this test.
   */
  public function tearDown() {
    if ($this->fs->exists($this->file)) {
      $this->fs->remove($this->file);
    }
  }
}
