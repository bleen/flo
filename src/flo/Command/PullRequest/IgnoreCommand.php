<?php

namespace flo\Command\PullRequest;

use flo\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class IgnoreCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-ignore')
      ->setDescription('Ignore a specific pull-request.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be ignored.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $pr_number = $input->getArgument('pull-request');
    $this->addGithubLabel($pr_number, self::GITHUB_LABEL_IGNORED);
    $output->writeln("<info>PR #$pr_number has been ignored.</info>");
  }
}
