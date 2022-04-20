<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface Unauthorized {
  const CODE = 40;
  const MESSAGE = "The session token does not exist";
  const DETAILS = "The session token served doesn't exist, impossible to confirm authority";
}
