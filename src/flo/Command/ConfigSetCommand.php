<?php

namespace flo\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml;
use Symfony\Component\Yaml\Dumper;


class ConfigSetCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('config-set')
      ->setDescription('Set configurations for flo command')
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
    $flo_config_file = getenv("HOME") . '/.config/flo';

    if (!$fs->exists($flo_config_file)) {
      $fs->put($flo_config_file, "---");
      $output->writeln("<error>No flo config file exist.</error>");
    }

    $flo_config = $yaml->parse($flo_config_file);
    $config_name = $input->getArgument('config-name');
    $config_value = $input->getArgument('config-value');

    $flo_config[$config_name] = $config_value;

    $updated_config = $dumper->dump($flo_config, 1);

    $fs->put($flo_config_file, $updated_config);

    $output->writeln("<info>{$config_name}: {$config_value} has been saved.</info>");
  }
}
