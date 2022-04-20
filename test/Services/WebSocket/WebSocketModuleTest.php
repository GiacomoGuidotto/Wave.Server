<?php

namespace Wave\Tests\Services\WebSocket;

use Exception;
use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use Wave\Model\Contact\Contact;
use Wave\Model\Session\Session;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Utilities\Utilities;

/**
 * @author Giacomo Guidotto
 */
class WebSocketModuleTest extends TestCase {
  protected static DatabaseService $service;
  
  private static array $mockUser = [
    'username' => 'mock_user',
    'password' => 'Fr6/ese342f',
    'name'     => 'mock',
    'surname'  => 'user',
    'source'   => null,
    'token'    => null,
  ];
  
  private static array $insomniaUser = [
    'username' => 'insomnia_agent',
    'password' => 'Fr6/ese342f',
    'name'     => 'Test',
    'surname'  => 'User',
    'source'   => '2e84cbd5-abbb-11ec-bad7-525400ec12dd',
    'token'    => null,
  ];
  
  /**
   * @throws Exception
   */
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== WebSocket service =======================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$service = DatabaseService::getInstance();
    
    // ==== generate first user dummy source
    self::$mockUser['source'] = Utilities::generateUuid();
    
    $handle = fopen("insomnia_token.txt", "r");
    $token = trim(fgets($handle));
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      throw new Exception('Invalid token');
    }
    self::$insomniaUser['token'] = $token;
  }
  
  protected function setUp(): void {
    // ==== generate first dummy user
    self::$service->createUser(
      self::$mockUser['username'],
      self::$mockUser['password'],
      self::$mockUser['name'],
      self::$mockUser['surname'],
    );
    
    // ==== generate first dummy token
    self::$mockUser['token'] = self::$service->login(
      self::$mockUser['username'],
      self::$mockUser['password'],
      self::$mockUser['source'],
    )['token'];
  }
  
  // ==== request contact to Insomnia ==============================================================
  // ===============================================================================================
  
  public function testContactRequestToInsomniaAgent(): array {
    echo PHP_EOL . 'Testing contact request to Insomnia agent...' . PHP_EOL;
    
    $result = self::$service->contactRequest(
      self::$mockUser['token'],
      self::$insomniaUser['username'],
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
  
  // ==== change Insomnia contact's info ===========================================================
  // ===============================================================================================
  
  public function testInsomniaAgentChange() {
    echo PHP_EOL . "Testing change of Insomnia agent's information..." . PHP_EOL;
    
    self::$service->contactRequest(
      self::$mockUser['token'],
      self::$insomniaUser['username'],
    );
    
    $newUsername = "new_username";
    $newName = "New name";
    
    $result = self::$service->changeUserInformation(
                self::$mockUser['token'],
      username: $newUsername,
      name    : $newName,
    );
    
    self::$mockUser['username'] = $newUsername;
    self::$mockUser['name'] = $newName;
    
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
  
  // ==== delete contact request to Insomnia =======================================================
  // ===============================================================================================
  
  public function testContactRequestDeletionToInsomniaAgent(): void {
    echo PHP_EOL . 'Testing contact request deletion to Insomnia agent...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$mockUser['token'],
      self::$insomniaUser['username'],
    );
    
    $result = self::$service->deleteContactRequest(
      self::$mockUser['token'],
      self::$insomniaUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
  }
  
  // ==== delete contact request to Insomnia =======================================================
  // ===============================================================================================
  
  public function testChangeContactStatusAcceptRequestFromInsomniaAgent(): ?array {
    echo PHP_EOL . 'Testing contact request deletion to Insomnia agent...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$insomniaUser['token'],
      self::$mockUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      self::$mockUser['token'],
      self::$insomniaUser['username'],
      'A'
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
  
  // ===============================================================================================
  
  protected function tearDown(): void {
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username=:username
                OR username = :new_username',
      [
        ':username'     => self::$mockUser['username'],
        ':new_username' => 'new_username',
      ]
    );
  }
  
  public static function tearDownAfterClass(): void {
    $loop = Loop::get();
    $loop->addTimer(1, function () use ($loop) {
      $loop->stop();
    });
  }
}





















