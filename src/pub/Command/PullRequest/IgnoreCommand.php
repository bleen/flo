<?php

namespace pub\Command\PullRequest;

use pub\Config;
use pub\ProjectConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Github;

/**
 * Class IgnoreCommand
 *
 * Not really needed unless a developer just want to quicky ignore a PR.
 *
 * @package pub\Command\PullRequest
 */
class IgnoreCommand extends Command {
  const GITHUB_LABEL = 'ci:ignored';

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-ignore')
      ->setDescription('Ignore a specific pull-request.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be ignored.'
      );
  }

  /**
   * {@inheritdoc}
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

    $github->authenticate($pub_config['github-oauth-token'], NULL, Github\Client::AUTH_URL_TOKEN);

    $project_config->load();
    $labels = $github->api('issue')->labels()->add($project_config->settings['organization'] , $project_config->settings['repository'], $pr_number, self::GITHUB_LABEL);
    if (count($labels) >= 1) {
      $pr_url = "https://github.com/{$project_config->settings['organization']}/{$project_config->settings['repository']}/pull/{$pr_number}";
      $output->writeln("<info>Pull Request: $pr_url has been ignored.</info>");
    }
  }
}
