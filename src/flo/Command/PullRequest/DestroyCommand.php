<?php

namespace flo\Command\PullRequest;

use flo\Drupal;
use flo\Command\Command;
use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Github;


class DestroyCommand extends Command {

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

    $pull_request = $this->getConfigParameter('pull_request');
    $pr_directories = $this->getConfigParameter('pr_directories');

    // Get deployed PR's based on directories in $pr_directories and compare
    // that to the PR's open on GitHub.
    if ($input->getOption('closed')) {
      $deployed_prs = array();
      $finder = new Finder();
      // @TODO need to ensure that prefix is alphanumeric ONLY.
      $pattern = '/^' . $pull_request['prefix'] . '\-([0-9]+)/';
      $iterator = $finder
        ->directories()
        ->name($pattern)
        ->depth(0)
        ->in($pr_directories);
      foreach ($iterator as $directory) {
        preg_match($pattern, $directory->getFilename(), $matches);
        $deployed_prs[] = $matches[1];
      }
      if (!empty($deployed_prs)) {
        $github = $this->getGithub();
        $open_prs = array();
        $paginator  = new Github\ResultPager($github);
        $prs = $paginator->fetchAll(
          $github->api('pr'),
          'all',
          array(
            $this->getConfigParameter('organization'),
            $this->getConfigParameter('repository'),
            array('state' => 'open'),
          )
        );
        foreach ($prs as $pr) {
          $open_prs[] = $pr['number'];
        }
        // PR's to destroy are deployed PR's that are not open.
        $destroy_prs = array_diff($deployed_prs, $open_prs);
      }
    }
    else {
      $pr_number = $input->getArgument('pull-request');
      $destroy_prs[] = $pr_number;
    }

    if (!empty($destroy_prs)) {
      $fs = new Filesystem();
      $site_dir = $input->getOption('site-dir');

      foreach ($destroy_prs as $destroy_pr) {

        if (!is_numeric($destroy_pr)) {
          throw new \Exception("PR must be a number.");
        }

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
      }
    }
    else {
      $output->writeln("<info>No PR's to destroy.</info>");
    }
  }
}
