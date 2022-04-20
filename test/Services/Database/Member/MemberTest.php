<?php

namespace Wave\Tests\Services\Database\Member;

use PHPUnit\Framework\TestCase;
use Wave\Model\Member\Member;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\State\Forbidden;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\Wave\Wave;
use Wave\Tests\Utilities\TestUtilities;
use Wave\Utilities\Utilities;

/**
 * @author Giacomo Guidotto
 */
class MemberTest extends TestCase {
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
  
  private static array $group = [
    'uuid' => null,
    'name' => 'test_group',
    'info' => 'Testing group',
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
    
    // ==== generate dummy group
    self::$group['uuid'] = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
    )['uuid'];
  }
  
  // ==== addMember ==============================================================================
  // ===============================================================================================
  
  /**
   * @group addMember
   */
  public function testCorrectAddMemberProcedure(): array {
    echo PHP_EOL . '==== addMember ===============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct add member procedure...' . PHP_EOL;
    
    $result = self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    return $result;
  }
  
  /**
   * @group addMember
   */
  public function testAddMemberWithUnknownUser(): array {
    echo PHP_EOL . 'Testing add member with unknown user...' . PHP_EOL;
    
    $result = self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      "unknown_user",
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== getMemberList ==============================================================================
  // ===============================================================================================
  
  /**
   * @group getMemberList
   */
  public function testCorrectMemberRetrieve(): array {
    echo PHP_EOL . '==== getMemberList ===========================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct member retrieve...' . PHP_EOL;
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->getMemberList(
      self::$firstUser['token'],
      self::$group['uuid'],
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
      Member::validatePermission($result['permissions']),
    );
    self::assertNull($result['last_seen_message']);
    
    return $result;
  }
  
  /**
   * @group getMemberList
   */
  public function testCorrectMemberListRetrieve(): array {
    echo PHP_EOL . 'Testing correct member list retrieve...' . PHP_EOL;
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->getMemberList(
      self::$firstUser['token'],
      self::$group['uuid'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    return $result;
  }
  
  /**
   * @group getMemberList
   */
  public function testMemberRetrieveWithUnknownUser(): array {
    echo PHP_EOL . 'Testing member retrieve with unknown user...' . PHP_EOL;
    
    $result = self::$service->getMemberList(
      self::$firstUser['token'],
      self::$group['uuid'],
      "unknown_user",
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== changeMemberPermission ===================================================================
  // ===============================================================================================
  
  /**
   * @group changeMemberPermission
   */
  public function testCorrectChangeMemberPermissionProcedure(): array {
    echo PHP_EOL . '==== addMember ===============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct change member permission procedure...' . PHP_EOL;
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      "insomnia_agent",
    );
    
    $result = self::$service->changeMemberPermission(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
      Wave::MAX_GROUP_PERMISSION
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
      Member::validatePermission($result['permissions']),
    );
    self::assertNull($result['last_seen_message']);
    
    return $result;
  }
  
  /**
   * @group changeMemberPermission
   */
  public function testChangeMemberPermissionWithUnauthorizedUser(): array {
    echo PHP_EOL . 'Testing change member permission with unauthorized user...' . PHP_EOL;
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->changeMemberPermission(
      self::$secondUser['token'],
      self::$group['uuid'],
      self::$firstUser['username'],
      201
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Forbidden::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== removeMember =============================================================================
  // ===============================================================================================
  
  /**
   * @group removeMember
   */
  public function testCorrectRemoveMemberProcedure(): array {
    echo PHP_EOL . '==== removeMember ============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct remove member procedure...' . PHP_EOL;
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->removeMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    return $result;
  }
  
  /**
   * @group removeMember
   */
  public function testRemoveMemberWithUnauthorizedUser(): array {
    echo PHP_EOL . 'Testing change member permission with unauthorized user...' . PHP_EOL;
    
    self::$service->addMember(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->removeMember(
      self::$secondUser['token'],
      self::$group['uuid'],
      self::$firstUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Forbidden::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  public function tearDown(): void {
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    DatabaseModule::execute(
      'DELETE FROM `groups`
             WHERE name = :group_name',
      [
        ':group_name' => self::$group['name'],
      ]
    );
    
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