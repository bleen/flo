<?php

namespace flo;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;


/**
 * Class Config.
 *
 * @package flo
 */
class Config implements ConfigurationInterface {

  /**
   * Configuration values array.
   *
   * @var array
   */
  private $config = array();

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    try {
      $processor = new Processor();
      $configs = func_get_args();
      $this->config = $processor->processConfiguration($this, $configs);
    }
    catch (\Exception $e) {
      throw new \Exception("There is an error with your configuration: " . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigTreeBuilder() {
    $tree_builder = new TreeBuilder();
    $root_node = $tree_builder->root('project');
    $root_node
      ->children()
        ->scalarNode('git')
          ->defaultValue('/usr/bin/git')
        ->end()
        ->scalarNode('github_oauth_token')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('github_username')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('pr_directories')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('shortname')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('github_git_uri')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('acquia_git_uri')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('organization')
          ->defaultValue('NBCUOTS')
        ->end()
        ->scalarNode('repository')
          ->cannotBeEmpty()
        ->end()
        ->scalarNode('subscription')
        ->end()
        ->arrayNode('pull_request')
          ->children()
            ->scalarNode('domain')
              ->defaultValue('pr.publisher7.com')
            ->end()
            ->scalarNode('sync_alias')
              ->defaultValue('')
            ->end()
            ->scalarNode('prefix')
              ->cannotBeEmpty()
            ->end()
          ->end()
        ->end()
        ->arrayNode('acquia')
          ->children()
            ->scalarNode('username')
            ->end()
            ->scalarNode('password')
            ->end()
          ->end()
        ->end()
        ->arrayNode('vars')
          ->children()
            ->scalarNode('version_file')
              ->defaultValue('version.php')
            ->end()
            ->scalarNode('version_constant')
              ->defaultValue('PUBLISHER_VERSION')
            ->end()
          ->end()
        ->end()
        ->arrayNode('scripts')
          ->prototype('array')
            ->prototype('scalar')
            ->end()
          ->end()
        ->end()
      ->end();
    return $tree_builder;
  }

  /**
   * Check if config param is present.
   *
   * @param string $key
   *   Key of the param to check.
   *
   * @return bool
   *   TRUE if key exists.
   */
  public function has($key) {
    return array_key_exists($key, $this->config);
  }

  /**
   * Get a config param value.
   *
   * @param string $key
   *   Key of the param to get.
   *
   * @return mixed|null
   *   Value of the config param, or NULL if not present.
   */
  public function get($key) {
    return $this->has($key) ? $this->config[$key] : NULL;
  }

  /**
   * Set a config param value.
   *
   * @param string $key
   *   Key of the param to get.
   *
   * @param mixed $val
   *   Value of the param to set.
   *
   * @return bool
   */
  public function set($key, $val) {
    return $this->config[$key] = $val;
  }

  /**
   * Get all config values.
   *
   * @return array
   *   All config galues.
   */
  public function all() {
    return $this->config;
  }

}
