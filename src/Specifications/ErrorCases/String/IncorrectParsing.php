<?php

namespace Wave\Specifications\ErrorCases\String;

/**
 * @see ErrorCases
 */
interface IncorrectParsing {
    const CODE = 22;
    const MESSAGE = "string isn't one of the predefined";
    const DETAILS = "the string-typed attribute doesn't correspond to predefined schema";
}