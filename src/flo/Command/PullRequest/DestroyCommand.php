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

  /**
   * The number of closed PR's to check if they need destroying.
   */
  const CLOSED_PR_DESTROY_LIMIT = 50;

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-destroy')
      ->setDescription('Destroy pull-request environment(s), removing its web root and database.')
      ->addArgument(
        'pull-request',
        InputArgument::OPTIONAL,
        'The pull-request number to be destroyed.'
      )
      ->addOption(
        'site-dir',
        'sd',
        InputOption::VALUE_REQUIRED,
        'The site-dir that is being destroyed.',
        self::DEFAULT_SITE_DIR
      )
      ->addOption(
        'closed',
        'c',
        InputOption::VALUE_NONE,
        'Destroy all closed pull-requests.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {

    $destroy_prs = array();

    $fs = new Filesystem();
    $github = $this->getGithub();

    $pull_request = $this->getConfigParameter('pull_request');
    $pr_directories = $this->getConfigParameter('pr_directories');

    $site_dir = $input->getOption('site-dir');

    // Get last CLOSED_PR_DESTROY_LIMIT number of closed pull-requests.
    if ($input->getOption('closed')) {
      $prs = $github->api('pr')->all(
        $this->getConfigParameter('organization'),
        $this->getConfigParameter('repository'),
        array(
          'state' => 'closed',
          'per_page' => self::CLOSED_PR_DESTROY_LIMIT,
        )
      );
      // Add any that exist to $destroy_prs.
      foreach ($prs as $pr) {
        $destroy_prs[] = $pr['number'];
      }
    }
    else {
      $pr_number = $input->getArgument('pull-request');
      if (!is_numeric($pr_number)) {
        throw new \Exception("PR must be a number.");
      }
      $destroy_prs[] = $pr_number;
    }

    foreach ($destroy_prs as $destroy_pr) {

      // @TODO get this path from a central place.
      $pr_path = rtrim($pr_directories, '/') . "/{$pull_request['prefix']}-{$destroy_pr}.{$pull_request['domain']}";

      if ($fs->exists($pr_path)) {
        // Drop the database.
        // @TODO set/get database name in central place, and use in DrupalSettings.
        $database = $pull_request['prefix'] . '_' . $destroy_pr;
        $process = new Process("drush sqlq 'DROP database {$database}'", $pr_path . "/docroot/sites/" . $site_dir);
        $process->run();

        // Remove the PR's web root.
        $fs->remove($pr_path);

        // @TODO destroy memcache bins.

        $output->writeln("<info>Successfully destroyed PR #{$destroy_pr}</info>");
      }
      else {
        $output->writeln("<info>No need to destroy PR #{$destroy_pr}</info>");
      }
    }

  }
}
