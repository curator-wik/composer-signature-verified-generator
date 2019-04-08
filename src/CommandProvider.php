<?php


namespace Curator\ComposerSAPlugin;


use Curator\ComposerSAPlugin\Command\DumpHashesCommand;
use Curator\ComposerSAPlugin\Command\SignHashesCommand;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
  public function getCommands()
  {
    return array(
      new DumpHashesCommand(null),
      new SignHashesCommand(null)
    );
  }
}