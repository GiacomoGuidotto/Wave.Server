<?php

namespace Wave\Model\Contact;

use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectParsing;

/**
 * ContactInterface resource class
 * The implementation of the UserInterface interface
 */
class Contact implements ContactInterface {
  
  /**
   * @inheritDoc
   */
  public static function validateStatus(string $status): int {
    if (strlen($status) > 1) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($status) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    $enum = ['P', 'A', 'B'];
    if (!in_array($status, $enum, true)) {
      return IncorrectParsing::CODE;
    }
    
    return Success::CODE;
  }
}