<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface Timeout {
  const CODE = 41;
  const MESSAGE = "The session has expired";
  const DETAILS = "The time to live of the session token ended";
}