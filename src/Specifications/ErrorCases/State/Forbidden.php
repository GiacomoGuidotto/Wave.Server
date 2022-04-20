<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface Forbidden {
  const CODE = 42;
  const MESSAGE = "The entity or action is forbidden";
  const DETAILS = "The searched entity doesn't belong to the user or the specified directive isn't allow to the user";
}