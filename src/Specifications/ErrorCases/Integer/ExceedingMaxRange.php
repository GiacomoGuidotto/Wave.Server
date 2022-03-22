<?php

namespace Wave\Specifications\ErrorCases\Integer;

/**
 * @see ErrorCases
 */
interface ExceedingMaxRange {
  const CODE = 30;
  const MESSAGE = "Integer exceed the maximum value";
  const DETAILS = 'The int-typed attribute exceeds the maximum permitted value';
}