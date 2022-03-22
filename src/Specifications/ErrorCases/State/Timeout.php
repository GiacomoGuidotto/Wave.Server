<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see ErrorCases
 */
interface Timeout {
  const CODE = 41;
  const MESSAGE = "The session has expired";
  const DETAILS = "The time to live of the session token ended";
}