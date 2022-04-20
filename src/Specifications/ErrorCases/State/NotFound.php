<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface NotFound {
  const CODE = 43;
  const MESSAGE = "The entity does not exist";
  const DETAILS = "The elaboration parameters didn't produced any entity";
}