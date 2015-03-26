<?php

namespace flo\Command\Deployments;

use Acquia\Cloud\Api\CloudApiClient;
use flo\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;



class TagDeploy extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tag-deploy')
      ->setDescription('Deploy a Tag on Acquia.')
      ->addOption('pre-release', null, InputOption::VALUE_NONE, 'Allow deployment of pre-release tag.')
      ->addArgument('env', InputArgument::REQUIRED,  'The environment on Acquia to deploy this tag.')
      ->addArgument('tag', InputArgument::REQUIRED, 'The tag on GitHub to be marked as a "pre-release" tag.');
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $tag = $input->getArgument('tag');
    $GitHub = $this->getGithub(FALSE);
    $is_environment_available = FALSE;

    if (!$input->getOption('pre-release')) {
      try {
        $release = $GitHub->api('repo')->releases()->showTag(
          $this->getConfigParameter('organization'),
          $this->getConfigParameter('repository'),
          $tag
        );
      }
      catch (\Exception $e) {
        $output->writeln("<error>Tag: {$tag} does not exists.</error>");
        return 1;
      }

      // If the release is already marked "prerelease". faile
      if (!empty($release['prerelease']) && $release['prerelease'] == 1) {
        $output->writeln("<error>Tag: {$tag} is marked as pre-release. Please certify before deploying</error>");
      }

      return 1;
    }



    $env = $input->getArgument('env');
    $tag = 'tags/' . $input->getArgument('tag');
    $acquia = $this->getConfigParameter('acquia');
    $subscription = $this->getConfigParameter('subscription');
    if (empty($acquia['username']) || empty($acquia['username']) || empty($subscription)) {
      $output->writeln("<error>You must have your acquia username/password/subscription configured in your flo config to run deploys.</error>");
      return 1;
    }

    $cloud_api = CloudApiClient::factory(array(
      'username' => $acquia['username'],
      'password' => $acquia['password']
    ));
    $acquia_environments = $cloud_api->environments($subscription);

    foreach ($acquia_environments as $acquia_env) {
      if ($acquia_env->name() == $env) {
        $is_environment_available = TRUE;
        break;
      }
    }

    if (!$is_environment_available) {
      $output->writeln("<error>Environment: {$env} does not exists on Acquia Cloud.</error>");
      return 1;
    }

    $task = $cloud_api->pushCode($subscription, $env, $tag);
    $progress = new ProgressBar($output, 100);
    $progress->start();

    while (!$task->completed()) {
      $progress->advance();
      // Lets not kill their api.
      sleep(2);
      $task = $cloud_api->task($subscription, $task);
    }

    $progress->finish();
    if ($task->state() == 'failed') {
      $output->writeln("\n<error>Task: {$task->id()} failed.</error>");
      $output->writeln($task->logs());
      return 1;
    }

    $output->writeln("\n<info>Tag: {$tag} deployed.</info>");
    $output->writeln($task->logs());
  }
}
