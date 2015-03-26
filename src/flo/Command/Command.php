<?php

namespace flo\Command;

use flo\PHPGit\Repository;
use Github;

/**
 * Class Command
 * @package flo\Command
 */
class Command extends \Symfony\Component\Console\Command\Command {

  const DEFAULT_SITE_DIR = 'default';
  const GITHUB_LABEL_CERTIFIED = 'ci:certified';
  const GITHUB_LABEL_ERROR = 'ci:error';
  const GITHUB_LABEL_POSTPONED = 'ci:postponed';
  const GITHUB_LABEL_REJECTED = 'ci:rejected';
  const GITHUB_PULL_REQUEST_ID = 'ghprbPullId';
  const GITHUB_PULL_REQUEST_COMMIT = 'ghprbActualCommit';
  const GITHUB_PULL_REQUEST_TARGET_BRANCH = 'ghprbTargetBranch';
  const JENKINS_BUILD_URL = 'BUILD_URL';

  public $github;
  private $repository;

  /**
   * Helper to get a config parameter.
   *
   * @param string $key
   *   The parameter name.
   *
   * @return mixed|null
   *   The parameter value.
   */
  public function getConfigParameter($key) {
    return $this->getApplication()->getFlo()->getConfig()->get($key);
  }

  /**
   * @return Github\Client
   */
  public function getGithub($cache = TRUE, $api = NULL) {
    if (null === $this->github) {
      if ($cache) {
        $this->github = new Github\Client(
          new Github\HttpClient\CachedHttpClient(array('cache_dir' => '/tmp/github-api-cache'))
        );
      }
      else {
        $this->github = new Github\Client();
      }
      $this->github->authenticate($this->getConfigParameter('github_oauth_token'), NULL, Github\Client::AUTH_URL_TOKEN);

      if ($api !== NULL) {
        return $this->github->api($api);
      }
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

    $github = $this->getGithub(FALSE, 'issue');
    $github->labels()->add(
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
    $github = $this->getGithub(FALSE, 'issue');
    $github->labels()->remove(
      $this->getConfigParameter('organization'),
      $this->getConfigParameter('repository'),
      $pr_number,
      $label
    );
  }

  /**
   * Helper function to get HOME environment variable.
   *
   * getenv() and $_ENV tend to act differently when fetching "HOME"
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
