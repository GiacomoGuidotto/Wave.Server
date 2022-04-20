<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface WrongDirective {
  const CODE = 53;
  const MESSAGE = "Wrong directive";
  const DETAILS = "The given directive isn't one of the predefined";
}