<?php

class BookItCacheException extends Exception {}

/**
 * Cache functionality for the BookIt code, very lightweight.
 *
 * @author Tom Rix
 * @version 1.0
 *
 */
class BookitCache extends Extension {
  /**
   * Directory where things are cached
   *
   * @var string
   */
  private $cacheDir;

  const CLEAR_ALL = 0;
  const CLEAR_SPECIFIED = 1;

  /**
   * Retrieve data from cache
   *
   * @param string $name name of data
   * @return mixed false if data not found / expired, data otherwise
   */
  public function fetch($name) {
    if (file_exists($this->cacheDir . DIRECTORY_SEPARATOR . $name) && file_exists($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info')) {
      $info = array();
      parse_str(file_get_contents($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info'), $info);
      if ($info['expire'] > time()) {
        return unserialize(file_get_contents($this->cacheDir . DIRECTORY_SEPARATOR . $name));
      } else {
        unlink($this->cacheDir . DIRECTORY_SEPARATOR . $name);
        unlink($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info');
      }
    }
    return false;
  }

  /**
   * Save data to the cache
   *
   * @param string $name name of the data
   * @param mixed $data the data itself
   * @param int $age how many seconds to store the data for
   * @return bool
   */
  public function save($name, $data, $age) {
    file_put_contents($this->cacheDir . DIRECTORY_SEPARATOR . $name, serialize($data));
    file_put_contents($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info', 'expire=' . (time() + $age));
    if (file_exists($this->cacheDir . DIRECTORY_SEPARATOR . $name) && file_exists($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info')) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Clear the cache. You can clear the whole thing, or just a specific file it is up to you.
   *
   * @param int $mode (refer BookItCache::CLEAR_* for modes)
   * @param string $name if clearing a specific cache item, name it here
   * @return int the number of files deleted
   */
  public function clear($mode, $name = '') {
    switch($mode) {
      case self::CLEAR_ALL:
        $filesToDelete = glob($this->cacheDir . DIRECTORY_SEPARATOR . '*');
      break;
      case self::CLEAR_SPECIFIED:
        if ($name == '') {
          throw new BookItCacheException('When using BookItCache::CLEAR_SPECIFIED, $name must be specified');
        }
      $filesToDelete = array();
      if (file_exists($this->cacheDir . DIRECTORY_SEPARATOR . $name)) {
        $filesToDelete[] = realpath($this->cacheDir . DIRECTORY_SEPARATOR . $name);
      }
      if (file_exists($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info')) {
        $filesToDelete[] = realpath($this->cacheDir . DIRECTORY_SEPARATOR . $name . '-info');
      }
      break;
      default:
        throw new BookItCacheException('unknown cache clear mode');
    }
    foreach($filesToDelete as $file) {
      unlink($file);
    }
    return count($filesToDelete/2);
  }

  /**
   * Set the cache directory
   *
   * @param string $cacheDir
   */
  public function setCacheDir($cacheDir) {
    if ($_SERVER['SERVER_SOFTWARE'] == 'Apache/2.2.11 (Win32) PHP/5.2.8') {
      $cacheDir = 'c:/tmp';
    }
    if (!is_dir($cacheDir)) {
      // Attempt to make it
      if (!mkdir($cacheDir)) {
        throw new BookItCacheException('cacheDir must be a directory');
      }
    }
    $this->cacheDir = $cacheDir;
  }

  /**
   * Get the cache directory
   *
   * @param string $cacheDir
   * @return string
   */
  public function getCacheDir($cacheDir) {
    return $this->cacheDir;
  }
}