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
   * {@inheritdoc}
   */
  public function __construct() {
    parent::__construct('flo', Flo::VERSION);
  }

  /**
   * {@inheritdoc}
   */
  public function doRun(InputInterface $input, OutputInterface $output) {
    // Check if hub exists if not throw an error.
    $process = new Process('hub --version');
    $process->run();
    if (!$process->isSuccessful()) {
      // If you do not have hub we do nothing.
      throw new \RuntimeException($process->getErrorOutput());
    }
    return parent::doRun($input, $output);
  }

  /**
   * Initializes all the flo commands.
   */
  protected function getDefaultCommands() {
    $commands = parent::getDefaultCommands();
    $commands[] = new Command\AcquiaCloudHooksCommand();
    $commands[] = new Command\ConfigDelCommand();
    $commands[] = new Command\ConfigGetCommand();
    $commands[] = new Command\ConfigSetCommand();
    $commands[] = new Command\GitInitCommand();
    $commands[] = new Command\NewReleaseCommand();
    $commands[] = new Command\NewRelicDeployCommand();
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
    $commands[] = new Command\Deployments\TagDeploy();
    $commands[] = new Command\Deployments\TagPreRelease();
    $commands[] = new Command\RunScriptCommand();
    $commands[] = new Command\UpdateCommand();

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
