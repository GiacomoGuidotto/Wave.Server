<?php

namespace Wave\Specifications\ErrorCases\Mime;

/**
 * @see    ErrorCases
 *
 * @author Giacomo Guidotto
 */
interface IncorrectFileType {
  const CODE = 31;
  const MESSAGE = "MIME type is invalid";
  const DETAILS = "The file's extension, or MIME type, isn't supported";
}