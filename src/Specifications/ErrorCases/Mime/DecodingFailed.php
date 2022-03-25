<?php

namespace Wave\Specifications\ErrorCases\Mime;

/**
 * @see ErrorCases
 */
interface DecodingFailed {
  const CODE = 32;
  const MESSAGE = "The data decoding failed";
  const DETAILS = "The data isn't correct, the decoding failed";
}