<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\Wamp\WampServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Wave\Services\WebSocket\WebSocketService;
use Wave\Services\ZeroMQ\ZeroMQModule;
use Wave\Specifications\Wave\Wave;

// Dependencies
$loop = Loop::get();
$zeroMQ = ZeroMQModule::getInstance($loop);
$channel = WebSocketService::getInstance();

// ZeroMQ binding
$zeroMQ->bindCallback([$channel, 'onAPIRequest']);

//WebSocket server initialization
new IoServer(
  new HttpServer(
    new WsServer(
      new WampServer(
        $channel
      )
    )
  ),
  new SocketServer(Wave::CHANNEL_URI)
);