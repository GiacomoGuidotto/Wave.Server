<?php

namespace Wave\Specifications\ErrorCases\String;

/**
 * @see ErrorCases
 */
interface ExceedingMaxLength {
  const CODE = 20;
  const MESSAGE = "string exceed the maximum length";
  const DETAILS = 'the string-typed attribute exceeds the maximum permitted length';
}