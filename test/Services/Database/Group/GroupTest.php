<?php

namespace Wave\Tests\Services\Database\Group;

use PHPUnit\Framework\TestCase;
use Wave\Model\Group\Group;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Utilities\Utilities;

class GroupTest extends TestCase {
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
    
    // ==== generate second user dummy source
    self::$firstUser['source'] = Utilities::generateUuid();
    
    // ==== generate first user dummy source
    self::$secondUser['source'] = Utilities::generateUuid();
  }
  
  protected function setUp(): void {
    // ==== generate first dummy user
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
  
  // ==== createGroup ==============================================================================
  // ===============================================================================================
  
  /**
   * @group createGroup
   */
  public function testCreateGroupCorrectCreation(): array {
    echo PHP_EOL . '==== createGroup =============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing group correct creation...' . PHP_EOL;
    
    $randomGroupName = "test_group";
    $generatedInfo = Utilities::generateString(22);
    
    $result = self::$service->createGroup(
      self::$firstUser['token'],
      $randomGroupName,
      $generatedInfo,
      null,
      [
        self::$secondUser['username'],
      ]
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Group::validateGroup($result['uuid']),
    );
    self::assertEquals(
      Success::CODE,
      Group::validateName($result['name']),
    );
    self::assertEquals(
      Success::CODE,
      Group::validateInfo($result['info']),
    );
    self::assertNull($result['picture']);
    self::assertEquals(
      Success::CODE,
      Group::validateState($result['state']),
    );
    self::assertIsBool($result['muted']);
    
    DatabaseModule::execute(
      'DELETE FROM `groups`
             WHERE name = :group_name',
      [
        ':group_name' => $randomGroupName,
      ]
    );
    
    return $result;
  }
  
  /**
   * @group createGroup
   */
  public function testCreateGroupWithUnknownUser(): array {
    echo PHP_EOL . '==== createGroup =============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing group correct creation...' . PHP_EOL;
    
    $randomGroupName = "test_group";
    $generatedInfo = Utilities::generateString(22);
    
    $result = self::$service->createGroup(
      self::$firstUser['token'],
      $randomGroupName,
      $generatedInfo,
      null,
      [
        self::$secondUser['username'],
        "random_name",
      ]
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== getGroupInformation ======================================================================
  // ===============================================================================================
  
  public function tearDown(): void {
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username = :first_username
                OR username = :second_username',
      [
        ':first_username' => self::$firstUser['username'],
        ':second_username' => self::$secondUser['username'],
      ]
    );
    
    self::$firstUser['token'] = null;
    self::$secondUser['token'] = null;
  }
}