<?php

namespace Wave\Services\MIME\Module;

use Wave\Model\Singleton\Singleton;
use Wave\Model\User\UserImpl;
use Wave\Specifications\ErrorCases\Mime\IncorrectFileType;
use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\Success\Success;
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
  public static function saveMedia(
    string $filepath,
    string $media,
  ): int|string {
    // ==== Validate media =======================
    if (preg_match('/^data:image\/(\w+);base64,/', $media, $type) !== false) {
      $type = strtolower($type[1]);
      if (!in_array($type, MIME::SUPPORTED_MIME)) {
        return IncorrectFileType::CODE;
      }
    } else {
      return IncorrectPayload::CODE;
    }
    
    // ==== Prepare media ========================
    $data = substr($media, strpos($media, ',') + 1);
    $data = str_replace(' ', '+', $data);
    
    $data = base64_decode($data);
    
    if ($data === false) {
      return IncorrectPayload::CODE;
    }
    
    // ==== Insert media =========================
    $filepath .= ".$type";
    
    file_put_contents($filepath, $data, LOCK_EX);
    
    return $filepath;
  }
  
  /**
   * Encode and prepare a media retrieved from the filesystem of a specified validated filepath
   *
   * @param string $filepath The specified filepath
   * @return int|string      The encoded media
   */
  public static function retrieveMedia(
    string $filepath,
  ): int|string {
    // ==== Validate filepath ====================
    $filepathValidation = UserImpl::validatePicture($filepath);
    
    if ($filepathValidation != Success::CODE) {
      return $filepathValidation;
    }
    
    // ==== Retrieve media =======================
    $data = base64_encode(file_get_contents($filepath));
    
    // ==== Prepare media ========================
    $type = pathinfo($filepath, PATHINFO_EXTENSION);
    $media = 'data:image/' . $type . ';base64,' . $data;
    
    // ==== Validate media =======================
    if (preg_match('/^data:image\/(\w+);base64,/', $media, $type) !== false) {
      $type = strtolower($type[1]);
      if (!in_array($type, MIME::SUPPORTED_MIME)) {
        return IncorrectFileType::CODE;
      }
    } else {
      return IncorrectPayload::CODE;
    }
    return $media;
  }
  
  /**
   * Delete a media from the filesystem of a targeted validated filepath
   *
   * @param string $filepath The targeted filepath
   * @return int|null        The eventual error code
   */
  public static function deleteMedia(
    string $filepath,
  ): ?int {
    // ==== Validate filepath ====================
    $filepathValidation = UserImpl::validatePicture($filepath);
    
    if ($filepathValidation != Success::CODE) {
      return $filepathValidation;
    }
    
    // ==== Delete media =========================
    unlink($filepath);
    return null;
  }
}