<?php

namespace pub\Command\PullRequest;

use pub\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class UnPostPoneCommand extends Command {
  
  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('pr-unpostpone')
      ->setDescription('Un-postpone a specific pull-request.')
      ->addArgument(
        'pull-request',
        InputArgument::REQUIRED,
        'The pull-request number to be un-postponed.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $pr_number = $input->getArgument('pull-request');
    $this->removeGithubLabel($pr_number, self::GITHUB_LABEL_POSTPONED);
    $output->writeln("<info>PR #$pr_number has been un-postponed.</info>");
  }
}
