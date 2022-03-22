<?php

namespace Wave\Specifications\ErrorCases;

use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\Integer\ExceedingMaxRange;
use Wave\Specifications\ErrorCases\Integer\ExceedingMinRange;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\Forbidden;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\State\Timeout;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\String\ExceedingMaxLength;
use Wave\Specifications\ErrorCases\String\ExceedingMinLength;
use Wave\Specifications\ErrorCases\String\IncorrectParsing;
use Wave\Specifications\ErrorCases\String\IncorrectPattern;
use Wave\Specifications\ErrorCases\Success\Success;

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
 * ==== string-related =========
 * 20 Exceeding max length
 * 21 Exceeding min length
 * 22 Incorrect parsing
 * 23 Incorrect pattern
 *
 * ==== int-related ============
 * 30 Exceeding max range
 * 31 Exceeding min range
 *
 * ==== state-related ==========
 * 40 Unauthorized
 * 41 Timeout
 * 42 Forbidden
 * 43 Not found
 * 44 Already exist
 */
interface ErrorCases {
  const CODES_ASSOCIATIONS = [
    Success::CODE            => 200,
    NullAttributes::CODE     => 400,
    ExceedingMaxLength::CODE => 400,
    ExceedingMinLength::CODE => 400,
    IncorrectParsing::CODE   => 400,
    IncorrectPattern::CODE   => 400,
    ExceedingMaxRange::CODE  => 400,
    ExceedingMinRange::CODE  => 400,
    Unauthorized::CODE       => 401,
    Timeout::CODE            => 401,
    Forbidden::CODE          => 403,
    NotFound::CODE           => 404,
    AlreadyExist::CODE       => 409,
  ];
  
  const ERROR_MESSAGES = [
    Success::CODE            => Success::MESSAGE,
    NullAttributes::CODE     => NullAttributes::MESSAGE,
    ExceedingMaxLength::CODE => ExceedingMaxLength::MESSAGE,
    ExceedingMinLength::CODE => ExceedingMinLength::MESSAGE,
    IncorrectParsing::CODE   => IncorrectParsing::MESSAGE,
    IncorrectPattern::CODE   => IncorrectPattern::MESSAGE,
    ExceedingMaxRange::CODE  => ExceedingMaxRange::MESSAGE,
    ExceedingMinRange::CODE  => ExceedingMinRange::MESSAGE,
    Unauthorized::CODE       => Unauthorized::MESSAGE,
    Timeout::CODE            => Timeout::MESSAGE,
    Forbidden::CODE          => Forbidden::MESSAGE,
    NotFound::CODE           => NotFound::MESSAGE,
    AlreadyExist::CODE       => AlreadyExist::MESSAGE,
  ];
  
  const ERROR_DETAILS = [
    Success::CODE            => Success::DETAILS,
    NullAttributes::CODE     => NullAttributes::DETAILS,
    ExceedingMaxLength::CODE => ExceedingMaxLength::DETAILS,
    ExceedingMinLength::CODE => ExceedingMinLength::DETAILS,
    IncorrectParsing::CODE   => IncorrectParsing::DETAILS,
    IncorrectPattern::CODE   => IncorrectPattern::DETAILS,
    ExceedingMaxRange::CODE  => ExceedingMaxRange::DETAILS,
    ExceedingMinRange::CODE  => ExceedingMinRange::DETAILS,
    Unauthorized::CODE       => Unauthorized::DETAILS,
    Timeout::CODE            => Timeout::DETAILS,
    Forbidden::CODE          => Forbidden::DETAILS,
    NotFound::CODE           => NotFound::DETAILS,
    AlreadyExist::CODE       => AlreadyExist::DETAILS,
  ];
}