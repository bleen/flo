<?php

namespace flo\Command;

use flo\SymfonyOverwrite\Filesystem;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;


class ProjectSetup extends Command {

  const FLO_YAML_PATH = './flo.yml';

  protected function configure() {
    $this->setName('project-setup')
      ->setDescription('Initializes proper flo.yml for for Flo projects.');
  }

  /**
   * Executes the flo project-setup command.
   * @param InputInterface $input
   * @param OutputInterface $output
   * @throws \Exception
   *
   * @return null
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $this->checkGitRoot();

    $fs = new Filesystem();
    $helper = $this->getHelper('question');

    if ($fs->exists($this::FLO_YAML_PATH)) {
      throw new \Exception("flo.yml already exists.");
    }

      // Questions.
    $org_question = new Question('Please enter the name of the GitHub organization: ', 'NBCUOTS');
    $repo_question = new Question('Please enter the name github repository: ');
    $shortname_question = new Question('Please enter the Publisher short name: ');
    $github_question = new Question('Please enter the GitHub git url: ');
    $acquia_question = new Question('Please enter the Acquia git url: ');
    $pull_request_question = new Question('Please enter the pull-request url: ');
    $pull_request_prefix_question = new Question('Please enter the pull-request prefix(ex p7): ');

    // Prompts.
    $organization = $helper->ask($input, $output, $org_question);
    $repository = $helper->ask($input, $output, $repo_question);
    $shortname = $helper->ask($input, $output, $shortname_question);
    $github_git_url = $helper->ask($input, $output, $github_question);
    $acqui_git_url = $helper->ask($input, $output, $acquia_question);
    $pull_request_domain = $helper->ask($input, $output, $pull_request_question);
    $pull_request_prefix = $helper->ask($input, $output, $pull_request_prefix_question);



    $flo_yml = array(
      'organization' => $organization,
      'repository' => $repository,
      'shortname' => $shortname,
      'github_git_uri' => $github_git_url,
      'acquia_git_uri' => $acqui_git_url,
      'pull_request' => array(
        'domain' => $pull_request_domain,
        'prefix' => $pull_request_prefix,
      ),
    );

    $fs->dumpFile($this::FLO_YAML_PATH, Yaml::dump($flo_yml, 2, 2));
    if ($fs->exists($this::FLO_YAML_PATH)) {
      $output->writeln("<info>Flo.yml created.</info>");
    }
  }
}
