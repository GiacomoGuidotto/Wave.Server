<?php

namespace Wave\Specifications\ErrorCases\Type;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface ExceedingMinimum {
  const CODE = 25;
  const MESSAGE = "Integer exceed the minimum value";
  const DETAILS = 'The int-typed attribute exceeds the minimum permitted value';
}