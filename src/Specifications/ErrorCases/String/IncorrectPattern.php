<?php

namespace Wave\Specifications\ErrorCases\String;

/**
 * @see ErrorCases
 */
interface IncorrectPattern {
  const CODE = 23;
  const MESSAGE = "String isn't following the regex pattern";
  const DETAILS = "The string-typed attribute doesn't follow the regex pattern";
}