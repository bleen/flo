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

  // TODO: Figure out how to make this work with symfony2 finder depth.
  // based on my testing there's a bug I need to track down in the Finder component.
  // for now it's easier ot just hard-code it.
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
    else {
      $this->project_config_file = '../' . $this->project_config_file;
      if ($fs->exists($this->project_config_file)) {
        $retval = TRUE;
      }
    }

    return $retval;
  }


  /**
   * Load the Project config yaml into memory.
   *
   * @return mixed
   * @throws \Exception
   */
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
