<?php

namespace pub\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml;
use Illuminate\Config;
use Illuminate\Filesystem\Filesystem;
use pub\PHPGit\Repository;


class GitInitCommand extends Command {
  const GIT_REMOTE_LIST = "git remote -v | grep fetch | awk '{print $1 \"|\" $2 \";\"}'";


  protected function configure() {
    $this->setName('git-init')
      ->setDescription('Initialized proper git remotes NBCUOTS & Acquia');
    }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $fs = new Filesystem();
    $yaml = new Yaml\Parser();
    $profile_file = 'project-config.yml';
    $pub_config_file = getenv("HOME") . '/.config/pub';


    if ($fs->exists($pub_config_file)) {
      $config = $yaml->parse($fs->get($pub_config_file));
    }
    else {
      $config = array(
        'git' => '/usr/bin/git'
      );
    }


    if (!$fs->exists($profile_file)) {
      throw new \Exception("{$profile_file} does not exists");
    }

    $value = $yaml->parse($fs->get($profile_file));
    if (empty($value['acquia_git_uri'])) {
      throw new \Exception("{$profile_file} is missing an acquia_git_uri property");
    }

    $git = Repository::open(getcwd(), $config['git']);
    $remotes = $git->getCurrentRemote();
    if (!isset($remotes['acquia']) || $remotes['acquia']['fetch'] !== $value['acquia_git_uri']) {
      if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
        $output->writeln("<info>verbose: calling git remote add acquia {$value['acquia_git_uri']}</info>>");
      }

      $git->addRemote('acquia', $value['acquia_git_uri']);
      $output->writeln("<info>Acquia Remote added</info>");
    }
    else {
      $output->writeln("<info>Acquia remote already exists</info>");
    }
  }
}
