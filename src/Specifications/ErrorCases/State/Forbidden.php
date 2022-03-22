<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see ErrorCases
 */
interface Forbidden {
  const CODE = 42;
  const MESSAGE = "The entity doesn't belong to the user";
  const DETAILS = "The searched entity isn't of this user property";
}