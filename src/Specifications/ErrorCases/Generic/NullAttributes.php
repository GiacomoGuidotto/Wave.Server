<?php

namespace Wave\Specifications\ErrorCases\Generic;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface NullAttributes {
  const CODE = 10;
  const MESSAGE = "Attribute can't be null";
  const DETAILS = 'The attribute does not exist or is null';
}