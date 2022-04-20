<?php

namespace Wave\Model\User;

use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectParsing;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;
use Wave\Specifications\Wave\Wave;

/**
 * User resource class
 * The implementation of the User interface
 *
 * @author Giacomo Guidotto
 */
class User implements UserInterface {
  
  /**
   * @inheritDoc
   */
  public static function validateUsername(string $username): int {
    if (strlen($username) > 32) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($username) < 5) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match("#^([a-z0-9_]){5,32}$#", $username) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validatePassword(string $password): int {
    if (strlen($password) > 32) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($password) < 5) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8,16}$#",
        $password
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
  public static function validateSurname(string $surname): int {
    if (strlen($surname) > 64) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($surname) < 1) {
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
  public static function validatePhone(string $phone): int {
    if (strlen($phone) > 19) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($phone) < 5) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^([0-9]){5,19}$#",
        $phone
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateTheme(string $theme): int {
    if (strlen($theme) > 1) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($theme) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    $enum = ['L', 'D'];
    if (!in_array($theme, $enum, true)) {
      return IncorrectParsing::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateLanguage(string $language): int {
    if (strlen($language) > 2) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($language) < 2) {
      return ExceedingMinLength::CODE;
    }
    if (!in_array($language, Wave::SUPPORTED_LANGUAGE, true)) {
      return IncorrectParsing::CODE;
    }
    
    return Success::CODE;
  }
}