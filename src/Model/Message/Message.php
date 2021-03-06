<?php

namespace Wave\Model\Message;

use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectParsing;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;

/**
 * Message resource class
 * The implementation of the Message interface
 *
 * @author Giacomo Guidotto
 */
class Message implements MessageInterface {
  
  /**
   * @inheritDoc
   */
  public static function validateKey(string $key): int {
    if (strlen($key) > 36) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($key) < 36) {
      return ExceedingMinLength::CODE;
    }
    if (preg_match(
        "#^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$#",
        $key
      ) != 1) {
      return IncorrectPattern::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateTimestamp(string $timestamp): int {
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
  public static function validateContent(string $content): int {
    if (strlen($content) > 1) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($content) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    $enum = ['M', 'I'];
    if (!in_array($content, $enum, true)) {
      return IncorrectParsing::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateText(string $text): int {
    if (strlen($text) > 64) {
      return ExceedingMaxLength::CODE;
    }
    if (strlen($text) < 1) {
      return ExceedingMinLength::CODE;
    }
    
    return Success::CODE;
  }
  
  /**
   * @inheritDoc
   */
  public static function validateMedia(string $media): int {
    if (!file_exists($media)) {
      return IncorrectPayload::CODE;
    }
    
    return Success::CODE;
  }
}