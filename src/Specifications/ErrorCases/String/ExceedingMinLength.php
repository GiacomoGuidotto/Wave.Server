<?php

namespace Wave\Specifications\ErrorCases\String;

/**
 * @see ErrorCases
 */
interface ExceedingMinLength {
    const CODE = 21;
    const MESSAGE = "string exceed the minimum length";
    const DETAILS = 'the string-typed attribute exceeds the minimum permitted length';
}