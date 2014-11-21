<?php

/**
 * @file
 *
 * Validate a composer.json file to have all the needed publisher settings.
 * Ex. flo composer-validate composer.json
 */

namespace flo\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;


class ValidateComposerCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this
      ->setName('composer-validate')
      ->setDescription('Validate a projects composer file for publisher & flo')
      ->addArgument(
        'file',
        InputArgument::REQUIRED,
        'composer.json file'
      );

  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $composer_file = $input->getArgument('file');
    $errors = 0;

    if ($fs->exists($composer_file)) {
      $json = \GuzzleHttp\json_decode(file_get_contents($composer_file));

      // Lets make sure our satis server is up to date.
      if ($json->repositories[0]->url != 'http://nbcuots.github.io/satis/') {
        $errors++;
        $output->writeln('<error>Incorrect satis server. Correct URL Should be: http://nbcuots.github.io/satis/</error>');
      }

      // Lets make sure all install  scrpt are uptodate.
      if ($json->scripts->{"post-install-cmd"} != 'vendor/nbcuots/publisher7/scripts/publisher-install') {
        $errors++;
        $output->writeln('<error>Incorrect post-install-cmd. You must have post-install-cmd set to ' .
        '"vendor/nbcuots/publisher7/scripts/publisher-install" for publisher7.</error>');
      }

      // Lets make sure all update script are uptodate.
      if ($json->scripts->{"post-update-cmd"} != 'vendor/nbcuots/publisher7/scripts/publisher-install') {
        $errors++;
        $output->writeln('<error>Incorrect post-update-cmd. You must have post-update-cmd set to ' .
          '"vendor/nbcuots/publisher7/scripts/publisher-install" for publisher7.</error>');
      }
    }

    if ($errors > 0) {
      $output->writeln('Please update your composer.json. Optional: You can run flo composer-fix to fix any issues we found.');
    }
  }
}
