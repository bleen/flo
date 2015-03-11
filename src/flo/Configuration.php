<?php

namespace flo;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 * @package flo
 */
class Configuration implements ConfigurationInterface {

  /**
   * An array of configuration values.
   *
   * @var array
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $fs = new Filesystem();

    $user_config = array();
    $user_config_file = getenv("HOME") . '/.config/flo';
    if ($fs->exists($user_config_file)) {
      $user_config = Yaml::parse($user_config_file);
    }

    $project_config = array();
    $process = new Process('git rev-parse --show-toplevel');
    $process->run();
    if ($process->isSuccessful()) {
      $project_config_file = trim($process->getOutput()) . '/flo.yml';
      if ($fs->exists($project_config_file)) {
        $project_config = Yaml::parse($project_config_file);
      }
    }

    try {
      $processor = new Processor();
      $this->config = $processor->processConfiguration(
        $this,
        array($user_config, $project_config)
      );
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
            ->scalarNode('subscription')
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
      ->end();
    return $tree_builder;
  }

  /**
   * Get an array of configuration values.
   *
   * @return array
   *   Array of combined user and project configuration.
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * Get a config parameter.
   *
   * @param string $name
   *   The parameter name.
   *
   * @return mixed|null
   *   The parameter value
   *
   * @throws \Exception
   *   If the desired parameter is not set.
   */
  public function getParameter($name) {
    $config = $this->getConfig();
    if (array_key_exists($name, $config)) {
      return $config[$name];
    }
    else {
      throw new \Exception("The config variable '{$name}' is not set. Run `flo config-set {$name} some-value` to set this value globally, or update your project's flo.yml.", 1);
    }
  }

}
