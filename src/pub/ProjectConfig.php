<?php

namespace pub;

use Symfony\Component\Yaml;
use Illuminate\Filesystem\Filesystem;

/**
 * Class Project Config
 *
 * Manages our project-config.yml for project based configuration.
 *
 * @package pub
 */
class ProjectConfig {

  public $project_config_file = 'project-config.yml';
  public $settings = NULL;

  /**
   * Returns TRUE or FALSE based on the config file existing.
   *
   * @return bool
   */
  public function exists() {
    $retval = FALSE;
    $fs = new Filesystem();

    if ($fs->exists($this->project_config_file)) {
      $retval = TRUE;
    }

    return $retval;
  }


  public function load() {
    $fs = new Filesystem();
    $yaml = new Yaml\Parser();

    if (!$this->exists()) {
      throw new \Exception("{$this->project_config_file} does not exists");
    }

    $this->settings = $yaml->parse($fs->get($this->project_config_file));

    return $this->settings;
  }
}
