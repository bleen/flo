<?php

namespace pub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Illuminate\Filesystem\Filesystem;

class ConfigGetCommand extends Command {

  protected function configure() {
    $this->setName('config-get')
      ->setDescription('Get configurations for pub command')
      ->addArgument(
        'config',
        InputArgument::OPTIONAL,
        'Configuration property you want to get'
      );
  }

  /**
   * Get a specific config or loop all configs.
   *
   * Ex. ```pub config-get git``` : /usr/local/lib
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $yaml = new Yaml\Parser();
    $pub_config_file = getenv("HOME") . '/.config/pub';

    if (!$fs->exists($pub_config_file)) {
      return $output->writeln("<error>No pub config file exist.</error>");
    }

    $pub_config = $yaml->parse($fs->get($pub_config_file));

    $config_name = $input->getArgument('config');
    if (!empty($config_name)) {
      if (!isset($pub_config[$config_name])) {
        return $output->writeln("<error>No configuration set for '{$config_name}'.</error>");
      }

      return $output->writeln("<info>{$config_name}: {$pub_config[$config_name]}</info>");
    }
    else {
      // Otherwise lets just pretty print all the options.
      foreach ($pub_config as $key => $value) {
        $output->writeln("<info>{$key}: {$value}</info>");
      }
    }
  }
}
