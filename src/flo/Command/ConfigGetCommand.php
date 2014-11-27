<?php

namespace flo\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;

class ConfigGetCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    // Do not initialized config for flo config-get.
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('config-get')
      ->setDescription('Get configurations for flo command')
      ->addArgument(
        'config',
        InputArgument::OPTIONAL,
        'Configuration property you want to get'
      );
  }

  /**
   * Get a specific config or loop all configs.
   *
   * Ex. ```flo config-get git``` : /usr/local/lib
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $home_directory = $this->getHome();
    $flo_config_file = $home_directory . '/.config/flo';

    if (!$fs->exists($flo_config_file)) {
      return $output->writeln("<error>No flo config file exist.</error>");
    }

    $flo_config = Yaml::parse($flo_config_file);

    $config_name = $input->getArgument('config');
    if (!empty($config_name)) {
      if (!isset($flo_config[$config_name])) {
        return $output->writeln("<error>No configuration set for '{$config_name}'.</error>");
      }

      return $output->writeln("<info>{$config_name}: {$flo_config[$config_name]}</info>");
    }
    else {
      // Otherwise lets just pretty print all the options.
      foreach ($flo_config as $key => $value) {
        $output->writeln("<info>{$key}: {$value}</info>");
      }
    }
  }
}
