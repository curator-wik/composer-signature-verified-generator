<?php


namespace Curator\ComposerSAPlugin;


use Curator\ComposerSAPlugin\Command\DumpHashesCommand;

class CommandProvider implements \Composer\Plugin\Capability\CommandProvider
{
  public function getCommands()
  {
    return array(new DumpHashesCommand(null));
  }
}