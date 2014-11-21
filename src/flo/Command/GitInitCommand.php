<?php

namespace flo\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class GitInitCommand extends Command {

  protected function configure() {
    $this->setName('git-init')
      ->setDescription('Initialized proper git remotes NBCUOTS & Acquia');
    }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $repository = $this->getRepository();
    $remotes = $repository->getCurrentRemote();
    $acquia_git_uri = $this->getConfigParameter('acquia_git_uri');
    if (!isset($remotes['acquia']) || $remotes['acquia']['fetch'] !== $acquia_git_uri) {
      if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
        $output->writeln("<info>verbose: calling git remote add acquia {$acquia_git_uri}</info>>");
      }

      $repository->addRemote('acquia', $acquia_git_uri);
      $output->writeln("<info>Acquia Remote added</info>");
    }
    else {
      $output->writeln("<info>Acquia remote already exists</info>");
    }
  }
}
