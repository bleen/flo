<?php

namespace pub;

use Symfony\Component\Yaml;
use Illuminate\Filesystem\Filesystem;

/**
 * Class Config
 *
 * Manages our ~/.config/pub file for user based configuration.
 *
 * @package pub
 */
class Config {

  public $pub_config_file = '';

  function __construct() {
    $this->pub_config_file =  getenv("HOME") . '/.config/pub';
  }

  /**
   * Returns TRUE or FALSE based on the config file existing.
   *
   * @return bool
   */
  public function exists() {
    $retval = FALSE;
    $fs = new Filesystem();

    if ($fs->exists($this->pub_config_file)) {
      $retval = TRUE;
    }

    return $retval;
  }

  /**
   * Returns a loaded configuration file if it exists, FALSE otherwise.
   * @return bool|mixed
   */
  public function load() {
    $fs = new Filesystem();
    $yaml = new Yaml\Parser();

    if (!$this->exists()) {
      return FALSE;
    }

    return $yaml->parse($fs->get($this->pub_config_file));
  }
}
