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
  
  const ZEROMQ_DSN = Wave::ZEROMQ_NETWORK . ":" . Wave::ZEROMQ_PORT;
  private const ZEROMQ_NETWORK = 'tcp://127.0.0.1';
  private const ZEROMQ_PORT = '5555';
  
  const CHANNEL_URI = Wave::CHANNEL_NETWORK . ":" . Wave::CHANNEL_PORT;
  private const CHANNEL_NETWORK = '127.0.0.1';
  private const CHANNEL_PORT = '8000';
}