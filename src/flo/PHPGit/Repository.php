<?php

namespace flo\PHPGit;

class Repository extends \TQ\Git\Repository\Repository {


  /**
   * Returns the remote info
   *
   * This overwrite the parent method to fix a bug.
   *
   * @link https://github.com/teqneers/PHP-Stream-Wrapper-for-Git/pull/20
   * @see TQ\Git\Repository\Repository::getCurrentRemote().
   *
   * @return  array
   */
  public function getCurrentRemote() {
    /** @var $result CallResult */
    $result = $this->getGit()->{'remote'}($this->getRepositoryPath(), array(
      '-v'
    ));
    $result->assertSuccess(sprintf('Cannot remote "%s"', $this->getRepositoryPath()));

    $tmp = $result->getStdOut();

    preg_match_all('/([a-z]*)\h(.*)\h\((.*)\)/', $tmp, $matches);

    $retVar = array();
    foreach($matches[0] as $key => $value) {
      $retVar[$matches[1][$key]][$matches[3][$key]] = $matches[2][$key];
    }

    return $retVar;
  }


  /**
   * Add a new remote repository to a git repo.
   *
   * @param $name
   * @param $uri
   * @return array
   */
  public function addRemote($name, $uri) {
    $result = $this->getGit()->{'remote'}($this->getRepositoryPath(), array(
      'add',
      $name,
      $uri
    ));
    $result->assertSuccess(
      sprintf('Cannot add remote for "%s"', $this->getRepositoryPath())
    );

    $output = rtrim($result->getStdOut());

    return $output;
  }
}

