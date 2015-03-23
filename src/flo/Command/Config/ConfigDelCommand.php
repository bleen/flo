<?php

namespace flo\Command\Config;

use flo\Command\Command;
use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;

class ConfigDelCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    // Do not initialized config for flo config-del.
  }

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('config-del')
      ->setDescription('Delete configurations key for flo command')
      ->addArgument(
        'config-name',
        InputArgument::REQUIRED,
        'name for the configuration property.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $dumper = new Dumper();
    $home_directory = $this->getHome();
    $flo_config_file = $home_directory . '/.config/flo';

    if (!$fs->exists($flo_config_file)) {
      return $output->writeln("<error>No flo config file exist.</error>");
    }

    $flo_config = Yaml::parse($flo_config_file);
    $config_name = $input->getArgument('config-name');

    if (isset($flo_config[$config_name])) {
      // If it exists remove the key and resaved the config file.
      unset($flo_config[$config_name]);
      $updated_config = $dumper->dump($flo_config, 1);
      $fs->dumpFile($flo_config_file, $updated_config);
      $output->writeln("<info>{$config_name} has been deleted.</info>");
    }
    else {
      // No config exist as info since they wanted to remove it anyways.
      $output->writeln("<info>No config key '{$config_name}' exist.</info>");
    }
  }
}
