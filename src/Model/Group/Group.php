<?php

namespace Wave\Model\Group;

use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectParsing;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;

/**
 * GroupInterface resource class
 * The implementation of the GroupInterface interface
 */
class Group implements GroupInterface {
  
  /**
   * @inheritDoc
   */
  public static function validateGroup(string $group): int {
    if (strlen($group) > 36) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($group) < 36) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$#",
        $group
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateName(string $name): int {
    if (strlen($name) > 64) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($name) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateInfo(string $info): int {
    if (strlen($info) > 225) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($info) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validatePicture(string $picture): int {
    if (!file_exists($picture)) {
      return IncorrectPayload::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateChat(string $chat): int {
    if (strlen($chat) > 36) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($chat) < 36) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$#",
        $chat
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateState(string $state): int {
    if (strlen($state) > 1) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($state) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    $enum = ['N', 'A', 'P'];
    if (!in_array($state, $enum, true)) {
      return IncorrectParsing::CODE;
    }
    
    return Success::CODE;
  }
}