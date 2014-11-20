<?php

namespace pub\Command\PullRequest;

use pub\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class RejectCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-reject')
      ->setDescription('Reject a specific pull-request.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be rejected.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $pr_number = $input->getArgument('pull-request');
    $this->addGithubLabel($pr_number, self::GITHUB_LABEL_REJECTED);
    $output->writeln("<info>PR #$pr_number has been rejected.</info>");
  }
}
