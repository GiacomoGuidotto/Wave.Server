<?php

namespace Wave\Specifications\ErrorCases\Mime;

/**
 * @see ErrorCases
 */
interface IncorrectPayload {
  const CODE = 30;
  const MESSAGE = "Data isn't a supported MIME data";
  const DETAILS = "Data didn't match URI with supported MIME data";
}