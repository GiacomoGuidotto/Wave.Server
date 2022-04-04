<?php

namespace Wave\Specifications\ErrorCases;

use Wave\Specifications\ErrorCases\Elaboration\BlockedByUser;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\Mime\DecodingFailed;
use Wave\Specifications\ErrorCases\Mime\IncorrectFileType;
use Wave\Specifications\ErrorCases\Mime\IncorrectPayload;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\Forbidden;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\State\Timeout;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaximum;
use Wave\Specifications\ErrorCases\Type\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\Type\ExceedingMinimum;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectParsing;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;

/**
 * Error cases' set
 *
 * schema:
 * {
 *  code,
 *  message,
 *  details
 * }
 *
 * ==== success case ===========
 * 00 Success
 *
 * ==== generic ================
 * 10 Null attributes
 *
 * ==== type-related =========
 * 20 Exceeding max length
 * 21 Exceeding min length
 * 22 Incorrect parsing
 * 23 Incorrect pattern
 * 24 Exceeding maximum
 * 25 Exceeding minimum
 *
 * ==== mime-related ===========
 * 30 Incorrect payload
 * 31 Incorrect file type
 * 32 Decoding failed
 *
 * ==== state-related ==========
 * 40 Unauthorized
 * 41 Timeout
 * 42 Forbidden
 * 43 Not found
 * 44 Already exist
 *
 * ==== elaboration-related ====
 * 50 Blocked by user
 */
interface ErrorCases {
  const CODES_ASSOCIATIONS = [
    Success::CODE            => 200,
    NullAttributes::CODE     => 400,
    ExceedingMaxLength::CODE => 400,
    ExceedingMinLength::CODE => 400,
    IncorrectParsing::CODE   => 400,
    IncorrectPattern::CODE   => 400,
    ExceedingMaximum::CODE   => 400,
    ExceedingMinimum::CODE   => 400,
    IncorrectPayload::CODE   => 400,
    IncorrectFileType::CODE  => 400,
    DecodingFailed::CODE     => 400,
    Unauthorized::CODE       => 401,
    Timeout::CODE            => 401,
    Forbidden::CODE          => 403,
    NotFound::CODE           => 404,
    BlockedByUser::CODE      => 406,
    AlreadyExist::CODE       => 409,
  ];
  
  const ERROR_MESSAGES = [
    Success::CODE            => Success::MESSAGE,
    NullAttributes::CODE     => NullAttributes::MESSAGE,
    ExceedingMaxLength::CODE => ExceedingMaxLength::MESSAGE,
    ExceedingMinLength::CODE => ExceedingMinLength::MESSAGE,
    IncorrectParsing::CODE   => IncorrectParsing::MESSAGE,
    IncorrectPattern::CODE   => IncorrectPattern::MESSAGE,
    ExceedingMaximum::CODE   => ExceedingMaximum::MESSAGE,
    ExceedingMinimum::CODE   => ExceedingMinimum::MESSAGE,
    IncorrectPayload::CODE   => IncorrectPayload::MESSAGE,
    IncorrectFileType::CODE  => IncorrectFileType::MESSAGE,
    DecodingFailed::CODE     => DecodingFailed::MESSAGE,
    Unauthorized::CODE       => Unauthorized::MESSAGE,
    Timeout::CODE            => Timeout::MESSAGE,
    Forbidden::CODE          => Forbidden::MESSAGE,
    NotFound::CODE           => NotFound::MESSAGE,
    BlockedByUser::CODE      => BlockedByUser::MESSAGE,
    AlreadyExist::CODE       => AlreadyExist::MESSAGE,
  ];
  
  const ERROR_DETAILS = [
    Success::CODE            => Success::DETAILS,
    NullAttributes::CODE     => NullAttributes::DETAILS,
    ExceedingMaxLength::CODE => ExceedingMaxLength::DETAILS,
    ExceedingMinLength::CODE => ExceedingMinLength::DETAILS,
    IncorrectParsing::CODE   => IncorrectParsing::DETAILS,
    IncorrectPattern::CODE   => IncorrectPattern::DETAILS,
    ExceedingMaximum::CODE   => ExceedingMaximum::DETAILS,
    ExceedingMinimum::CODE   => ExceedingMinimum::DETAILS,
    IncorrectPayload::CODE   => IncorrectPayload::DETAILS,
    IncorrectFileType::CODE  => IncorrectFileType::DETAILS,
    DecodingFailed::CODE     => DecodingFailed::DETAILS,
    Unauthorized::CODE       => Unauthorized::DETAILS,
    Timeout::CODE            => Timeout::DETAILS,
    Forbidden::CODE          => Forbidden::DETAILS,
    NotFound::CODE           => NotFound::DETAILS,
    BlockedByUser::CODE      => BlockedByUser::DETAILS,
    AlreadyExist::CODE       => AlreadyExist::DETAILS,
  ];
}