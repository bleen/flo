<?php

namespace flo\Command\PullRequest;

use flo\Drupal;
use flo\Command\Command;
use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Github;


class DestroyCommand extends Command {

  const DEFAULT_SITE_DIR = 'default';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-destroy')
      ->setDescription('Destroy a specific pull-request environment.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be destroyed.'
      )
      ->addOption(
        'site-dir',
        'sd',
        InputOption::VALUE_REQUIRED,
        'The site-dir that is being deployed.',
        self::DEFAULT_SITE_DIR
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $pr_number = $input->getArgument('pull-request');
    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }

    $site_dir = $input->getOption('site-dir');

    $pull_request = $this->getConfigParameter('pull_request');
    $pr_directories = $this->getConfigParameter('pr_directories');
    $pr_path = rtrim($pr_directories, '/') . "/{$pull_request['prefix']}-{$pr_number}.{$pull_request['domain']}";

    // @TODO set/get database name in central place, and use in DrupalSettings.
    $database = $pull_request['prefix'] . '_' . $pr_number;
    $process = new Process("drush sqlq 'DROP database {$database}'", $pr_path . "/docroot/sites/" . $site_dir);
    $process->run();

    $output->writeln("<info>Dropped database: {$database}</info>");

    $fs = new Filesystem();
    $fs->remove($pr_path);

    $output->writeln("<info>Removed PR installation: {$pr_path}</info>");

    // @TODO destroy memcache bins.

  }
}
