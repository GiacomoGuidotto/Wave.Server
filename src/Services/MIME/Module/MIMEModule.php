<?php

namespace Wave\Services\MIME\Module;

use Wave\Model\Singleton\Singleton;
use Wave\Specifications\ErrorCases\Mime\IncorrectFileType;
use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\MIME\MIME;

/**
 * Database module
 *
 * Access point to the filesystem
 */
class MIMEModule extends Singleton {
  
  /**
   * Validate, prepare, decode the media's data for its insertion into the filesystem
   *
   * @param string $filepath The desired filepath
   * @param string $media    The media's data
   * @return int|string      Its final filepath
   */
  public static function saveMedia(string $filepath, string $media): int|string {
    
    // ==== Validate image =======================
    if (preg_match('/^data:image\/(\w+);base64,/', $media, $type) !== false) {
      $type = strtolower($type[1]);
      if (!in_array($type, MIME::SUPPORTED_MIME)) {
        return IncorrectFileType::CODE;
      }
    } else {
      return IncorrectPayload::CODE;
    }
    
    // ==== Prepare image ========================
    $data = substr($media, strpos($media, ',') + 1);
    $data = str_replace(' ', '+', $data);
    
    $data = base64_decode($data);
    
    if ($data === false) {
      return IncorrectPayload::CODE;
    }
    
    
    // ==== Insert image =========================
    $filepath .= ".$type";
    
    file_put_contents($filepath, $data, LOCK_EX);
    
    return $filepath;
  }
}