<?php

namespace Wave\Specifications\Wave;

/**
 * Wave specifications.
 *
 * Set of global constant defining general details.
 */
class Wave {
  const SESSION_DURATION = '15 minutes';
  const SUPPORTED_LANGUAGE = ['IT', 'EN'];
  const ZEROMQ_PORT = '5555';
  const CHANNEL_PORT = '8000';
  
  // Logging properties
  const LOG_DATE_FORMAT = "d/m/Y:H:i:s T";
  const LOG_MESSAGE_FORMAT =
    "[" . self::LOG_MESSAGE_DATETIME . "] " .
    self::LOG_MESSAGE_LEVEL . " | " .
    self::LOG_MESSAGE_CHANNEL . ": " .
    self::LOG_MESSAGE_MESSAGE . "\n";
  
  private const LOG_MESSAGE_DATETIME = "%datetime%";
  private const LOG_MESSAGE_LEVEL = "%level_name%";
  private const LOG_MESSAGE_CHANNEL = "%channel%";
  private const LOG_MESSAGE_MESSAGE = "%message%";
  private const LOG_MESSAGE_CONTEXT = "%context%";
  private const LOG_MESSAGE_EXTRA = "%extra%";
}