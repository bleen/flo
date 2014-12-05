<?php

namespace flo\Command\PullRequest;

use flo\Drupal;
use flo\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Github;


class DeployCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-deploy')
      ->setDescription('Deploy a specific pull-request to a solo environment.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be deployed.'
      )
      ->addOption(
        'database',
        'd',
        InputOption::VALUE_NONE,
        'If set we will also create the release Database.'
      )
      ->addOption(
        'ref',
        'r',
        InputOption::VALUE_REQUIRED,
        'The commit to deploy on GitHub using their Deployment API.'
      )
      ->addOption(
        'env',
        'e',
        InputOption::VALUE_REQUIRED,
        'If set we will tag the release on GitHub using their Deployment API.'
      )
      ->addOption(
        'site-dir',
        'sd',
        InputOption::VALUE_REQUIRED,
        'The site-dir that is being deployed.',
        self::DEFAULT_SITE_DIR
      )
      ->addOption(
        'database-name',
        'D',
        InputOption::VALUE_REQUIRED,
        'The database name.'
      );
  }

  /**
   * Process pr-deploy job.
   *
   * - Takes the current working Environment and rsync it to where they belong (config: pr_directories).
   * - Post a deployment job to github deployment API.
   *
   * GH API: POST /repos/:owner/:repp/deployments
   *  - Extra Header: "Accept: application/vnd.github.cannonball-preview+json"
   *  - We need to save the Status ID for this Deployment.
   *
   * <pre>
   * curl --request POST
   *  --data '{
   *   "ref":"6d77282",
   *   "auto_merge": false,
   *   "payload": "{"user": "ericduran", "room_id":123456}",
   *   "environment": "test",
   *   "description": "W00t W00t"}'
   *  -H "Authorization: token 07f76f17aa077ee7fea9d5b3d9cef70993509697"
   *  -H "Content-Type: application/json"
   *  -H "Accept: application/vnd.github.cannonball-preview+json" https://api.github.com/repos/ericduran/Publisher7/deployments
   * </pre>
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|void
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $github = $this->getGithub();

    $site_dir = $input->getOption('site-dir');
    $pr_number = $input->getArgument('pull-request');
    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }

    // We always run from the top git directory.
    $git_root = new Process('git rev-parse --show-toplevel');
    $git_root->run();
    if (!$git_root->isSuccessful()) {
      throw new \RuntimeException($git_root->getErrorOutput());
    }

    $current_dir = new Process('pwd');
    $current_dir->run();
    if (!$current_dir->isSuccessful()) {
      throw new \RuntimeException($current_dir->getErrorOutput());
    }

    if ($git_root->getOutput() !== $current_dir->getOutput()) {
      throw new \Exception("You must run pr-deploy from the git root.");
    }

    if ($git_root->getOutput() !== $current_dir->getOutput()) {
      throw new \Exception("You must run pr-deploy from the git root.");
    }

    // Lets rsync this workspace now.
    $pull_request = $this->getConfigParameter('pull_request');
    $path = "{$pull_request['prefix']}-{$pr_number}.{$pull_request['domain']}";
    $url = "http://{$path}";
    $pr_directories = $this->getConfigParameter('pr_directories');
    $command = "rsync -qrltoD --delete --exclude='.git/*' . {$pr_directories}{$path}";
    $process = new Process($command);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Lets generate the settings.local.php file.
    $database_name = $input->getOption('database-name');

    if (empty($database_name)) {
      Drupal\DrupalSettings::generateSettings($pr_number, $site_dir);
    }
    else {
      Drupal\DrupalSettings::generateSettings($pr_number, $site_dir, $database_name);
    }

    if (!empty($input->getOption('database'))) {
      // Support multi-sites
      if (!empty($site_dir)) {
        $process = new Process("cd {$pr_directories}{$path}/docroot/sites/{$site_dir} && drush sql-create --yes");
      }
      else {
        $process = new Process("cd {$pr_directories}{$path}/docroot && drush sql-create --yes && drush psi --yes --account-pass=pa55word");
      }

      // The installation process has a 7 minute timeout anything greater gets cutoff.
      $process->setTimeout(60 * 60 * 7);
      $process->run();
      if (!$process->isSuccessful()) {
        throw new \RuntimeException($process->getErrorOutput());
      }
    }

    // If they have an env set then we also tag it on github.
    if (!empty($input->getOption('env')) && !empty($input->getOption('ref'))) {
      $ref = $input->getOption('ref');
      $environment = $input->getOption('env');

      $deployment = $github->api('deployment')->create(
        $this->getConfigParameter('organization'),
        $this->getConfigParameter('repository'),
        array(
          'ref' => $ref,
          'environment' => $environment,
          'description' => 'flo:pr-deploy',
          'auto_merge' => FALSE
        )
      );

      $github->api('deployment')->update(
        $this->getConfigParameter('organization'),
        $this->getConfigParameter('repository'),
        $deployment['id'],
        array(
          'state' => 'success',
          'target_url' => $url,
          'description' => 'Completed Test Deployment'
        )
      );
    }

    $output->writeln("<info>PR #$pr_number has been deployed to {$url}.</info>");
  }
}
