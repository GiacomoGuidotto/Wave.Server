<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\WebSocket;

use Exception;
use JetBrains\PhpStorm\Pure;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Wave\Model\Singleton\Singleton;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Log\LogModule;
use Wave\Services\WebSocket\Connection\UsersConnectionStorage;
use Wave\Services\ZeroMQ\ZeroMQModule;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Utilities\Utilities;

/**
 * WebSocket service
 *
 * WAMP-based service for the "Channel" management
 */
class WebSocketService extends Singleton implements MessageComponentInterface, WebSocketInterface {
  
  protected UsersConnectionStorage $users;
  
  private array $contacts = [];
  private array $groups = [];
  
  #[Pure] public function __construct() {
    $this->users = new UsersConnectionStorage();
    // TODO fill $contacts with "username" => "username" and $groups "group_UUID" => ["members"]
    //    from existing entity from database
  }
  
  // ==== Utility method ===========================================================================
  
  /**
   * Utility method used in other classes to send data to this service through the ZeroMQ channel.
   *
   * With packets of this schema:
   *
   * {
   *  origin: user's username,
   *  directive: CREATE | UPDATE | DELETE,
   *  topic: resource/attribute,
   *  payload: {
   *    headers: [
   *      ...
   *    ],
   *    body: {
   *      ...
   *    }
   *  }
   * }
   *
   * @param string     $origin
   * @param string     $directive
   * @param string     $topic
   * @param array|null $headers
   * @param array|null $body
   * @return void
   */
  public static function sendToWebSocket(
    string $origin,
    string $directive,
    string $topic,
    array  $headers = null,
    array  $body = null,
  ) {
    try {
      $zeroMQ = ZeroMQModule::getInstance();
    } catch (Exception $e) {
      LogModule::log(
        'WebSocket',
        'sending message to server',
        "failed to send, connection isn't initialized: " . $e->getMessage(),
        true
      );
      // TODO replace with context
      return;
    }
    $zeroMQ->sendData(
      [
        'origin'    => $origin,
        'directive' => $directive,
        'topic'     => $topic,
        'payload'   => [
          'headers' => $headers,
          'body'    => $body,
        ],
      ]
    );
  }
  
  // ==== Native WebSocket methods =================================================================
  
  /**
   * @inheritDoc
   */
  public function onOpen(ConnectionInterface $conn) {}
  
  /**
   * Used only for validating the connection with a message of this schema:
   * {
   *  "token": ...
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
        json_encode(Utilities::generateErrorMessage(NullAttributes::CODE))
      );
      return;
    }
    
    $username = DatabaseService::validateUser($token);
    if (!is_string($username)) {
      $from->send(
        json_encode(Utilities::generateErrorMessage($username))
      );
      return;
    }
    
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
      'General error caught by the interface: ' . $e->getMessage(),
      true,
    );
    if ($this->users->contains($conn)) {
      $this->users->detach($conn);
    }
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
    $topic = $packet['topic'] ?? null;
    $directive = $packet['directive'] ?? null;
    $payload = $packet['payload'] ?? null;
    
    if ($origin == null || $topic == null || $directive == null || $payload == null) {
      LogModule::log(
        'WebSocket',
        'API request decoding',
        'Incorrect packet schema',
        true
      );
      // TODO respond to origin with error
      return;
    }
    
    switch ("$directive $topic") {
      case 'CREATE contact':
        $this->onContactCreate($origin, $payload);
        break;
      case 'DELETE contact/status':
        $this->onContactDelete($origin, $payload);
        break;
      case 'UPDATE contact/status':
      case 'UPDATE contact/information':
        // from 3 use cases
        $this->onContactUpdate($origin, $payload);
        break;
      case 'CREATE group':
        $this->onGroupCreate($origin, $payload);
        break;
      case 'UPDATE group/information':
        $this->onGroupUpdate($origin, $payload);
        break;
      case 'DELETE group/member':
        $this->onMemberDelete($origin, $payload);
        break;
      case 'CREATE group/member':
        $this->onMemberCreate($origin, $payload);
        break;
      case 'UPDATE group/member':
        $this->onMemberUpdate($origin, $payload);
        break;
      case 'DELETE group':
        $this->onGroupDelete($origin, $payload);
        break;
      case 'CREATE message':
        $this->onMessageCreate($origin, $payload);
        break;
      case 'UPDATE message':
        $this->onMessageUpdate($origin, $payload);
        break;
      case 'DELETE message':
        $this->onMessageDelete($origin, $payload);
        break;
      default: // Error case
        LogModule::log(
          'WebSocket',
          'API request handling',
          'Incorrect directives form ZeroMQ packet',
          true
        );
    }
  }
  
  // ==== Use cases ================================================================================
  
  /**
   * @inheritDoc
   */
  function onContactCreate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onContactCreate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onContactUpdate(
    string $origin,
    array  $payload,
  ): void {
    $headers = $payload['headers'] ?? null;
    $body = $payload['headers'] ?? null;
    
    if (is_null($headers['directive'] ?? null)) {
      // "New contact infos" case
      // TODO parse through contacts reference with old_username in headers, then change reference
    } else {
      // "New contact status/reply" case
    }
  }
  
  /**
   * @inheritDoc
   */
  function onContactDelete(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onContactDelete() method.
  }
  
  /**
   * @inheritDoc
   */
  function onGroupCreate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onGroupCreate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onGroupUpdate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onGroupUpdate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onGroupDelete(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onGroupDelete() method.
  }
  
  /**
   * @inheritDoc
   */
  function onMemberCreate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onMemberCreate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onMemberUpdate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onMemberUpdate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onMemberDelete(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onMemberDelete() method.
  }
  
  /**
   * @inheritDoc
   */
  function onMessageCreate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onMessageCreate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onMessageUpdate(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onMessageUpdate() method.
  }
  
  /**
   * @inheritDoc
   */
  function onMessageDelete(
    string $origin,
    array  $payload,
  ): void {
    // TODO: Implement onMessageDelete() method.
  }
}