<?php

namespace pub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Symfony\Component\Yaml\Dumper;
use Illuminate\Filesystem\Filesystem;


class ConfigSetCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('config-set')
      ->setDescription('Set configurations for pub command')
      ->addArgument(
        'config-name',
        InputArgument::REQUIRED,
        'name for the configuration property.'
      )
      ->addArgument(
        'config-value',
        InputArgument::REQUIRED,
        'value for the configuration property.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $yaml = new Yaml\Parser();
    $dumper = new Dumper();
    $pub_config_file = getenv("HOME") . '/.config/pub';

    if (!$fs->exists($pub_config_file)) {
      $fs->put($pub_config_file, "---");
      $output->writeln("<info>No pub config file exist.</error>");
    }

    $pub_config = $yaml->parse($fs->get($pub_config_file));
    $config_name = $input->getArgument('config-name');
    $config_value = $input->getArgument('config-value');

    $pub_config[$config_name] = $config_value;

    $updated_config = $dumper->dump($pub_config);

    $fs->put($pub_config_file, $updated_config);

    $output->writeln("<info>{$config_name}: {$config_value} has been saved.</info>");
  }
}
