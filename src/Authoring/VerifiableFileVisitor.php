<?php


namespace Curator\ComposerSAPlugin\Authoring;

use Composer\Composer;
use Curator\ComposerSAPlugin\ArraySearch;

/**
 * Class VerifiableFileVisitor
 *
 * Visits each file of the root project that could potentially be autoloaded.
 */
class VerifiableFileVisitor {
  protected $visitedFileCounter = 0;
  protected $visitedDirectories = array();
  protected $composer;

  public function __construct(Composer $composer) {
    $this->composer = $composer;
  }

  public function getVisitedDirectories() {
    return $this->visitedDirectories;
  }

  public function getVisitedFileCounter() {
    return $this->visitedFileCounter;
  }

  public function beginVisiting() {
    $package = $this->composer->getPackage();
    $autoloads = $package->getAutoload();

    // Collects highest-level directories from psr-[04]
    $psr_dirs = [];

    foreach ($autoloads as $type => $paths) {
      switch ($type) {
        case 'classmap':
        case 'files':
          foreach ($paths as $file) {
            $this->visitFile($file);
          }
          break;
        case 'psr-4':
          foreach ($paths as $path) {
            $path = realpath($path);
            $this->collectPsrDirectory($path, $psr_dirs);
          }
          break;
        case 'psr-0':
          // more of a cluster
          throw new \RuntimeException('Not implemented');
      }
    }

    foreach ($psr_dirs as $dir) {
      $this->visitAllDescendantPhpFiles($dir);
    }

  }

  protected function visitFile($file) {
    $dir = dirname($file);
    $this->visitedDirectories[$dir] = TRUE;
    $this->visitedFileCounter++;
  }

  protected function collectPsrDirectory($directory, &$psr_dirs) {
    if (empty($directory)) {
      return;
    }

    if (substr($directory, -1) != DIRECTORY_SEPARATOR) {
      $directory .= DIRECTORY_SEPARATOR;
    }

    $parentOrSelfIndex = ArraySearch::binarySearch($directory, $psr_dirs, 'strcmp', count($psr_dirs));
    if ($parentOrSelfIndex < count($psr_dirs)) {
      $parentOrSelfCandidate = $psr_dirs[$parentOrSelfIndex];
      if (strncmp($parentOrSelfCandidate, $directory, strlen($parentOrSelfCandidate)) === 0) {
        return;
      }
    }

    // purge subsequent entries that are descendants of $directory
    $purgeLength = 0;
    $purgeTestIndex = $parentOrSelfIndex;
    while (
      $purgeTestIndex < count($psr_dirs)
      && strncmp($directory, $psr_dirs[$purgeTestIndex], strlen($directory)) === 0) {
      $purgeLength++;
      $purgeTestIndex++;
    }

    array_splice($psr_dirs, $parentOrSelfIndex, $purgeLength, $directory);
  }

  protected function visitAllDescendantPhpFiles($dir) {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::CURRENT_AS_PATHNAME));
    foreach ($iterator as $path) {
      if (substr($path, -4) == '.php' && strlen($path) > 4) {
        $this->visitFile($path);
      }
    }
  }
}
