<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface BlockedByUser {
  const CODE = 51;
  const MESSAGE = "Blocked by user";
  const DETAILS = 'The elaboration failed because the targeted user blocked requests from you';
}