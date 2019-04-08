<?php


namespace Curator\ComposerSAPlugin\Command;


use Composer\Command\BaseCommand;
use Curator\ComposerSAPlugin\ArraySearch;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DumpHashesCommand extends BaseCommand {
  protected $hashedFileCounter = 0;
  protected $touchedDirectories = array();
  protected $filehandleCache = array();
  const FILE_HANDLE_CACHE_SIZE = 10;

  protected function configure()
  {
    $this->setName('dump-hashes');
    $this->setDescription('Generates per-directory hash files for validated autoloading.');
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    /** @var \Composer\Composer $composer */
    $composer = $this->getComposer();
    $package = $composer->getPackage();
    $autoloads = $package->getAutoload();

    // Collects highest-level directories from psr-[04]
    $psr_dirs = [];

    foreach ($autoloads as $type => $paths) {
      switch ($type) {
        case 'classmap':
        case 'files':
          foreach ($paths as $file) {
            $this->recordHashOfFile($file);
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
      $this->recordAllDescendantPhpFiles($dir);
    }

    $this->closeHashFiles();

    $output->writeln(sprintf("Hashes of %d project files have been written.", $this->hashedFileCounter));
  }

  protected function recordHashOfFile($file) {
    $dir = dirname($file);
    if (empty($this->touchedDirectories[$dir])) {
      $mode = 'w';
      $this->touchedDirectories[$dir] = true;
    } else {
      $mode = 'a';
    }

    if (empty($this->filehandleCache[$dir])) {
      if (count($this->filehandleCache) >= self::FILE_HANDLE_CACHE_SIZE) {
        $evictedFileHandle = array_shift($this->filehandleCache);
        fclose($evictedFileHandle);
      }

      $hashFile = $dir . DIRECTORY_SEPARATOR . 'hashes.sha256';
      $hashFileHandle = fopen($hashFile, $mode);

      $this->filehandleCache[] = $hashFileHandle;
    } else {
      $hashFileHandle = $this->filehandleCache[$dir];
    }

    fprintf($hashFileHandle, "%s  %s\n", hash_file('sha256', $file), basename($file));
    $this->hashedFileCounter++;
  }

  protected function recordAllDescendantPhpFiles($dir) {
    $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::CURRENT_AS_PATHNAME));
    foreach ($iterator as $path) {
      if (substr($path, -4) == '.php' && strlen($path) > 4) {
        $this->recordHashOfFile($path);
      }
    }
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

  protected function closeHashFiles() {
    foreach ($this->filehandleCache as $handle) {
      fclose($handle);
    }
  }
}