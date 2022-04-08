<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface DirectiveNotAllowed {
  const CODE = 53;
  const MESSAGE = "Directive not allowed";
  const DETAILS = "The given directive can't be performed for this user";
}