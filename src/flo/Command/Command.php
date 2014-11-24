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
  const GITHUB_LABEL_IGNORED = 'ci:ignored';
  const GITHUB_LABEL_POSTPONED = 'ci:postponed';
  const GITHUB_LABEL_REJECTED = 'ci:rejected';

  private $config;
  private $repository;
  private $github;

  /**
   * Constructor.
   *
   * @param string $name The name of the command
   *
   * @throws \LogicException When the command name is empty
   *
   * @api
   */
  public function __construct($name = null) {
    parent::__construct($name);
  }


  protected function initialize(InputInterface $input, OutputInterface $output) {
    try {
      $configuration = new Configuration();
      $this->config = $configuration->getConfig();
    }
    catch (\Exception $e) {
      $output->writeln("<error>error: {$e->getMessage()}</error>");
    }
  }

  /**
   * Get a config parameter.
   *
   * @param $name
   *   The parameter name
   * @param $default
   *   The default value, if parameter not set
   *
   * @return mixed|null
   *   The parameter value
   */
  public function getConfigParameter($name, $default = NULL) {
    $value = $default;
    $config = $this->getConfig();
    if (array_key_exists($name, $config)) {
      $value = $config[$name];
    }
    return $value;
  }

  /**
   * @return array
   */
  public function getConfig() {
    if (null === $this->config) {
      $this->config = $this->getApplication()->getConfig();
    }
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
      $this->repository = Repository::open(getcwd(), $this->getConfigParameter('git', '/usr/bin/git'));
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
}
