<?php

namespace flo;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class Configuration implements ConfigurationInterface {

  private $flo_config_file = '';
  private $project_config_file = 'project-config.yml';
  private $config;

  /**
   * {@inheritdoc}
   */
  public function __construct() {
    $fs = new Filesystem();

    $flo_config = array();
    $this->flo_config_file = getenv("HOME") . '/.config/flo';
    if ($fs->exists($this->flo_config_file)) {
      $flo_config = Yaml::parse($this->flo_config_file);
    }

    $project_config = array();
    $process = new Process('git rev-parse --show-toplevel');
    $process->run();
    if ($process->isSuccessful()) {
      $this->project_config_file = trim($process->getOutput()) . '/' . $this->project_config_file;
      if (!$fs->exists($this->project_config_file)) {
        throw new \Exception("{$this->project_config_file} does not exists");
      }
      $project_config = Yaml::parse($this->project_config_file);
    }
    else {
      throw new \Exception("Must run flo from project directory");
    }

    try {
      $processor = new Processor();
      $this->config = $processor->processConfiguration(
        $this,
        array($flo_config, $project_config)
      );
    }
    catch (\Exception $e) {
      throw new \Exception("There is an error with your configuration: " . $e->getMessage());
    }
  }

  /**
   * @return array
   */
  public function getConfig() {
    return $this->config;
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
        ->arrayNode('pull_request')
          ->children()
            ->scalarNode('domain')
              ->defaultValue('pr.publisher7.com')
              ->end()
            ->scalarNode('prefix')
              ->cannotBeEmpty()
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
}
