<?php

namespace flo\Test\Command;

use flo\Console\Application;
use Symfony\Component\Process\Process;
use flo\Command;
use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Console\Tester\CommandTester;


class NewReleaseCommandTest extends \PHPUnit_Framework_TestCase {
  /**
   * @var string
   */
  private $root;

  /**
   * @var string
   */
  private $versionFile;

  /**
   * @var string
   */
  private $versionConstant;

  /**
   * Set up test environment filesystem.
   */
  public function setUp() {
    // Each time the test runs, there should be a different version file and
    // version constant to ensure that data does not cross-contaminate tests.
    // Delta is used to ensure uniqueness.
    static $delta = 0;
    $this->versionFile = 'version' . $delta . '.php';
    $this->versionConstant = 'PUBLISHER_VERSION' . $delta;
    $delta++;

    // Attempt to create a temporary directory for the tests and change the
    // current working directory to that directory.
    try {
      $this->root = sys_get_temp_dir() . '/flo-test';
      mkdir($this->root);
    } catch (\Exception $e) {
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
   * Run the flo new-release command.
   *
   * @param string $increment
   *   (Optional) A valid semantic version or major|minor|patch
   *
   * @return CommandTester
   */
  private function runNewRelease($increment = '0.0.0') {
    $application = new Application();
    $command_new_release = $application->find('new-release');
    $commandTester = new CommandTester($command_new_release);
    $commandTester->execute(array(
      'command' => $command_new_release->getName(),
      'increment' => $increment,
    ));

    return $commandTester;
  }

  /**
   * Test the New Release command.
   *
   * @param string $initial_version
   *   (optional) A semantic version number or an empty string.
   * @param string $increment
   *   A semantic version number or major | minor | patch.
   * @param string $expected_version
   *   A semantic version number or an empty string.
   *
   * @dataProvider newReleaseProvider
   */
  public function testNewRelease($initial_version, $increment, $expected_version) {
    $fs = new Filesystem();

    $version_file = $this->root . '/' . $this->versionFile;
    $initial_content = "<?php define('$this->versionConstant', '$initial_version');\n";
    $expected_content = "<?php define('$this->versionConstant', '$expected_version');\n";

    // Setup some project configs making sure that version file and version
    // constant are unique between tests.
    $project_config = "---\nvars:\n  version_file: $this->versionFile\n  version_constant: $this->versionConstant";
    $fs->dumpFile($this->root . "/project-config.yml", $project_config);

    if (!empty($initial_version)) {
      $fs->dumpFile($version_file, $initial_content);
    }

    $this->runNewRelease($increment);

    // Assert that the version.php file has the correct contents.
    $this->assertEquals($expected_content, file_get_contents($version_file), 'The version file contained the correct version information.');

    // Assert that the git tag was created correctly.
    $process = new Process("git show-ref $expected_version");
    $success = $process->run();
    $this->assertTrue($success === 0, "The git tag ($expected_version) was created successfully.");

    // Assert that the tagged commit has the proper change to the version file.
    $process = new Process("git show $expected_version");
    $process->run();
    $this->assertContains('+' . $expected_content, $process->getOutput(), 'The tagged commit contains the proper change to the version file.');
  }

  /**
   * Data provider for testNewRelease.
   *
   * @return array
   */
  public function newReleaseProvider() {
    return array(
      array('', '0.2.1', '0.2.1'),
      array('1.0.0', '1.2.3', '1.2.3'),
      array('2.1.2', 'major', '3.0.0'),
      array('4.5.6', 'minor', '4.6.0'),
      array('5.2.9', 'patch', '5.2.10'),
    );
  }

  /**
   * Remove the files and directories created for this test.
   */
  public function tearDown() {
    $fs = new Filesystem();
    if ($fs->exists($this->root)) {
      $fs->remove($this->root);
    }
  }

}
