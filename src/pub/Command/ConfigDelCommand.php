<?php

namespace pub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Symfony\Component\Yaml\Dumper;
use Illuminate\Filesystem\Filesystem;

class ConfigDelCommand extends Command {

  protected function configure() {
    $this->setName('config-del')
      ->setDescription('Delete configurations key for pub command')
      ->addArgument(
        'config-name',
        InputArgument::REQUIRED,
        'name for the configuration property.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $yaml = new Yaml\Parser();
    $dumper = new Dumper();
    $pub_config_file = getenv("HOME") . '/.config/pub';

    if (!$fs->exists($pub_config_file)) {
      return $output->writeln("<error>No pub config file exist.</error>");
    }

    $pub_config = $yaml->parse($fs->get($pub_config_file));
    $config_name = $input->getArgument('config-name');

    if (isset($pub_config[$config_name])) {
      // If it exists remove the key and resaved the config file.
      unset($pub_config[$config_name]);
      $updated_config = $dumper->dump($pub_config);
      $fs->put($pub_config_file, $updated_config);
      $output->writeln("<info>{$config_name} has been deleted.</info>");
    }
    else {
      // No config exist as info since they wanted to remove it anyways.
      $output->writeln("<info>No config key '{$config_name}' exist.</info>");
    }
  }
}
