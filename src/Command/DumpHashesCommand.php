<?php


namespace Curator\ComposerSAPlugin\Command;


use Composer\Command\BaseCommand;
use Curator\ComposerSAPlugin\Authoring\HashDumpingVisitor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpHashesCommand extends BaseCommand {
  protected function configure()
  {
    $this->setName('dump-hashes');
    $this->setDescription('Generates per-directory hash files for validated autoloading.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var \Composer\Composer $composer */
    $composer = $this->getComposer();
    $hashDumpingVisitor = new HashDumpingVisitor($composer);

    $hashDumpingVisitor->beginVisiting();

    $output->writeln(sprintf("Hashes of %d project files have been written.", $hashDumpingVisitor->getVisitedFileCounter()));
  }
}
