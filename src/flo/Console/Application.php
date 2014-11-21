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

    $configuration = new Configuration();
    $this->config = $configuration->getConfig();

    // Check if hub exists if not throw an error.
    $process = new Process('hub --version');
    $process->run();
    if (!$process->isSuccessful()) {
      // If you do not have hub we do nothing.
      throw new \RuntimeException($process->getErrorOutput());
    }

    $this->addCommands(array(
      new Command\AcquiaCloudHooksCommand(),
      new Command\ConfigDelCommand(),
      new Command\ConfigGetCommand(),
      new Command\ConfigSetCommand(),
      new Command\GitInitCommand(),
      new Command\NewReleaseCommand(),
      new Command\NewRelicDeployCommand(),
      new Command\PullRequest\CertifyCommand(),
      new Command\PullRequest\DeployCommand(),
      new Command\PullRequest\IgnoreCommand(),
      new Command\PullRequest\IntegrationCommand(),
      new Command\PullRequest\PostPoneCommand(),
      new Command\PullRequest\RejectCommand(),
      new Command\PullRequest\UnPostPoneCommand(),
      new Command\PullRequest\UnRejectCommand(),
      new Command\UpdateCommand(),
      new Command\ValidateComposerCommand(),
    ));
  }

  /**
   * @return array
   */
  public function getConfig() {
    return $this->config;
  }
}
