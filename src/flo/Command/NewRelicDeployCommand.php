<?php

namespace flo\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp;

class NewRelicDeployCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('new-relic')
      ->setDescription('Deploy a tag to new-relic.')
      ->addArgument(
        'tag',
        InputArgument::REQUIRED,
        'The tag to mark in New Relic.'
      );
  }

  /**
   * Process pr-certify job.
   *
   * This adds a ci:certified label to the PR (aka issue) on github.
   *
   * GH API: POST /repos/:owner/:repo/issues/:number/labels ["Label1", "Label2"]
   *
   * @param InputInterface $input
   * @param OutputInterface $output
   * @return int|void
   * @throws \Exception
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $guzzle = new GuzzleHttp\Client();
    $new_relic = $this->getConfigParameter('new_relic');

    $tag = $input->getArgument('tag');
    $res = $guzzle->post('https://api.newrelic.com/deployments.xml', [
      'body' => [
        'deployment[app_name]' => 'nbcupublisher7.devi1',
        'deployment[application_id]' => '4328192',
        'deployment[description]' => 'Regular weekly deployment release',
        'deployment[user]' => 'ericduran',
        'deployment[revision]' => $tag,
      ],
      'headers' => [
        'x-api-key' => $new_relic,
      ]
    ]);

    print_r($res);
  }
}
