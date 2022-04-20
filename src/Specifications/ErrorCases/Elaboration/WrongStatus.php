<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface WrongStatus {
  const CODE = 52;
  const MESSAGE = "Wrong contact status";
  const DETAILS = "The status between this user and the targeted one can't allow this directive";
}