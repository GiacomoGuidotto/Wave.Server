<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\ZeroMQ;

use Exception;
use React\EventLoop\LoopInterface;
use React\ZMQ\Context;
use React\ZMQ\SocketWrapper;
use Wave\Model\Singleton\Singleton;
use Wave\Specifications\WebSocket\ZeroMQ\ZeroMQ;
use ZMQ;
use ZMQContext;
use ZMQSocket;

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
    $this->pullSocket->bind(ZeroMQ::DSN);
    
    // PUSH socket initialization
    $context = new ZMQContext();
    $this->pushSocket = $context->getSocket(ZMQ::SOCKET_PUSH);
    $this->pushSocket->connect(ZeroMQ::DSN);
    
  }
  
  /**
   * Bind a callback to a message received at the PULL socket (sent from the PUSH socket)
   *
   * //TODO add type to ws server
   * //TODO add this in the bin/execute.php when initializing the ws server
   *
   * @param        $server //The instance of the class that contain the method
   * @param string $method The literal name of the method to bind
   * @return void
   */
  public function bindCallback($server, string $method): void {
    $this->pullSocket->on('message', [$server, $method]);
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