<?php

namespace Wave\Specifications\ErrorCases\String;

/**
 * @see ErrorCases
 */
interface ExceedingMaxLength {
  const CODE = 20;
  const MESSAGE = "String exceed the maximum length";
  const DETAILS = 'The string-typed attribute exceeds the maximum permitted length';
}