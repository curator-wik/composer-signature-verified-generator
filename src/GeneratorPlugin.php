<?php

namespace Curator\ComposerSAPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;

class GeneratorPlugin implements PluginInterface, Capable {

  /** @var Composer $composer */
  protected $composer;
  /** @var IOInterface $io */
  protected $io;

  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  public function getCapabilities() {
    return array(
      'Composer\Plugin\Capability\CommandProvider' => 'Curator\ComposerSAPlugin\CommandProvider',
    );
  }
}