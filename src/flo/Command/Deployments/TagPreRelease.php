<?php

namespace flo\Command\Deployments;

use flo\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagPreRelease extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tag-pre-release')
      ->setDescription('Marks a Tag on GitHub as a non-production tag.')
      ->addArgument(
        'tag',
        InputArgument::REQUIRED,
        'The tag on GitHub to be marked as a "pre-release" tag.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $tag = $input->getArgument('tag');
    $GitHub = $this->getGithub(FALSE);

    try {
      $release = $GitHub->api('repo')->releases()->showTag(
        $this->getConfigParameter('organization'),
        $this->getConfigParameter('repository'),
        $tag
      );
    }
    catch (\Exception $e) {
      $output->writeln("<error>Tag: {$tag} does not exists.</error>");
      return 1;
    }

    // Check if the release is already marked "prerelease".
    if (!empty($release['prerelease']) && $release['prerelease'] == 1) {
      $output->writeln("Tag: {$tag} is already marked as pre-release.");
    }
    else {
      // If there is no release lets create one.
      if (empty($release['id'])) {
        $GitHub->api('repo')->releases()->create($this->getConfigParameter('organization'), $this->getConfigParameter('repository'),
          array('tag_name' => $tag, 'prerelease' => TRUE)
        );
        $output->writeln("<info>A release was created for {$tag} and marked as a pre-release.</info>");
      }
      else {
        // If there is already a release lets just mark it pre-release.
        $GitHub->api('repo')->releases()->edit($this->getConfigParameter('organization'), $this->getConfigParameter('repository'), $release['id'],
          array('prerelease' => TRUE)
        );
        $output->writeln("<info>Tag: {$tag} was marked as a pre-release.</info>");
      }
    }
  }
}
