<?php


namespace Curator\ComposerSAPlugin\Command;


use Composer\Command\BaseCommand;
use Curator\ComposerSAPlugin\Authoring\HashSigningVisitor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SignHashesCommand extends BaseCommand {

  protected function configure()
  {
    $this->setName('sign-hashes')
    ->setDescription('Signs dumped hashes with a private key.')
    ->setDefinition(array(
      new InputOption(
        'private-key',
        null,
        InputOption::VALUE_REQUIRED,
        'Path to private key file you wish to sign with.'
      ),
      new InputOption(
        'signing-authority',
        null,
        InputOption::VALUE_REQUIRED,
        'Unique, well-known code identifying the signing authority.'
      ),
    ));
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    $signingVisitor = new HashSigningVisitor(
      $this->getComposer(),
      $input->getOption('private-key'),
      $input->getOption('signing-authority')
    );

    $signingVisitor->beginVisiting();

    $output->writeln(sprintf("Signed %d hash files\n", count($signingVisitor->getVisitedDirectories())));
  }
}