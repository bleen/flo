<?php

/**
 * This file is an overwrite of the Symfony Filesystem component.
 *
 * We have to overwrite the dumpFile method to allow it to work with streams.
 * The issue is that Symfony tries to make fully save writes and it
 * accomplishes this by writing to a temp folder 1st, then moving
 * the file to the location but when working with a Virtual File System
 * we can not move across different streams. So in oder to test this
 * we need to completely overwrite this method.
 */

namespace flo\SymfonyOverwrite;

use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Provides basic utility to manipulate the file system.
 */
class Filesystem extends \Symfony\Component\Filesystem\Filesystem {

  /**
   * Atomically dumps content into a file.
   *
   * @param  string       $filename The file to be written to.
   * @param  string       $content  The data to write into the file.
   * @param  null|int     $mode     The file mode (octal). If null, file permissions are not modified
   *                                Deprecated since version 2.3.12, to be removed in 3.0.
   * @throws IOException            If the file cannot be written to.
   */
  public function dumpFile($filename, $content, $mode = 0666)
  {
    $dir = dirname($filename);

    if (!is_dir($dir)) {
      $this->mkdir($dir);
    } elseif (!is_writable($dir)) {
      throw new IOException(sprintf('Unable to write to the "%s" directory.', $dir), 0, null, $dir);
    }

    if (false === @file_put_contents($filename, $content)) {
      throw new IOException(sprintf('Failed to write file "%s".', $filename), 0, null, $filename);
    }

    if (null !== $mode) {
      $this->chmod($filename, $mode);
    }
  }
}
