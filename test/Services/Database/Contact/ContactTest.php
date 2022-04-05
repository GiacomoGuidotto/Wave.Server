<?php

namespace Wave\Tests\Services\Database\Contact;

use Monolog\Test\TestCase;
use Wave\Model\Contact\Contact;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Utilities\Utilities;

class ContactTest extends TestCase {
  protected static DatabaseService $service;
  
  private static array $firstUser = [
    'username' => 'first_user',
    'password' => 'Fr6/ese342f',
    'name'     => 'first',
    'surname'  => 'user',
    'source'   => null,
    'token'    => null,
  ];
  
  private static array $secondUser = [
    'username' => 'second_user',
    'password' => 'Fr6/ese342f',
    'name'     => 'second',
    'surname'  => 'user',
    'source'   => null,
    'token'    => null,
  ];
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== Contact =================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$service = DatabaseService::getInstance();
    
    // ==== generate first dummy user
    self::$firstUser['source'] = Utilities::generateUuid();
    
    self::$service->createUser(
      self::$firstUser['username'],
      self::$firstUser['password'],
      self::$firstUser['name'],
      self::$firstUser['surname'],
    );
    
    // ==== generate first dummy token
    self::$firstUser['token'] = self::$service->login(
      self::$firstUser['username'],
      self::$firstUser['password'],
      self::$firstUser['source'],
    )['token'];
    
    // ==== generate second dummy user
    self::$secondUser['source'] = Utilities::generateUuid();
    
    self::$service->createUser(
      self::$secondUser['username'],
      self::$secondUser['password'],
      self::$secondUser['name'],
      self::$secondUser['surname'],
    );
    
    // ==== generate second dummy token
    self::$secondUser['token'] = self::$service->login(
      self::$secondUser['username'],
      self::$secondUser['password'],
      self::$secondUser['source'],
    )['token'];
  }
  
  // ==== contactRequest ===============================================================================
  // ===============================================================================================
  
  /**
   * @group contactRequest
   */
  public function testCorrectContactRequest(): array {
    echo PHP_EOL . '==== contactRequest ==========================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct contact request...' . PHP_EOL;
    
    $result = self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
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
    self::assertNull($result['picture']);
    self::assertEquals(
      Success::CODE,
      Contact::validateStatus($result['status']),
    );
    
    return $result;
  }
  
  /**
   * @group contactRequest
   */
  public function testContactRequestWithUnknownUser(): array {
    echo PHP_EOL . 'Testing contact request with unknown user...' . PHP_EOL;
    
    $result = self::$service->contactRequest(
      self::$firstUser['token'],
      'random_user',
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group contactRequest
   */
  public function testContactRequestWithBlockedUser(): array {
    // TODO when changeContactStatus is implemented
    
    self::assertTrue(true);
    
    return [];
  }
  
  /**
   * @group contactRequest
   */
  public function testContactRequestWithPreviouslyRemoved(): array {
    // TODO when changeContactStatus is implemented
    
    self::assertTrue(true);
    
    return [];
  }
  
  // ==== deleteContactRequest =====================================================================
  // ===============================================================================================
  
  
  public static function tearDownAfterClass(): void {
    DatabaseModule::execute(
      'DELETE FROM users
            WHERE username = :first_username
               OR username = :second_username',
      [
        ':first_username' => self::$firstUser['username'],
        ':second_username' => self::$secondUser['username'],
      ]
    );
  }
}