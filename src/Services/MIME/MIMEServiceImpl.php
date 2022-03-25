<?php

namespace Wave\Services\MIME;

use Wave\Model\Group\GroupImpl;
use Wave\Model\Message\MessageImpl;
use Wave\Model\Singleton\Singleton;
use Wave\Model\User\UserImpl;
use Wave\Services\MIME\Module\MIMEModule;
use Wave\Specifications\ErrorCases\Mime\IncorrectFileType;
use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\MIME\MIME;

class MIMEServiceImpl extends Singleton implements MIMEService {
  
  /**
   * @inheritDoc
   */
  public static function retrieveImage(string $filepath): string|int {
    // TODO: Implement retrieveImage() method.
    return '';
  }
  
  /**
   * @inheritDoc
   */
  public static function saveUserImage(string $image, string $username): string|int {
    $usernameValidation = UserImpl::validateUsername($username);
    
    if ($usernameValidation != Success::CODE) {
      return $usernameValidation;
    }
    
    // ==== Validate image ===================
    if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
      if (!in_array(strtolower($type[1]), MIME::SUPPORTED_MIME)) {
        return IncorrectFileType::CODE;
      }
    } else {
      return IncorrectPayload::CODE;
    }
    
    // ==== Save image =======================
    $filepath = MIME::BASE_URI . `/user/$username.$type`;
    
    $result = MIMEModule::saveMedia($filepath, $image);
    
    if (!$result) {
      return IncorrectPayload::CODE;
    }
    
    return $filepath;
  }
  
  /**
   * @inheritDoc
   */
  public static function saveGroupImage(string $image, string $group): string|int {
    $groupValidation = GroupImpl::validateGroup($group);
    
    if ($groupValidation != Success::CODE) {
      return $groupValidation;
    }
    
    // ==== Validate image ===================
    if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
      if (!in_array(strtolower($type[1]), MIME::SUPPORTED_MIME)) {
        return IncorrectFileType::CODE;
      }
    } else {
      return IncorrectPayload::CODE;
    }
    
    // ==== Save image =======================
    $filepath = MIME::BASE_URI . `/group/$group.$type`;
    
    $result = MIMEModule::saveMedia($filepath, $image);
    
    if (!$result) {
      return IncorrectPayload::CODE;
    }
    
    return $filepath;
  }
  
  /**
   * @inheritDoc
   */
  public static function saveMessageMedia(string $image, string $message): string|int {
    $messageValidation = MessageImpl::validateKey($message);
    
    if ($messageValidation != Success::CODE) {
      return $messageValidation;
    }
    
    // ==== Validate image ===================
    if (preg_match('/^data:image\/(\w+);base64,/', $image, $type)) {
      if (!in_array(strtolower($type[1]), MIME::SUPPORTED_MIME)) {
        return IncorrectFileType::CODE;
      }
    } else {
      return IncorrectPayload::CODE;
    }
    
    // ==== Save image =======================
    $filepath = MIME::BASE_URI . `/message/$message.$type`;
    
    $result = MIMEModule::saveMedia($filepath, $image);
    
    if (!$result) {
      return IncorrectPayload::CODE;
    }
    
    return $filepath;
  }
}