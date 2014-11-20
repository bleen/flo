<?php

namespace pub;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class Configuration implements ConfigurationInterface {

  private $pub_config_file = '';
  private $project_config_file = 'project-config.yml';
  private $config;

  function __construct() {
    $fs = new Filesystem();

    $pub_config = array();
    $this->pub_config_file = getenv("HOME") . '/.config/pub';
    if ($fs->exists($this->pub_config_file)) {
      $pub_config = Yaml::parse($fs->get($this->pub_config_file));
    }

    $project_config = array();
    $process = new Process('git rev-parse --show-toplevel');
    $process->run();
    if ($process->isSuccessful()) {
      $this->project_config_file = trim($process->getOutput()) . '/' . $this->project_config_file;
      if (!$fs->exists($this->project_config_file)) {
        throw new \Exception("{$this->project_config_file} does not exists");
      }
      $project_config = Yaml::parse($fs->get($this->project_config_file));
    }
    else {
      throw new \Exception("Must run pub from project directory");
    }

    try {
      $processor = new Processor();
      $this->config = $processor->processConfiguration(
        $this,
        array($pub_config, $project_config)
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

  public function getConfigTreeBuilder() {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('project');
    $rootNode
      ->children()
        ->scalarNode('github_oauth_token')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->scalarNode('github_username')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->scalarNode('pr_directories')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->scalarNode('shortname')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->scalarNode('github_git_uri')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->scalarNode('acquia_git_uri')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->scalarNode('organization')
          ->defaultValue('NBCUOTS')
          ->end()
        ->scalarNode('repository')
          ->isRequired()
          ->cannotBeEmpty()
          ->end()
        ->arrayNode('pull_request')
          ->children()
            ->scalarNode('domain')
              ->defaultValue('pr.publisher7.com')
              ->end()
            ->scalarNode('prefix')
              ->isRequired()
              ->cannotBeEmpty()
              ->end()
          ->end()
        ->end()
      ->end()
    ;
    return $treeBuilder;
  }
}
