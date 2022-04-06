<?php

namespace Wave\Tests\Services\WebSocket;

use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use Wave\Model\Contact\Contact;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Utilities\Utilities;

class WebSocketServiceTest extends TestCase {
  protected static DatabaseService $service;
  
  private static array $originUser = [
    'username' => 'first_user',
    'password' => 'Fr6/ese342f',
    'name'     => 'first',
    'surname'  => 'user',
    'source'   => null,
    'token'    => null,
  ];
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== WebSocket service =======================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$service = DatabaseService::getInstance();
    
    // ==== generate first dummy user
    self::$originUser['source'] = Utilities::generateUuid();
    
    self::$service->createUser(
      self::$originUser['username'],
      self::$originUser['password'],
      self::$originUser['name'],
      self::$originUser['surname'],
    );
    
    // ==== generate first dummy token
    self::$originUser['token'] = self::$service->login(
      self::$originUser['username'],
      self::$originUser['password'],
      self::$originUser['source'],
    )['token'];
    
  }
  
  public function testContactRequestToInsomniaAgent(): array {
    echo PHP_EOL . 'Testing contact request to Insomnia agent...' . PHP_EOL;
    
    $result = self::$service->contactRequest(
      self::$originUser['token'],
      'insomnia_agent',
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['username']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['name']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['surname']),
    );
    self::assertEquals(
      Success::CODE,
      Contact::validateStatus($result['status']),
    );
    return $result;
  }
  
  public function testInsomniaAgentChange() {
    echo PHP_EOL . "Testing change of Insomnia agent's information..." . PHP_EOL;
    
    $newUsername = "new_username";
    $newName = "New name";
    
    $result = self::$service->changeUserInformation(
                self::$originUser['token'],
      username: $newUsername,
      name    : $newName,
    );
    
    self::$originUser['username'] = $newUsername;
    self::$originUser['name'] = $newName;
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['username']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['name']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['surname']),
    );
  }
  
  public static function tearDownAfterClass(): void {
    $loop = Loop::get();
    $loop->addTimer(1, function () use ($loop) {
      $loop->stop();
    });
    
    
    $userId = DatabaseModule::fetchOne(
      'SELECT user_id
             FROM users
             WHERE username = :username',
      [
        ':username' => 'insomnia_agent',
      ]
    )['user_id'];
    
    DatabaseModule::execute(
      'DELETE FROM contacts
            WHERE first_user = :first_user
               OR second_user = :first_user',
      [
        ':first_user' => $userId,
      ]
    );
    
    DatabaseModule::execute(
      'DELETE FROM users
            WHERE username=:username
               OR username = :new_username',
      [
        ':username'     => self::$originUser['username'],
        ':new_username' => 'new_username',
      ]
    );
  }
  
}





















