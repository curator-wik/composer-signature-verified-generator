<?php


namespace Curator\ComposerSAPlugin\Authoring;


use Composer\Composer;

class HashSigningVisitor extends VerifiableFileVisitor {

  protected $keyFile;
  protected $signingAuthorityCode;

  public function __construct(\Composer\Composer $composer, $keyFile, $signingAuthorityCode) {
    parent::__construct($composer);
    $this->keyFile = $keyFile;
    $this->signingAuthorityCode = $signingAuthorityCode;
  }

  public function beginVisiting() {
    parent::beginVisiting();

    $this->beginSigning();
  }

  protected function beginSigning() {
    $key = file_get_contents($this->keyFile);
    if (strlen($key) != SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
      die('Failed to read proper key file. Run this from a directory you have run bin/generate-keys.php in.');
    }

    foreach ($this->getVisitedDirectories() as $directory => $_) {
      $hashesFile = $directory . DIRECTORY_SEPARATOR . 'hashes.sha256';
      if (! is_readable($hashesFile)) {
        throw new \RuntimeException('Expected readable file at ' . $hashesFile);
      }

      // Check that all provided hashes represent provided code.
      $hashes = file_get_contents($hashesFile);
      foreach (explode("\n", $hashes) as $hashLine) {
        if (trim($hashLine) == '') {
          continue;
        }

        list($expectedHash, $file) = explode('  ', $hashLine);
        $fileAbsolute = $directory . DIRECTORY_SEPARATOR . $file;
        $actualHash = hash_file('sha256', $fileAbsolute);
        if ($expectedHash != $actualHash) {
          throw new \UnexpectedValueException("File \"$fileAbsolute\" has incorrect hash - aborting signing");
        }
      }

      $signature = sodium_crypto_sign_detached($hashes, $key);
      $authorityCode = $this->signingAuthorityCode;
      file_put_contents("${hashesFile}.sig.${authorityCode}", $signature);
    }
  }
}
