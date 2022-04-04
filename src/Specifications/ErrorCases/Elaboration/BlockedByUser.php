<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface BlockedByUser {
  const CODE = 50;
  const MESSAGE = "Blocked by user";
  const DETAILS = 'The elaboration failed because the targeted user blocked requests from you';
}