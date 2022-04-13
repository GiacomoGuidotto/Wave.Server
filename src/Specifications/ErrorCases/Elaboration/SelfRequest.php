<?php

namespace Wave\Specifications\ErrorCases\Elaboration;

interface SelfRequest {
  const CODE = 50;
  const MESSAGE = "Self request";
  const DETAILS = 'The target of the contact request is the origin of said request';
}