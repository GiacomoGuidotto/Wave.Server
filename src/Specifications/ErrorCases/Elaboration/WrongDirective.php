<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface WrongDirective {
  const CODE = 52;
  const MESSAGE = "Wrong directive";
  const DETAILS = "The given directive isn't one of the predefined";
}