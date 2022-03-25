<?php

namespace Wave\Services\MIME\Module;

use Wave\Model\Singleton\Singleton;

/**
 * Database module
 *
 * Access point to the filesystem
 */
class MIMEModule extends Singleton {
  
  /**
   * Prepare and decode the media's data for its insertion into the filesystem
   *
   * @param string $filepath The desired filepath
   * @param string $media    The media's data
   * @return false|null      The eventual decoding fail
   */
  public static function saveMedia(string $filepath, string $media): ?bool {
    $data = substr($media, strpos($media, ',') + 1);
    $data = str_replace(' ', '+', $data);
    
    $data = base64_decode($data);
    
    if ($data === false) {
      return false;
    }
    
    file_put_contents($filepath, $data, LOCK_EX);
    return null;
  }
}