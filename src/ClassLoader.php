<?php
namespace Curator\ComposerSAPlugin\Autoload;

use Composer\Autoload\ClassLoader as LoaderBase;

class ClassLoader extends LoaderBase {

  /**
   * Loads the given class or interface.
   *
   * @param  string $class The name of the class
   *
   * @return bool|null True if loaded, null otherwise
   */
  public function loadClass($class) {
    $file = $this->findFile($class);
    if ($file === FALSE) {
      return NULL;
    }

    if (opcache_is_script_cached($file)) {
      includeFile($file);
      return TRUE;
    }

    if ($this->validateAndCache($file)) {
      includeFile($file);
      return TRUE;
    }

    return null;
  }

  /**
   * @param string $file
   * @param bool $retrying
   *   True on recursive 2nd call if file was modified.
   *
   * @return bool
   */
  public function validateAndCache($file, $retrying = false) {
    global $baseDir;

    $hash_file = dirname($file) . DIRECTORY_SEPARATOR . 'hashes.sha256';
    if (! is_file($hash_file) || ($hashes = file_get_contents($hash_file)) === false) {
      return false;
    }

    // Package providers could perhaps obtain signatures from several signers
    // and distribute a few .sig files, allowing usage by more users.
    $signature_file = dirname($file) . DIRECTORY_SEPARATOR . 'hashes.sha256.sig.poc';
    if (! is_file($signature_file) || ($hash_signature = file_get_contents($signature_file)) === false) {
      return false;
    }

    // $baseDir set by all autoload_*.php optimized autoload files.
    // It should always be set here, but if not, prevent reading /pubkey.
    if (empty($baseDir)) {
      return false;
    }
    $pubkey_file = $baseDir . DIRECTORY_SEPARATOR . 'pubkey.poc';
    if (! is_file($pubkey_file) || ($pubkey = file_get_contents($pubkey_file)) === false) {
      return false;
    }

    if (! sodium_crypto_sign_verify_detached($hash_signature, $hashes, $pubkey)) {
      return false;
    }

    $code_ctime = stat($file);
    if ($code_ctime === false) {
      return false;
    }
    $code_ctime = $code_ctime['ctime'];

    if (! $this->validateFile($file, $hashes, 'sha1')) {
      return false;
    }

    if (! opcache_compile_file($file)) {
      return false;
    }

    clearstatcache(true, $file);
    $new_ctime = stat($file);
    if ($new_ctime === false) {
      return false;
    }
    $new_ctime = $new_ctime['ctime'];

    if ($new_ctime !== $code_ctime) {
      opcache_invalidate($file, true);
      if (! $retrying) {
        return $this->validateAndCache($file, true);
      }
      return false;
    }

    return true;
  }

  /**
   * Validates contents of $file against sha1sum output.
   *
   * @param string $file_basename
   *   Path to an on-disk file
   * @param string $hashes
   *   Trusted file hash data for files including $file
   * @param string $hash_algo
   *   The hashing algorithm used in $hashes
   *
   * @return bool
   */
  public function validateFile($file, $hashes, $hash_algo) {
    // TODO: seeking in the $hashes data could be optimized, esp. with a better on-disk format
    $file_basename = basename($file);
    $expected_hash = '';
    $probable_offset = 0;
    while ($probable_offset !== false && $probable_offset < strlen($hashes)) {
      $probable_offset = strpos($hashes, $file_basename, $probable_offset);
      if ($probable_offset !== false) {
        $hash_start_offset = strrpos($hashes, "\n", $probable_offset - strlen($hashes) - 1);
        if ($hash_start_offset === false) {
          // First line.
          $hash_start_offset = 0;
        } else {
          $hash_start_offset++;
        }
        $line_end = strpos($hashes, "\n", $hash_start_offset);
        if ($line_end === false) {
          $line_end = strlen($hashes);
        }
        list($expected_hash, $hash_file) = explode('  ', substr($hashes, $hash_start_offset, $line_end), 2);
        if ($hash_file !== $file_basename && $hash_file !== "./$file_basename") {
          $expected_hash = '';
          $probable_offset = $line_end + 1;
          continue;
        }
      }
    }

    if ($expected_hash === '') {
      return false;
    }

    $actual_hash = hash_file($hash_algo, $file);

    return $expected_hash === $actual_hash;
  }
}