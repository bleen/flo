<?php

namespace flo\Test;

use flo\Config;


/**
 * Class ConfigTest.
 *
 * @package flo\Test
 */
class ConfigTest extends \PHPUnit_Framework_TestCase {

  /**
   * Test merging of multiple configuration arrays.
   *
   * @param array $expected
   *   Expected config values.
   * @param array $user_config
   *   User config values.
   * @param array $local_config
   *   Local config values.
   *
   * @dataProvider dataMergeConfigs
   */
  public function testMergeConfigs(array $expected, $user_config = array(), $local_config = array()) {
    $config = new Config($user_config, $local_config);
    $this->assertEquals($expected, $config->all());
  }

  /**
   * Data provider for testMergeConfigs.
   */
  public function dataMergeConfigs() {
    $data = array();
    $data['defaults'] = array(
      array(
        'git' => '/usr/bin/git',
        'organization' => 'NBCUOTS',
        'scripts' => array(),
      ),
    );
    $data['user override'] = array(
      array(
        'git' => '/usr/bin/git',
        'organization' => 'USER_OVERRIDE',
        'scripts' => array(),
      ),
      array(
        'organization' => 'USER_OVERRIDE',
      ),
    );
    $data['local override'] = array(
      array(
        'git' => '/usr/bin/git',
        'organization' => 'LOCAL_OVERRIDE',
        'scripts' => array(),
      ),
      array(
        'organization' => 'USER_OVERRIDE',
      ),
      array(
        'organization' => 'LOCAL_OVERRIDE',
      ),
    );
    return $data;
  }

  /**
   * Test getting a config value.
   */
  public function testGet() {
    $config = new Config(array(
      'organization' => 'OVERRIDE',
    ));
    $this->assertEquals('OVERRIDE', $config->get('organization'));
    $this->assertNull($config->get('NULL'));
  }

  /**
   * Test that invalid configuration throws an exception.
   *
   * @expectedException \Exception
   * @expectedExceptionMessageRegExp #There is an error with your configuration.*#
   */
  public function testInvalidConfigException() {
    new Config(array(
      'invalid' => 'INVALID',
    ));
  }

}
