<?php

namespace Wave\Services\MIME;

use Wave\Model\Group\GroupImpl;
use Wave\Model\Message\MessageImpl;
use Wave\Model\Singleton\Singleton;
use Wave\Model\User\UserImpl;
use Wave\Services\MIME\Module\MIMEModule;
use Wave\Specifications\ErrorCases\Success\Success;

class MIMEServiceImpl extends Singleton implements MIMEService {
  
  
  /**
   * @inheritDoc
   */
  public static function saveUserImage(
    string $image,
    string $username,
  ): string|int {
    $usernameValidation = UserImpl::validateUsername($username);
    
    if ($usernameValidation != Success::CODE) {
      return $usernameValidation;
    }
    
    // ===========================================
    $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/user/$username";
    
    return MIMEModule::saveMedia($filepath, $image);
  }
  
  /**
   * @inheritDoc
   */
  public static function saveGroupImage(
    string $image,
    string $group,
  ): string|int {
    $groupValidation = GroupImpl::validateGroup($group);
    
    if ($groupValidation != Success::CODE) {
      return $groupValidation;
    }
    
    // ===========================================
    $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/group/$group";
    
    return MIMEModule::saveMedia($filepath, $image);
  }
  
  /**
   * @inheritDoc
   */
  public static function saveMessageMedia(
    string $image,
    string $message,
  ): string|int {
    $messageValidation = MessageImpl::validateKey($message);
    
    if ($messageValidation != Success::CODE) {
      return $messageValidation;
    }
    
    // ===========================================
    $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/message/$message";
    
    return MIMEModule::saveMedia($filepath, $image);
  }
  
  /**
   * @inheritDoc
   */
  public static function retrieveImage(string $filepath): string|int {
    return MIMEModule::retrieveMedia($filepath);
  }
  
  /**
   * @inheritDoc
   */
  public static function deleteImage(string $filepath): ?int {
    return MIMEModule::deleteMedia($filepath);
  }
}