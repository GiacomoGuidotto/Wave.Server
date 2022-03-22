<?php

namespace Wave\Specifications\ErrorCases\Integer;

/**
 * @see ErrorCases
 */
interface ExceedingMinRange {
  const CODE = 31;
  const MESSAGE = "Integer exceed the minimum value";
  const DETAILS = 'The int-typed attribute exceeds the minimum permitted value';
}