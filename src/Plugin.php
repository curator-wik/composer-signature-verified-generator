<?php

namespace Curator\ComposerSAPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;

class Plugin implements PluginInterface, EventSubscriberInterface {

  /** @var Composer $composer */
  protected $composer;
  /** @var IOInterface $io */
  protected $io;

  public function activate(Composer $composer, IOInterface $io) {
    $this->composer = $composer;
    $this->io = $io;
  }

  public static function getSubscribedEvents() {
    return array(
      'post-autoload-dump' => 'injectValidatingAutoloader'
    );
  }

  public function injectValidatingAutoloader(Event $event) {
    $this->io->write('Intercepted post-autoload-dump event');
  }
}