<?php

namespace Wave\Specifications\Logging;

/**
 * Logging specifications.
 *
 * Set of global constant defining logging-related details.
 *
 * @author Giacomo Guidotto
 */
class Logging {
  const LOG_DATE_FORMAT = "d/m/Y:H:i:s T";
  const LOG_MESSAGE_FORMAT =
    "[" . self::LOG_MESSAGE_DATETIME . "] " .
    self::LOG_MESSAGE_LEVEL . " | " .
    self::LOG_MESSAGE_CHANNEL . ": " . self::LOG_MESSAGE_MESSAGE . " | " .
    self::LOG_MESSAGE_CONTEXT . "\n";
  
  // Log message modules
  private const LOG_MESSAGE_DATETIME = "%datetime%";
  private const LOG_MESSAGE_LEVEL = "%level_name%";
  private const LOG_MESSAGE_CHANNEL = "%channel%";
  private const LOG_MESSAGE_MESSAGE = "%message%";
  private const LOG_MESSAGE_CONTEXT = "%context%";
  private const LOG_MESSAGE_EXTRA = "%extra%";
}