<?php

namespace flo\Test;

use flo;
use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Process\Process;


/**
 * Class FactoryTest.
 *
 * @package flo\Test
 */
class FactoryTest extends \PHPUnit_Framework_TestCase {

  /**
   * The root project directory.
   *
   * @var string
   */
  private $root;

  /**
   * Filesystem object.
   *
   * @var Filesystem
   */
  private $fs;

  /**
   * Set up test environment filesystem.
   */
  public function setUp() {

    $this->fs = new Filesystem();

    // Attempt to create a temporary directory for the tests and change the
    // current working directory to that directory.
    try {
      $this->root = sys_get_temp_dir() . '/' . str_replace('\\', '-', __CLASS__);
      if ($this->fs->exists($this->root)) {
        $this->fs->remove($this->root);
      }
      $this->fs->mkdir($this->root);
    }
    catch (\Exception $e) {
      $this->tearDown();
      // Throw the exception again so the tests will be skipped.
      throw $e;
    }
    chdir($this->root);

    // Setup a git repo.
    $process = new Process('git init');
    $process->run();
  }

  /**
   * Test that \flo\Factory::create() handles environments variables correctly.
   */
  public function testcreateFlo() {
    $this->writeConfig();
    $this->flo = \flo\Factory::create();

    // Lets
    $this->assertEquals('Publisher7_nbcuflo', $this->flo->getConfig()->get('repository'));


    $override_project_config = <<<EOT
---
organization: Another
repository: Publisher7_nbcuflo
shortname: Publisher7_nbcuflo
github_git_uri: git@github.com:NBCUOTS/Publisher7_nbcuflo.git
pull_request:
  domain: pr.publisher7.com
  prefix: flo-test
scripts:
  pre_deploy_cmd:
  - scripts/pre-deploy.sh
  post_deploy_cmd:
  - scripts/post-deploy.sh
EOT;

    putenv('FLO=' . $override_project_config);
    $this->flo2 = \flo\Factory::create();

    // Now lets assert that the Orgnization is Another and not NBCUOTS.
    $this->assertEquals('Another', $this->flo2->getConfig()->get('organization'));
  }

  /**
   * Helper function to write configuration file.
   */
  private function writeConfig() {
    // Create a sample flo.yml file.
    $project_config = <<<EOT
---
organization: NBCUOTS
repository: Publisher7_nbcuflo
shortname: Publisher7_nbcuflo
github_git_uri: git@github.com:NBCUOTS/Publisher7_nbcuflo.git
pull_request:
  domain: pr.publisher7.com
  prefix: flo-test
scripts:
  pre_deploy_cmd:
  - scripts/pre-deploy.sh
  post_deploy_cmd:
  - scripts/post-deploy.sh
EOT;

    $this->fs->dumpFile($this->root . "/flo.yml", $project_config);
  }

  /**
   * Remove the files and directories created for this test.
   */
  public function tearDown() {
    if ($this->fs->exists($this->root)) {
      $this->fs->remove($this->root);
    }
  }
}
