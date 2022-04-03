<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\ZeroMQ;

use Exception;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper;
use Wave\Model\Singleton\Singleton;
use Wave\Specifications\Wave\Wave;
use ZMQ;
use ZMQContext;
use ZMQSocket;

/**
 * ZeroMQ module
 *
 * Module for the management of the MessageQueue sockets
 */
class ZeroMQModule extends Singleton {
  private ZMQSocket $pushSocket;
  private SocketWrapper $pullSocket;
  
  /**
   * Initialize the sockets, the first run need to be from the PULL perspective
   *
   * @throws Exception
   */
  protected function __construct(LoopInterface $loop = null) {
    if (is_null($loop))
      throw new Exception('PULL socket initialization failed: loop is null');
    
    // PULL socket initialization
    $context = new Context($loop);
    $this->pullSocket = $context->getSocket(ZMQ::SOCKET_PULL);
    $this->pullSocket->bind(Wave::ZEROMQ_DSN);
    
    // PUSH socket initialization
    $context = new ZMQContext();
    $this->pushSocket = $context->getSocket(ZMQ::SOCKET_PUSH);
    $this->pushSocket->connect(Wave::ZEROMQ_DSN);
    
  }
  
  /**
   * Bind a callback to a message received at the PULL socket (sent from the PUSH socket)
   *
   * @param string   $event  The type of event to bind the function to
   * @param callable $method The function to bind
   * @return void
   */
  public function bindCallback(callable $method, string $event = 'message'): void {
    $this->pullSocket->on($event, $method);
  }
  
  /**
   * Send data through the connection from the PUSH to the PULL socket
   *
   * @param array $data The JSON data as array
   * @throws Exception
   */
  public function sendData(array $data): void {
    $this->pushSocket->send(json_encode($data));
  }
}