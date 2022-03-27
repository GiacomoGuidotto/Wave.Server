<?php

namespace Wave\Services\MIME;

use Wave\Model\Singleton\Singleton;
use Wave\Model\User\User;
use Wave\Specifications\ErrorCases\Mime\IncorrectFileType;
use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\MIME\MIME;

/**
 * MIME service class
 *
 * The implementation of the MIMEServiceInterface interface
 */
class MIMEService extends Singleton implements MIMEServiceInterface {
  
  /**
   * @inheritDoc
   */
  public static function createMedia(
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
   * @inheritDoc
   */
  public static function researchMedia(
    string $filepath,
  ): int|string {
    // ==== Validate filepath ====================
    $filepathValidation = User::validatePicture($filepath);
    
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
   * @inheritDoc
   */
  public static function updateMedia(
    string $filepath,
    string $media,
  ): int|string {
    return self::createMedia($filepath, $media);
  }
  
  /**
   * @inheritDoc
   */
  public static function deleteMedia(
    string $filepath,
  ): ?int {
    // ==== Validate filepath ====================
    $filepathValidation = User::validatePicture($filepath);
    
    if ($filepathValidation != Success::CODE) {
      return $filepathValidation;
    }
    
    // ==== Delete media =========================
    unlink($filepath);
    return null;
  }
}