<?php

namespace Wave\Specifications\ErrorCases\Integer;

/**
 * @see ErrorCases
 */
interface ExceedingMaxRange {
  const CODE = 30;
  const MESSAGE = "integer exceed the maximum value";
  const DETAILS = 'the int-typed attribute exceeds the maximum permitted value';
}