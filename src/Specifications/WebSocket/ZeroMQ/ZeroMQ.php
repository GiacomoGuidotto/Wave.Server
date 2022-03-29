<?php

namespace Wave\Specifications\WebSocket\ZeroMQ;

use Wave\Specifications\Wave\Wave;

interface ZeroMQ {
  const NETWORK = 'tcp://127.0.0.1';
  // Data Source Name
  const DSN = self::NETWORK . ":" . Wave::ZEROMQ_PORT;
}