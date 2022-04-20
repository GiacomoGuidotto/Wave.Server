<?php

namespace Wave\Services\WebSocket;

use Exception;
use JetBrains\PhpStorm\Pure;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Wave\Model\Singleton\Singleton;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Log\LogModule;
use Wave\Services\WebSocket\ConnectionStorage\UsersConnectionStorage;
use Wave\Services\ZeroMQ\ZeroMQModule;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Utilities\Utilities;

/**
 * WebSocket service
 *
 * WAMP-based service for the "Channel" management
 *
 * @author Giacomo Guidotto
 */
class WebSocketModule extends Singleton implements MessageComponentInterface {
  
  protected UsersConnectionStorage $users;
  
  #[Pure] public function __construct() {
    $this->users = new UsersConnectionStorage();
  }
  
  // ==== Utility methods ===========================================================================
  
  /**
   * Utility method used in other classes to send data to this service through the ZeroMQ channel.
   * With packets of this schema:
   *
   * ---------------------------------------
   * {
   *  origin: user's username,
   *  target_s: [
   *    "..."
   *  ] OR "..."
   *  directive: CREATE | UPDATE | DELETE,
   *  topic: resource/attribute,
   *  payload: {
   *    headers: [
   *      "..."
   *    ],
   *    body: {
   *      "..."
   *    }
   *  }
   * }
   * ---------------------------------------
   *
   * @param string       $directive      The directive of the packet, contained in the first row
   * @param string       $topic          The scope of the packet, contained in the first row
   * @param string       $origin         The user that originated the packet
   * @param array|string $target_s       Either one user or the list of users to which the packet is
   *                                     destined to
   * @param array|null   $headers        The headers of the packet
   * @param array|null   $body           The body of the packet, after one row from the headers
   * @return void
   */
  public static function sendChannelPacket(
    string       $directive,
    string       $topic,
    string       $origin,
    array|string $target_s,
    ?array       $headers = null,
    ?array       $body = null,
  ) {
    try {
      $zeroMQ = ZeroMQModule::getInstance();
    } catch (Exception $e) {
      LogModule::log(
        'WebSocket',
        'sending message to server',
        "failed to send, connection isn't initialized",
        true,
        [
          "message" => $e->getMessage(),
        ]
      );
      return;
    }
    
    $zeroMQ->sendData(
      [
        'origin'    => $origin,
        'target_s'  => $target_s,
        'directive' => $directive,
        'topic'     => $topic,
        'payload'   => [
          'headers' => $headers,
          'body'    => $body,
        ],
      ]
    );
  }
  
  /**
   * Utility method used only in this class to format a packet, given its parts, to a string of
   * this schema:
   *
   * -------------------
   * VERB SCOPE
   * headers: values
   *
   * {
   *  body
   * }
   * -------------------
   *
   * @param string      $verb
   * @param string|null $scope
   * @param array|null  $headers
   * @param array|null  $body
   * @return string
   */
  public function generateChannelPacket(
    string  $verb,
    ?string $scope = null,
    ?array  $headers = null,
    ?array  $body = null
  ): string {
    $formattedHeaders = '';
    if (!is_null($headers)) {
      foreach ($headers as $key => $value) {
        $formattedHeaders .= PHP_EOL . "$key: $value";
      }
    }
    
    $formattedBody = '';
    if (!is_null($body)) {
      $formattedBody .= PHP_EOL . PHP_EOL . json_encode($body, JSON_PRETTY_PRINT);
    }
    
    return "$verb" . (" $scope" ?? '') .
      $formattedHeaders .
      $formattedBody;
  }
  
  // ==== API request handing ======================================================================
  
  /**
   * Redirect the incoming packets from the API runtime to the specific use case
   *
   * @param $packet
   * @return void
   */
  public function onAPIRequest($packet): void {
    $packet = json_decode($packet, JSON_OBJECT_AS_ARRAY);
    
    $origin = $packet['origin'] ?? null;
    $target_s = $packet['target_s'] ?? null;
    $topic = $packet['topic'] ?? null;
    $directive = $packet['directive'] ?? null;
    $payload = $packet['payload'] ?? null;
    
    if (
      is_null($origin) ||
      is_null($target_s) ||
      is_null($topic) ||
      is_null($directive) ||
      is_null($payload)
    ) {
      LogModule::log(
        'WebSocket',
        'API request decoding',
        'Incorrect packet schema',
        true
      );
      return;
    }
    
    $headers = $payload['headers'] ?? null;
    $body = $payload['body'] ?? null;
    
    if (!is_array($target_s)) $target_s = [$target_s];
    
    foreach ($target_s as $target) {
      $targetedUser = $this->users->getFromInfo($target);
      $targetedUser?->send(
        $this->generateChannelPacket(
                   $directive,
          scope  : $topic,
          headers: $headers,
          body   : $body
        )
      );
    }
  }
  
  // ==== Native WebSocket methods =================================================================
  
  /**
   * @inheritDoc
   */
  public function onOpen(ConnectionInterface $conn) {}
  
  /**
   * Used only for validating the connection with a message of this schema:
   * {
   *  "token": "..."
   * }
   *
   * After validation the connection can be saved in the storage with the user's username as key
   *
   * @inheritDoc
   */
  function onMessage(ConnectionInterface $from, $msg) {
    $packet = json_decode($msg, JSON_OBJECT_AS_ARRAY);
    
    $token = $packet['token'] ?? null;
    if (is_null($token)) {
      $from->send(
        $this->generateChannelPacket(
                'ERROR',
          body: Utilities::generateErrorMessage(NullAttributes::CODE)
        )
      );
      return;
    }
    
    $username = DatabaseService::validateUser($token);
    if (!is_string($username)) {
      $from->send(
        $this->generateChannelPacket(
                'ERROR',
          body: Utilities::generateErrorMessage($username)
        )
      );
      return;
    }
    
    $from->send(
      $this->generateChannelPacket(
                 'CONNECTED',
        headers: ["for" => $username]
      )
    );
    $this->users->attach($from, $username);
  }
  
  /**
   * @inheritDoc
   */
  public function onClose(ConnectionInterface $conn) {
    if ($this->users->contains($conn)) {
      $this->users->detach($conn);
    }
  }
  
  /**
   * @inheritDoc
   */
  public function onError(ConnectionInterface $conn, Exception $e) {
    LogModule::log(
      'WebSocket',
      'Connection interface',
      'General error caught by the interface',
      true,
      [
        "message" => $e->getMessage(),
      ]
    );
    if ($this->users->contains($conn)) {
      $this->users->detach($conn);
    }
  }
}