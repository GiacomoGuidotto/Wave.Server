<?php

namespace Wave\Specifications\ErrorCases\Type;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface ExceedingMinLength {
  const CODE = 21;
  const MESSAGE = "String exceed the minimum length";
  const DETAILS = 'The string-typed attribute exceeds the minimum permitted length';
}