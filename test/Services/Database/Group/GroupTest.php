<?php

namespace Wave\Tests\Services\Database\Group;

use PHPUnit\Framework\TestCase;
use Wave\Model\Group\Group;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\Elaboration\WrongState;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Tests\Utilities\TestUtilities;
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
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
  
  /**
   * @group getGroupInformation
   */
  public function testCorrectGroupsInformationRetrieve(): array {
    echo PHP_EOL . '==== getGroupInformation =====================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct groups information retrieve...' . PHP_EOL;
    
    self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    );
    
    self::$service->createGroup(
      self::$secondUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$firstUser['username'],
      ]
    );
    
    $result = self::$service->getGroupInformation(
      self::$secondUser['token'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNotEmpty($result);
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group getGroupInformation
   */
  public function testCorrectGroupInformationRetrieve(): array {
    echo PHP_EOL . 'Testing correct group information retrieve...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->getGroupInformation(
      self::$secondUser['token'],
      $group,
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group getGroupInformation
   */
  public function testGetGroupInformationWithUnknownGroupUUID(): array {
    echo PHP_EOL . 'Testing retrieve group information with unknown group uuid...' . PHP_EOL;
    
    $result = self::$service->getGroupInformation(
      self::$secondUser['token'],
      Utilities::generateUuid(),
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== changeGroupStatus ======================================================================
  // ===============================================================================================
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusCorrectArchiveProcedure(): array {
    echo PHP_EOL . '==== changeGroupStatus =====================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'A'
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusArchiveWithWrongState(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'A'
    );
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'A'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      WrongState::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusCorrectPinnedProcedure(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'P'
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusPinnedWithWrongState(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'P'
    );
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'P'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      WrongState::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusCorrectMuteProcedure(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'M'
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusMuteWithWrongState(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'M'
    );
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'M'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      WrongState::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusCorrectUnarchiveProcedure(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'A'
    );
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'Ua'
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusUnarchiveWithWrongState(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'Ua'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      WrongState::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusCorrectUnpinnedProcedure(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'P'
    );
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'Up'
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusUnpinnedWithWrongState(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'Up'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      WrongState::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusCorrectUnmuteProcedure(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'M'
    );
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'Um'
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeGroupStatus
   */
  public function testChangeGroupStatusUnmuteWithWrongState(): array {
    echo PHP_EOL . 'Testing correct archive group procedure...' . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
    
    $result = self::$service->changeGroupStatus(
      self::$secondUser['token'],
      $group,
      'Um'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      WrongState::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  // ==== changeGroupInformation ===================================================================
  // ===============================================================================================
  
  /**
   * @group changeGroupInformation
   */
  public function testCorrectGroupInformationChange(): array {
    echo PHP_EOL . '==== changeGroupInformation ==================================' . PHP_EOL;
    
    echo PHP_EOL . "Testing correct group's information change..." . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
        "insomnia_agent",
      ]
    )['uuid'];
    
    $newGroupName = 'new Group name';
    
    $result = self::$service->changeGroupInformation(
      self::$secondUser['token'],
      $group,
      $newGroupName
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    DatabaseModule::execute(
      'DELETE FROM `groups`
             WHERE name = :group_name',
      [
        ':group_name' => $newGroupName,
      ]
    );
    
    return $result;
  }
  
  /**
   * @group changeGroupInformation
   */
  public function testChangeGroupInformationWithUnknownGroup(): array {
    echo PHP_EOL . "Testing group's information change with unknown group..." . PHP_EOL;
    
    $newGroupName = 'new Group name';
    
    $result = self::$service->changeGroupInformation(
      self::$secondUser['token'],
      Utilities::generateUuid(),
      $newGroupName
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== exitGroup ================================================================================
  // ===============================================================================================
  
  /**
   * @group exitGroup
   */
  public function testCorrectExitGroupProcedure(): array {
    echo PHP_EOL . '==== changeGroupInformation ==================================' . PHP_EOL;
    
    echo PHP_EOL . "Testing correct group exit procedure..." . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
        "insomnia_agent",
      ]
    )['uuid'];
    
    $result = self::$service->exitGroup(
      self::$secondUser['token'],
      $group,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group exitGroup
   */
  public function testCorrectGroupDeletionProcedure(): array {
    echo PHP_EOL . "Testing correct group deletion procedure..." . PHP_EOL;
    
    $group = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
    )['uuid'];
    
    $result = self::$service->exitGroup(
      self::$firstUser['token'],
      $group,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group exitGroup
   */
  public function testExitGroupWithUnknownGroup(): array {
    echo PHP_EOL . "Testing group exit with unknown group..." . PHP_EOL;
    
    $result = self::$service->exitGroup(
      self::$secondUser['token'],
      Utilities::generateUuid(),
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  public function tearDown(): void {
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