<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface WrongStatus {
  const CODE = 51;
  const MESSAGE = "Wrong contact status";
  const DETAILS = "The status between this user and the targeted one can't allow this directive";
}