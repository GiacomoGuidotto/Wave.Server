<?php

namespace Wave\Specifications\ErrorCases\Type;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface ExceedingMaximum {
  const CODE = 24;
  const MESSAGE = "Integer exceed the maximum value";
  const DETAILS = 'The int-typed attribute exceeds the maximum permitted value';
}