<?php

namespace Wave\Specifications\ErrorCases\Integer;

/**
 * @see ErrorCases
 */
interface ExceedingMinRange {
  const CODE = 31;
  const MESSAGE = "integer exceed the minimum value";
  const DETAILS = 'the int-typed attribute exceeds the minimum permitted value';
}