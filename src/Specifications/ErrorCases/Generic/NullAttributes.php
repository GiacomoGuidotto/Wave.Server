<?php

namespace Wave\Specifications\ErrorCases\Generic;

/**
 * @see ErrorCases
 */
interface NullAttributes {
    const CODE = 10;
    const MESSAGE = "attribute can't be null";
    const DETAILS = 'the attribute does not exist or is null';
}