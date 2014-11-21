<?php

namespace flo\Command\PullRequest;

use flo\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class UnRejectCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-unreject')
      ->setDescription('Un-reject a specific pull-request.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be un-rejected.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $pr_number = $input->getArgument('pull-request');
    $this->removeGithubLabel($pr_number, self::GITHUB_LABEL_REJECTED);
    $output->writeln("<info>PR #$pr_number has been un-rejected.</info>");
  }
}
