<?php

namespace flo\Console;

use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use flo\Command;
use flo\Factory;
use flo\Flo;


/**
 * Class Application.
 *
 * @package flo\Console
 */
class Application extends BaseApplication {

  /**
   * @var Flo
   */
  protected $flo;

  /**
   * @var Process
   * Process
   */
  protected $process = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {
    parent::__construct('flo', Flo::VERSION);
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    // Check if hub exists if not throw an error.
    $process = $this->getProcess('hub --version');
    $process->run();
    if (!$process->isSuccessful()) {
      // If you do not have hub we do nothing.
      throw new \RuntimeException($process->getErrorOutput());
    }
    return parent::doRun($input, $output);
  }

  /**
   * Allow flo to overwrite the process command.
   *
   * @param $process
   */
  public function setProcess($process) {
    $this->process = $process;
  }

  /**
   * Used instead of Symfony\Component\Process\Process so we can easily mock it.
   *
   * This returns either an instantiated Symfony\Component\Process\Process or a mock object.
   * @param $commandline
   * @param null $cwd
   * @param array $env
   * @param null $input
   * @param int $timeout
   * @param array $options
   * @return Process
   *
   * @see Symfony\Component\Process\Process
   */
  public function getProcess($commandline, $cwd = null, array $env = null, $input = null, $timeout = 60, array $options = array()) {
    if ($this->process === NULL) {
      // @codeCoverageIgnoreStart
      // We ignore this since we mock it.
      return new Process($commandline, $cwd, $env, $input, $timeout, $options);
      // @codeCoverageIgnoreEnd
    }

    return $this->process;
  }

  /**
   * Initializes all the flo commands.
   */
  protected function getDefaultCommands() {
    $commands = parent::getDefaultCommands();
    $commands[] = new Command\ProjectSetup();
    $commands[] = new Command\Config\ConfigDelCommand();
    $commands[] = new Command\Config\ConfigGetCommand();
    $commands[] = new Command\Config\ConfigSetCommand();
    $commands[] = new Command\GitInitCommand();
    $commands[] = new Command\NewReleaseCommand();
    $commands[] = new Command\PhpSyntaxChecker();
    $commands[] = new Command\PhpCodeStyleChecker();
    $commands[] = new Command\PullRequest\CertifyCommand();
    $commands[] = new Command\PullRequest\DeployCommand();
    $commands[] = new Command\PullRequest\DestroyCommand();
    $commands[] = new Command\PullRequest\IntegrationCommand();
    $commands[] = new Command\PullRequest\PostPoneCommand();
    $commands[] = new Command\PullRequest\RejectCommand();
    $commands[] = new Command\PullRequest\UnPostPoneCommand();
    $commands[] = new Command\PullRequest\UnRejectCommand();
    $commands[] = new Command\PullRequestStatus\PhpUnitChecker();
    $commands[] = new Command\Deployments\TagCertify();
    $commands[] = new Command\Deployments\TagDeploy();
    $commands[] = new Command\Deployments\TagPreRelease();
    $commands[] = new Command\RunScriptCommand();

    return $commands;
  }

  /**
   * Get a configured Flo object.
   *
   * @return Flo
   *   A configured Flo object.
   */
  public function getFlo() {
    if (NULL === $this->flo) {
      $this->flo = Factory::create();
    }
    return $this->flo;
  }

}
