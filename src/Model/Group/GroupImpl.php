<?php

namespace Wave\Model\Group;

use Wave\Specifications\ErrorCases\String\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\String\ExceedingMinLength;
use Wave\Specifications\ErrorCases\String\IncorrectParsing;
use Wave\Specifications\ErrorCases\String\IncorrectPattern;
use Wave\Specifications\ErrorCases\Success\Success;

/**
 * Group resource class
 * The implementation of the Group interface
 */
class GroupImpl implements Group {

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
    // TODO base64 encoded image/png or image/jpg validation

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
            "#^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$#",
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