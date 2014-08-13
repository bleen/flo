<?php

namespace pub\Command\PullRequest;

use pub\Config;
use pub\ProjectConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
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
      );
  }

  /**
   * Process pr-deploy job.
   *
   * - Takes the current working Environment and rsync it to where they belong (config: pr-directories).
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
    $github = new Github\Client();
    $config = new Config();
    $project_config = new ProjectConfig();

    $pr_number = $input->getArgument('pull-request');
    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }

    if (!$config->exists()) {
      throw new \Exception("You must set up your pub configs settings for github.");
    }

    $pub_config = $config->load();
    if (!isset($pub_config['github-oauth-token'])) {
      throw new \Exception("You must have a github-oauth-token set up. Ex. pub config-set github-oauth-token MY_TOKEN_IS_THIS.");
    }

    $pub_config = $config->load();
    if (!isset($pub_config['pr-directories'])) {
      throw new \Exception("You must have a pr-directories set up. Ex. pub config-set pr-directories /var/www/html/ .");
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

    // Lets rsync this workspace now.
    $path = 'p7-' . $pr_number . '.pr.publisher7.com';
    $command = "rsync -aq --delete --exclude='.git/*' . {$pub_config['pr-directories']}{$path}";
    $process = new Process($command);
    $process->run();
    if (!$process->isSuccessful()) {
      throw new \RuntimeException($process->getErrorOutput());
    }
  }
}
