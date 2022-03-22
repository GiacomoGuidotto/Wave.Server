<?php

namespace Wave\Specifications\ErrorCases\Generic;

/**
 * @see ErrorCases
 */
interface NullAttributes {
  const CODE = 10;
  const MESSAGE = "Attribute can't be null";
  const DETAILS = 'The attribute does not exist or is null';
}