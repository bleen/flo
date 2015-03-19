<?php

namespace flo;

/**
 * Class Flo.
 *
 * @package flo
 */
class Flo {

  const VERSION = '@package_version@';

  /**
   * Holds Flo configuration settings.
   *
   * @var Config
   */
  private $config;

  /**
   * Getter for Configuration.
   *
   * @return Config
   *   Configuration object.
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Setter for Configuration.
   *
   * @param Config $config
   *   Configuration object.
   */
  public function setConfig(Config $config) {
    $this->config = $config;
  }

}
