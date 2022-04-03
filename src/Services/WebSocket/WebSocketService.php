<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\WebSocket;

use Exception;
use JetBrains\PhpStorm\Pure;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;
use SplObjectStorage;
use Wave\Model\Singleton\Singleton;
use Wave\Services\Log\LogModule;

/**
 * WebSocket service
 *
 * WAMP-based service for the "Channel" management
 */
class WebSocketService extends Singleton implements WampServerInterface, WebSocketInterface {
  
  protected SplObjectStorage $users;
  
  private array $contacts = [];
  private array $groups = [];
  
  #[Pure] public function __construct() {
    $this->users = new SplObjectStorage();
    // TODO fill $contacts with "username" => "username" and $groups "group_UUID" => ["members"]
    //    from existing entity from database
  }
  
  // ==== API request handing ======================================================================
  
  /**
   * Redirect the incoming packets from the API runtime to the specific use case
   *
   * {
   *  origin: user's username,
   *  directive: CREATE | UPDATE | DELETE,
   *  topic: resource/attribute,
   *  payload: {
   *    headers: [
   *      header1: value1
   *      ...
   *    ],
   *    body: {
   *      ...
   *    }
   *  },
   * }
   *
   * @param $packet
   * @return void
   */
  public function onAPIRequest($packet): void {
    $packet = json_decode($packet);
    
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
      default:
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
    // TODO: Implement onContactUpdate() method.
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
  
  // ==== Native WAMP methods ======================================================================
  
  /**
   * @inheritDoc
   */
  public function onOpen(ConnectionInterface $conn) {
    $this->users->attach($conn);
    // TODO: attach users with theirs username as keys of the array
    // https://stackoverflow.com/questions/28792051/find-object-in-splobjectstorage-by-attached-info
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
    // TODO: Implement onError() method.
  }
  
  // ==== Forbidden methods ========================================================================
  
  /**
   * @inheritDoc
   */
  public function onCall(
    ConnectionInterface $conn,
                        $id,
                        $topic,
    array               $params
  ) {
    // TODO: Implement Forbidden response.
  }
  
  /**
   * @inheritDoc
   */
  public function onSubscribe(
    ConnectionInterface $conn,
                        $topic
  ) {
    // TODO: Implement Forbidden response.
  }
  
  /**
   * @inheritDoc
   */
  public function onUnSubscribe(
    ConnectionInterface $conn,
                        $topic
  ) {
    // TODO: Implement Forbidden response.
  }
  
  /**
   * @inheritDoc
   */
  public function onPublish(
    ConnectionInterface $conn,
                        $topic,
                        $event,
    array               $exclude,
    array               $eligible
  ) {
    // TODO: Implement Forbidden response.
  }
}