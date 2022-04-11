<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

class SelfRequest {
  const CODE = 50;
  const MESSAGE = "Self request";
  const DETAILS = 'The target of the contact request is the origin of said request';
}