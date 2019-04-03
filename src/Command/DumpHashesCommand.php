<?php


namespace Curator\ComposerSAPlugin\Command;


use Composer\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpHashesCommand extends BaseCommand {
  protected function configure()
  {
    $this->setName('dump-hashes');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $output->writeln('Executing');
  }
}