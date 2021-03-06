<?php

namespace Wave\Model\Session;


use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;

/**
 * Session resource class
 * The implementation of the Session interface
 *
 * @author Giacomo Guidotto
 */
class Session implements SessionInterface {
  
  /**
   * @inheritDoc
   */
  public static function validateToken(string $token): int {
    if (strlen($token) > 36) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($token) < 36) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$#",
        $token
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateSource(string $source): int {
    if (strlen($source) > 36) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($source) < 36) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$#",
        $source
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateCreationTimestamp(string $timestamp): int {
    if (strlen($timestamp) > 19) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($timestamp) < 19) {
      return ExceedingMinLength::CODE;
    }
    // e.g. 2021-12-25 12:00:00
    if (preg_match(
        "#([0-9]{4})-(0[1-9]|1[1|2])-([0-2][0-9]|3[0|1]) ([0|1][0-9]|2[0-3])(:[0-5][0-9]){2}#",
        $timestamp
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateLastUpdated(string $timestamp): int {
    if (strlen($timestamp) > 19) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($timestamp) < 19) {
      return ExceedingMinLength::CODE;
    }
    // e.g. 2021-12-25 12:00:00
    if (preg_match(
        "#([0-9]{4})-(0[1-9]|1[1|2])-([0-2][0-9]|3[0|1]) ([0|1][0-9]|2[0-3])(:[0-5][0-9]){2}#",
        $timestamp
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
}