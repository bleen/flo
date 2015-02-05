<?php

namespace flo\Command;

use flo\Configuration;
use flo\PHPGit\Repository;
use Github;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class Command extends \Symfony\Component\Console\Command\Command {

  const GITHUB_LABEL_CERTIFIED = 'ci:certified';
  const GITHUB_LABEL_ERROR = 'ci:error';
  const GITHUB_LABEL_POSTPONED = 'ci:postponed';
  const GITHUB_LABEL_REJECTED = 'ci:rejected';
  const GITHUB_PULL_REQUEST_ID = 'ghprbPullId';
  const GITHUB_PULL_REQUEST_COMMIT = 'ghprbActualCommit';
  const GITHUB_PULL_REQUEST_TARGET_BRANCH = 'ghprbTargetBranch';


  const DEFAULT_SITE_DIR = 'default';

  private $config;
  private $repository;
  private $github;

  /**
   * {@inheritdoc}
   */
  protected function initialize(InputInterface $input, OutputInterface $output) {
    $configuration = new Configuration();
    $this->config = $configuration->getConfig();
  }

  /**
   * Get a config parameter.
   *
   * @param $name
   *   The parameter name
   *
   * @return mixed|null
   *   The parameter value
   */
  public function getConfigParameter($name) {
    $config = $this->getConfig();
    if (array_key_exists($name, $config)) {
      return $config[$name];
    }
    else {
      throw new \Exception("The config variable '$name' is not set. Run `flo config-set $name some-value` to set this value.", 1);
    }
  }

  /**
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }

  /**
   * @return Github\Client
   */
  public function getGithub() {
    if (null === $this->github) {
      $this->github = new Github\Client(
        new Github\HttpClient\CachedHttpClient(array('cache_dir' => '/tmp/github-api-cache'))
      );
      $this->github->authenticate($this->getConfigParameter('github_oauth_token'), NULL, Github\Client::AUTH_URL_TOKEN);
    }
    return $this->github;
  }

  /**
   * @return \TQ\Git\Repository\Repository
   */
  public function getRepository() {
    if (null === $this->repository) {
      $this->repository = Repository::open(getcwd(), $this->getConfigParameter('git'));
    }
    return $this->repository;
  }

  /**
   * Helper function to add a Github label.
   *
   * This adds the $label to the PR (aka issue) on Github.
   * GH API: POST /repos/:owner/:repo/issues/:number/labels ["Label1", "Label2"]
   *
   * @param $pr_number
   *   The Github Issue or Pull Request number
   * @param $label
   *   The label to apply
   *
   * @throws \Exception
   */
  public function addGithubLabel($pr_number, $label) {
    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }
    $github = $this->getGithub();
    $github->api('issue')->labels()->add(
      $this->getConfigParameter('organization'),
      $this->getConfigParameter('repository'),
      $pr_number,
      $label
    );
  }

  /**
   * Helper function to remove a Github label.
   *
   * This removes the $label to the PR (aka issue) on Github.
   * GH API: DELETE /repos/:owner/:repo/issues/:number/labels/:name
   *
   * @param $pr_number
   *   The Github Issue or Pull Request number
   * @param $label
   *   The label to apply
   *
   * @throws \Exception
   */
  public function removeGithubLabel($pr_number, $label) {
    if (!is_numeric($pr_number)) {
      throw new \Exception("PR must be a number.");
    }
    $github = $this->getGithub();
    $github->api('issue')->labels()->remove(
      $this->getConfigParameter('organization'),
      $this->getConfigParameter('repository'),
      $pr_number,
      $label
    );
  }

  /**
   * Helper function to get HOME environment variable.
   *
   * getenv() and $_ENV tend to act diffectly when fetching "HOME"
   * this lets us extract this out so we can easily overwrite it.
   * This is especially usefull when doing unitTest and we want to
   * "fake" our home path to a virtual directory.
   *
   */
  protected function getHome() {
    if (!empty($_ENV['HOME'])) {
      $home_directory = $_ENV['HOME'];
    }
    else {
      $home_directory = getenv("HOME");
    }

    return $home_directory;
  }
}
