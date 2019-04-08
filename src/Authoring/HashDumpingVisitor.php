<?php


namespace Curator\ComposerSAPlugin\Authoring;


class HashDumpingVisitor extends VerifiableFileVisitor {
  protected $filehandleCache = array();
  const FILE_HANDLE_CACHE_SIZE = 10;

  public function beginVisiting() {
    parent::beginVisiting();

    $this->closeHashFiles();
  }

  protected function visitFile($file) {
    $dir = dirname($file);
    if (empty($this->visitedDirectories[$dir])) {
      $mode = 'w';
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
    parent::visitFile($file);
  }

  protected function closeHashFiles() {
    foreach ($this->filehandleCache as $handle) {
      fclose($handle);
    }
  }
}