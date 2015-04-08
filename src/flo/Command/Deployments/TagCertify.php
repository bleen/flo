<?php

namespace flo\Command\Deployments;

use flo\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TagCertify extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('tag-release')
      ->setDescription('Marks a Tag on GitHub as a production tag.')
      ->addArgument(
        'tag',
        InputArgument::REQUIRED,
        'The tag on GitHub to be marked as a "release" tag.'
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $tag = $input->getArgument('tag');
    $GitHub = $this->getGithub(FALSE, 'repo');

    try {
      $release = $GitHub->releases()->showTag(
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
    if (empty($release['prerelease']) && !empty($release['id'])) {
      $output->writeln("Tag: {$tag} is already marked as production ready.");
    }
    else {
      // If there is no release lets create one.
      if (empty($release['id'])) {
        $GitHub->releases()->create($this->getConfigParameter('organization'), $this->getConfigParameter('repository'),
          array('tag_name' => $tag, 'prerelease' => FALSE)
        );
        $output->writeln("<info>A release was created for {$tag} and marked as a production ready.</info>");
      }
      else {
        // If there is already a release lets just mark it as non pre-release.
        $GitHub->releases()->edit($this->getConfigParameter('organization'), $this->getConfigParameter('repository'), $release['id'],
          array('prerelease' => FALSE)
        );
        $output->writeln("<info>Tag: {$tag} was marked as a production ready.</info>");
      }
    }
  }
}
