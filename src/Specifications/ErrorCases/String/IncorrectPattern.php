<?php

namespace Wave\Specifications\ErrorCases\String;

/**
 * @see ErrorCases
 */
interface IncorrectPattern {
    const CODE = 23;
    const MESSAGE = "string isn't following the regex pattern";
    const DETAILS = "the string-typed attribute doesn't follow the regex pattern";
}