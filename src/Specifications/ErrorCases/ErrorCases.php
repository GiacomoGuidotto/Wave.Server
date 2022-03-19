<?php

namespace Wave\Specifications\ErrorCases;

use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\Integer\ExceedingMaxRange;
use Wave\Specifications\ErrorCases\Integer\ExceedingMinRange;
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
 */
interface ErrorCases {
    const CODES_ASSOCIATIONS = [
        Success::CODE => 200,
        NullAttributes::CODE => 400,
        ExceedingMaxLength::CODE => 400,
        ExceedingMinLength::CODE => 400,
        IncorrectParsing::CODE => 400,
        IncorrectPattern::CODE => 400,
        ExceedingMaxRange::CODE => 400,
        ExceedingMinRange::CODE => 400,
    ];

    const ERROR_MESSAGES = [
        Success::CODE => Success::MESSAGE,
        NullAttributes::CODE => NullAttributes::MESSAGE,
        ExceedingMaxLength::CODE => ExceedingMaxLength::MESSAGE,
        ExceedingMinLength::CODE => ExceedingMinLength::MESSAGE,
        IncorrectParsing::CODE => IncorrectParsing::MESSAGE,
        IncorrectPattern::CODE => IncorrectPattern::MESSAGE,
        ExceedingMaxRange::CODE => ExceedingMaxRange::MESSAGE,
        ExceedingMinRange::CODE => ExceedingMinRange::MESSAGE,
    ];

    const ERROR_DETAILS = [
        Success::CODE => Success::DETAILS,
        NullAttributes::CODE => NullAttributes::DETAILS,
        ExceedingMaxLength::CODE => ExceedingMaxLength::DETAILS,
        ExceedingMinLength::CODE => ExceedingMinLength::DETAILS,
        IncorrectParsing::CODE => IncorrectParsing::DETAILS,
        IncorrectPattern::CODE => IncorrectPattern::DETAILS,
        ExceedingMaxRange::CODE => ExceedingMaxRange::DETAILS,
        ExceedingMinRange::CODE => ExceedingMinRange::DETAILS,
    ];
}