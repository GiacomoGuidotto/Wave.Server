<?php

namespace Wave\Specifications\ErrorCases\State;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface AlreadyExist {
  const CODE = 44;
  const MESSAGE = "The entity already exist";
  const DETAILS = 'The entity attributes already have this values';
}