<?php

namespace flo\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;


class RunScriptCommand extends Command {

  /**
   * {@inheritdoc}
   */
  protected function configure() {
    $this->setName('run-script')
      ->setDescription('Runs project-specific script for a particular event.')
      ->addArgument(
        'script',
        InputArgument::REQUIRED,
        'Script name to run.'
      )
      ->addArgument(
        'args',
        InputArgument::IS_ARRAY | InputArgument::OPTIONAL,
        'Arguments passed to script.'
      )
      ->setHelp(<<<EOT
The <info>run-script</info> command runs scripts defined in flo.yml:

<info>flo run-script post_deploy_cmd -- args1 args2 args3</info>
EOT
      );
  }

  /**
   * {@inheritdoc}
   */
  protected function execute(InputInterface $input, OutputInterface $output) {
    $scripts = $this->getConfigParameter('scripts');
    $script_name = $input->getArgument('script');
    if (isset($scripts[$script_name])) {
      $args = $input->getArgument('args');
      $script_args = empty($args) ? '' : implode(' ', $args);
      foreach ($scripts[$script_name] as $script) {
        // TODO: This is "quick and dirty", make this more fool-proof.
        $process = new Process("sh {$script} {$script_args}");
        $process->run();
        $output->write($process->getOutput());
      }
    }
    else {
      // Given script not found in scripts array, throw exception.
      throw new \Exception("Could not find script '{$script_name}' in flo.yml.");
    }
  }

}
