<?php

namespace Curator\ComposerSAPlugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Util\Filesystem;

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
    $this->io->write('Updating autoloader to perform verification...');
    $config = $this->composer->getConfig();
    $filesystem = new Filesystem();
    $vendorPath = $filesystem->normalizePath(realpath(realpath($config->get('vendor-dir'))));

    $class_loader_file = $vendorPath . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . 'ClassLoader.php';
    $class_loader_fh = fopen($class_loader_file, 'r+');
    $class_loader_base_code = fread($class_loader_fh, 4 * 1024);
    fseek($class_loader_fh, 0);
    $class_loader_base_code = str_replace("\nclass ClassLoader\n", "\nclass LoaderBase \n", $class_loader_base_code);
    fwrite($class_loader_fh, $class_loader_base_code);

    $new_loader_code = file_get_contents(dirname(__FILE__) . '/ClassLoader.php');
    $new_loader_code = substr($new_loader_code, strpos($new_loader_code, "\nclass ClassLoader extends LoaderBase"));

    fseek($class_loader_fh, 0, SEEK_END);
    fwrite($class_loader_fh, $new_loader_code);
    fclose($class_loader_fh);
  }

  /**
   * Copy file using stream_copy_to_stream to work around https://bugs.php.net/bug.php?id=6463
   *
   * @param string $source
   * @param string $target
   */
  protected function safeCopy($source, $target)
  {
    $source = fopen($source, 'r');
    $target = fopen($target, 'w+');

    stream_copy_to_stream($source, $target);
    fclose($source);
    fclose($target);
  }
}