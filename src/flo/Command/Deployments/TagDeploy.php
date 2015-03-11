<?php

namespace flo\Command\Deployments;

use Acquia\Cloud\Api\CloudApiClient;
use flo\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;



class TagDeploy extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tag-deploy')
      ->setDescription('Deploy a Tag on Acquia.')
      ->addArgument(
        'env',
        InputArgument::REQUIRED,
        'The environment on Acquia to deploy this tag.'
      )
      ->addArgument(
        'tag',
        InputArgument::REQUIRED,
        'The tag on GitHub to be marked as a "pre-release" tag.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $is_environment_available = FALSE;
    $env = $input->getArgument('env');
    $tag = 'tags/' . $input->getArgument('tag');
    $acquia = $this->getConfigParameter('acquia');
    if (empty($acquia['username']) || empty($acquia['username']) || empty($acquia['subscription'])) {
      $output->writeln("<error>You must have your acquia username/password/subscription configured in your flo config to run deploys.</error>");
      return 1;
    }

    $cloudapi = CloudApiClient::factory(array(
      'username' => $acquia['username'],
      'password' => $acquia['password']
    ));

    $acquia_environments = $cloudapi->environments($acquia['subscription']);


    foreach ($acquia_environments as $acquia_env) {
      if($acquia_env->name() == $env) {
        $is_environment_available = TRUE;
        break;
      }
    }

    if (!$is_environment_available) {
      $output->writeln("<error>Environment: {$env} does not exists on Acquia Cloud.</error>");
      return 1;
    }

    $task = $cloudapi->pushCode($acquia['subscription'], $env, $tag);

    $progress = new ProgressBar($output, 100);
    $progress->start();

    while (!$task->completed()) {
      $progress->advance();
      // Lets not kill their api.
      sleep(2);
      $task = $cloudapi->task($acquia['subscription'], $task);
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
