<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\WebSocket;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Wave\Model\Singleton\Singleton;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Log\LogModule;
use Wave\Services\WebSocket\Connection\UsersConnectionStorage;
use Wave\Services\ZeroMQ\ZeroMQModule;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\WebSocket\IncorrectPacketSchema;
use Wave\Utilities\Utilities;

/**
 * WebSocket service
 *
 * WAMP-based service for the "Channel" management
 */
class WebSocketService extends Singleton implements MessageComponentInterface, WebSocketInterface {
  
  protected UsersConnectionStorage $users;
  
  private array $contacts;
  private array $groups;
  
  public function __construct() {
    $this->users = new UsersConnectionStorage();
    $references = DatabaseService::retrieveReferences();
    $this->contacts = $references['contacts'];
    $this->groups = $references['groups'];
  }
  
  // ==== Utility methods ===========================================================================
  
  /**
   * Utility method used in other classes to send data to this service through the ZeroMQ channel.
   *  TODO change schema: add target
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
      'General error caught by the interface: ' . $e->getMessage(),
      true,
    ); // TODO replace with context
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
      // respond to origin with error
      $originUser = $this->users->getFromInfo($origin);
      $originUser?->send(
        $this->generateChannelPacket(
                'ERROR',
          body: Utilities::generateErrorMessage(IncorrectPacketSchema::CODE)
        )
      );
      
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
    $headers = $payload['headers'] ?? null;
    $body = $payload['body'] ?? null;
    $recipient = $headers['to'] ?? null;
    
    if (is_null($headers) || is_null($body) || is_null($recipient)) {
      LogModule::log(
        'WebSocket',
        'API request decoding',
        'Incorrect packet schema',
        true
      );
      return;
    }
    
    // add contact reference in user's contact array, from both sides
    if (!in_array($origin, $this->contacts)) {
      $this->contacts[$origin] = [];
    }
    if (!in_array($recipient, $this->contacts[$origin])) {
      $this->contacts[$origin][] = $recipient;
    }
    if (!in_array($recipient, $this->contacts)) {
      $this->contacts[$recipient] = [];
    }
    if (!in_array($origin, $this->contacts[$recipient])) {
      $this->contacts[$recipient][] = $origin;
    }
    
    $recipientUser = $this->users->getFromInfo($recipient);
    $recipientUser?->send(
      $this->generateChannelPacket(
              'CREATE',
              'contact',
        body: $body
      )
    );
  }
  
  /**
   * @inheritDoc
   */
  function onContactUpdate(
    string $origin,
    array  $payload,
  ): void {
    $headers = $payload['headers'] ?? null;
    $body = $payload['body'] ?? null;
    
    if (is_null($headers['directive'] ?? null)) {
      // ==== "New contact infos" case ===================================================
      // Retrieve old user reference from the packet
      $oldUserUsername = $headers['old_username'] ?? null;
      $newUserUsername = $body['username'] ?? null;
      
      if (is_null($oldUserUsername) || is_null($newUserUsername)) {
        LogModule::log(
          'WebSocket',
          'API request decoding',
          'Incorrect packet schema',
          true
        );
        // respond to origin with error
        $originUser = $this->users->getFromInfo($origin);
        $originUser?->send(
          $this->generateChannelPacket(
                  'ERROR',
            body: Utilities::generateErrorMessage(IncorrectPacketSchema::CODE)
          )
        );
        return;
      }
      
      // Check if the updated user has a contact
      if (!array_key_exists($oldUserUsername, $this->contacts)) {
        return;
      }
      
      // if username has changed, change reference to user's contacts
      // and change each user's contact's reference of the first user
      if ($oldUserUsername !== $newUserUsername) {
        $this->contacts[$newUserUsername] = $this->contacts[$oldUserUsername];
        unset($this->contacts[$oldUserUsername]);
        
        foreach ($this->contacts[$newUserUsername] as $contact) {
          array_map(
            function ($contactsContact) use ($oldUserUsername, $newUserUsername) {
              return $contactsContact === $oldUserUsername ? $newUserUsername : $contactsContact;
            },
            $this->contacts[$contact],
          );
        }
      }
      
      // Retrieve user's contacts from new or not user reference
      $userContacts = $this->contacts[$newUserUsername] ?? null;
      
      if (is_null($userContacts)) {
        LogModule::log(
          'WebSocket',
          'API request decoding',
          'Incorrect packet schema',
          true
        );
        // respond to origin with error
        $originUser = $this->users->getFromInfo($origin);
        $originUser?->send(
          $this->generateChannelPacket(
                  'ERROR',
            body: Utilities::generateErrorMessage(NotFound::CODE)
          )
        );
        return;
      }
      
      // Send to each contact reference new user data
      foreach ($userContacts as $userContact) {
        $targetedUser = $this->users->getFromInfo($userContact);
        $targetedUser?->send(
          $this->generateChannelPacket(
                  'UPDATE',
                  'contact/information',
            body: $body
          )
        );
      }
    } else {
      // ==== "New contact status/reply" case ============================================
      $headers = $payload['headers'] ?? null;
      $recipient = $headers['to'] ?? null;
      $directive = $headers['directive'] ?? null;
      
      if (is_null($headers) || is_null($directive) || is_null($recipient)) {
        LogModule::log(
          'WebSocket',
          'API request decoding',
          'Incorrect packet schema',
          true
        );
        return;
      }
      
      $recipientUser = $this->users->getFromInfo($recipient);
      $recipientUser?->send(
        $this->generateChannelPacket(
                   'UPDATE',
                   'contact/status',
          headers: [
                     'directive' => $directive,
                   ]
        )
      );
    }
  }
  
  /**
   * @inheritDoc
   */
  function onContactDelete(
    string $origin,
    array  $payload,
  ): void {
    $headers = $payload['headers'] ?? null;
    $target = $headers['to'] ?? null;
    
    if (is_null($headers) || is_null($target)) {
      LogModule::log(
        'WebSocket',
        'API request decoding',
        'Incorrect packet schema',
        true
      );
      
    }
    
    // Delete contact's reference from each other
    // and delete the user's reference if it hasn't any contacts
    $originContacts = &$this->contacts[$origin];
    if (($targetKey = array_search($target, $originContacts)) !== false) {
      unset($originContacts[$targetKey]);
    }
    if (count($originContacts) === 0) {
      unset($originContacts);
    }
    
    $targetContacts = &$this->contacts[$target];
    if (($originKey = array_search($origin, $targetContacts)) !== false) {
      unset($targetContacts[$originKey]);
    }
    if (count($targetContacts) === 0) {
      unset($targetContacts);
    }
    
    
    $targetedUser = $this->users->getFromInfo($target);
    $targetedUser?->send(
      $this->generateChannelPacket(
                 'DELETE',
                 'contact/status',
        headers: [
                   'username' => $origin,
                 ]
      )
    );
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