<?php

namespace flo\Console;

use Symfony\Component\Process\Process;
use flo\Command;
use flo\Configuration;


class Application extends \Symfony\Component\Console\Application {

  private $config;

  /**
   * {@inheritdoc}
   */
  public function __construct($name = 'UNKNOWN', $version = 'UNKNOWN') {

    parent::__construct($name, $version);

    // Check if hub exists if not throw an error.
    $process = new Process('hub --version');
    $process->run();
    if (!$process->isSuccessful()) {
      // If you do not have hub we do nothing.
      throw new \RuntimeException($process->getErrorOutput());
    }

    // Add commands.
    $this->addCommands(array(
      new Command\AcquiaCloudHooksCommand(),
      new Command\ConfigDelCommand(),
      new Command\ConfigGetCommand(),
      new Command\ConfigSetCommand(),
      new Command\GitInitCommand(),
      new Command\NewReleaseCommand(),
      new Command\NewRelicDeployCommand(),
      new Command\PhpSyntaxChecker(),
      new Command\PhpCodeStyleChecker(),
      new Command\PullRequest\CertifyCommand(),
      new Command\PullRequest\DeployCommand(),
      new Command\PullRequest\DestroyCommand(),
      new Command\PullRequest\IntegrationCommand(),
      new Command\PullRequest\PostPoneCommand(),
      new Command\PullRequest\RejectCommand(),
      new Command\PullRequest\UnPostPoneCommand(),
      new Command\PullRequest\UnRejectCommand(),
      new Command\UpdateCommand(),
    ));
  }

  /**
   * @return array
   */
  public function getConfig() {
    if (!isset($this->config)) {
      $configuration = new Configuration();
      $this->config = $configuration->getConfig();
    }
    return $this->config;
  }

}
