<?php

namespace Wave\Model\Contact;

use Wave\Specifications\ErrorCases\String\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\String\ExceedingMinLength;
use Wave\Specifications\ErrorCases\String\IncorrectParsing;
use Wave\Specifications\ErrorCases\Success\Success;

/**
 * Contact resource class
 * The implementation of the User interface
 */
class ContactImpl implements Contact {

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