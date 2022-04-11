<?php /** @noinspection SqlResolve */

namespace Wave\Tests\Services\Database\Contact;

use Monolog\Test\TestCase;
use Wave\Model\Contact\Contact;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\Elaboration\BlockedByUser;
use Wave\Specifications\ErrorCases\Elaboration\DirectiveNotAllowed;
use Wave\Specifications\ErrorCases\Elaboration\WrongStatus;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Tests\Utilities\TestUtilities;
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
  
  private static function retrieveDatabaseStatus(): string {
    $firstUserId = DatabaseModule::fetchOne(
      'SELECT user_id
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => self::$firstUser['username'],
      ]
    )['user_id'];
    
    $secondUserId = DatabaseModule::fetchOne(
      'SELECT user_id
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => self::$secondUser['username'],
      ]
    )['user_id'];
    
    $databaseContact = DatabaseModule::fetchOne(
      'SELECT status, blocked_by, chat, active
             FROM contacts
             WHERE (first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user)',
      [
        ':first_user'  => $firstUserId,
        ':second_user' => $secondUserId,
      ]
    );
    
    return (is_array($databaseContact)) ? "| status: " . $databaseContact['status'] .
      " | blocked_by: " . ($databaseContact['blocked_by'] ?? 'null') .
      " | chat: " . ($databaseContact['chat'] ?? 'null') .
      " | active: " . $databaseContact['active'] . ' |' : 'Not found';
    
  }
  
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
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
    echo PHP_EOL . 'Testing contact request with blocked user...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'B'
    );
    
    $result = self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertEquals(
      BlockedByUser::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group contactRequest
   */
  public function testContactRequestWithPreviouslyRemoved(): array {
    echo PHP_EOL . 'Testing contact request with blocked user...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'R'
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    $result = self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
  
  // ==== deleteContactRequest =====================================================================
  // ===============================================================================================
  
  /**
   * @group deleteContactRequest
   */
  public function testCorrectDeleteContactRequest(): void {
    echo PHP_EOL . '==== deleteContactRequest ====================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct contact request deletion...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->deleteContactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertNull($result);
  }
  
  /**
   * @group deleteContactRequest
   */
  public function testDeleteContactRequestWithUnknownUser(): array {
    echo PHP_EOL . 'Testing contact request deletion with unknown user...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->deleteContactRequest(
      self::$firstUser['token'],
      'random_user',
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group deleteContactRequest
   */
  public function testDeleteContactRequestWithActiveContact(): array {
    echo PHP_EOL . 'Testing contact request deletion with active contact...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    $result = self::$service->deleteContactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertEquals(
      WrongStatus::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group deleteContactRequest
   */
  public function testDeleteContactRequestFromTarget(): array {
    echo PHP_EOL . '==== deleteContactRequest ====================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct contact request deletion...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->deleteContactRequest(
      self::$secondUser['token'],
      self::$firstUser['username'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertEquals(
      DirectiveNotAllowed::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== changeContactStatus ======================================================================
  // ===============================================================================================
  
  // ==== decline ==================================================================================
  
  /**
   * @group   changeContactStatus
   */
  public function testChangeContactStatusCorrectDeclineProcedure(): void {
    echo PHP_EOL . '==== changeContactStatus =====================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct contact request decline procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'D'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertNull($result);
    
  }
  
  // ==== accept ===================================================================================
  
  /**
   * @group changeContactStatus
   */
  public function testChangeContactStatusCorrectAcceptProcedure(): array {
    echo PHP_EOL . 'Testing correct contact request accept procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  /**
   * @group changeContactStatus
   */
  public function testChangeContactStatusAcceptProcedureFromFirstUser(): array {
    echo PHP_EOL . 'Testing correct contact request accept procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      self::$firstUser['token'],
      self::$secondUser['username'],
      'A'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertEquals(
      DirectiveNotAllowed::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== block ====================================================================================
  
  /**
   * @group changeContactStatus
   */
  public function testChangeContactStatusCorrectBlockBeforeAcceptProcedure(): array {
    echo PHP_EOL . 'Testing correct block before accept contact request procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'B'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
   * @group changeContactStatus
   */
  public function testChangeContactStatusBlockBeforeAcceptFromFirstUser(): array {
    echo PHP_EOL . 'Testing correct block before accept contact request procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      self::$firstUser['token'],
      self::$secondUser['username'],
      'B'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    self::assertEquals(
      DirectiveNotAllowed::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group changeContactStatus
   */
  public function testChangeContactStatusCorrectBlockAfterAcceptProcedure(): array {
    echo PHP_EOL . 'Testing correct block after accept contact request procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    $result = self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'B'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    
    return $result;
  }
  
  // ==== remove ====================================================================================
  
  /**
   * @group changeContactStatus
   */
  public function testChangeContactStatusCorrectRemoveProcedure(): void {
    echo PHP_EOL . 'Testing correct remove contact procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    $result = self::$service->changeContactStatus(
      self::$firstUser['token'],
      self::$secondUser['username'],
      'R'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    
    self::assertNull($result);
  }
  
  // ==== unblock ==================================================================================
  
  /**
   * @group changeContactStatus
   */
  public function testChangeContactStatusCorrectUnblockBeforeAcceptProcedure(): array {
    echo PHP_EOL . 'Testing correct unblock before accept contact request procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'B'
    );
    
    $result = self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'U'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
   * @group changeContactStatus
   */
  public function testChangeContactStatusCorrectUnblockAfterAcceptProcedure(): array {
    echo PHP_EOL . 'Testing correct unblock after accept contact request procedure...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'B'
    );
    
    $result = self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'U'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    echo PHP_EOL . "Database status: " . PHP_EOL . self::retrieveDatabaseStatus();
    
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    
    return $result;
  }
  
  // ==== getContactInformation ====================================================================
  // ===============================================================================================
  
  /**
   * @group   getContactInformation
   */
  public function testCorrectContactsInformationRetrieve(): array {
    echo PHP_EOL . '==== getContactInformation ===================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct contacts information retrieve...' . PHP_EOL;
    
    $thirdUser = [
      'username' => 'third_user',
      'password' => 'Fr6/ese342f',
      'name'     => 'third',
      'surname'  => 'user',
      'source'   => Utilities::generateUuid(),
    ];
    
    // ==== generate third dummy user
    self::$service->createUser(
      $thirdUser['username'],
      $thirdUser['password'],
      $thirdUser['name'],
      $thirdUser['surname'],
    );
    
    // ==== generate third dummy token
    $thirdUser['token'] = self::$service->login(
      $thirdUser['username'],
      $thirdUser['password'],
      $thirdUser['source'],
    )['token'];
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username']
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      $thirdUser['username']
    );
    
    self::$service->changeContactStatus(
      $thirdUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    $result = self::$service->getContactInformation(
      self::$firstUser['token'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNotEmpty($result);
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    TestUtilities::deleteGeneratedTables($thirdUser['username']);
    
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username = :username',
      [
        ':username' => $thirdUser['username'],
      ]
    );
    
    return $result;
  }
  
  /**
   * @group getContactInformation
   */
  public function testContactsInformationRetrieveWithUnknownUser(): array {
    echo PHP_EOL . 'Testing contacts information retrieve with unknown user...' . PHP_EOL;
    
    $result = self::$service->getContactInformation(
      self::$firstUser['token'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group   getContactInformation
   */
  public function testCorrectContactInformationRetrieve(): array {
    echo PHP_EOL . 'Testing correct contact information retrieve...' . PHP_EOL;
    
    $thirdUser = [
      'username' => 'third_user',
      'password' => 'Fr6/ese342f',
      'name'     => 'third',
      'surname'  => 'user',
      'source'   => Utilities::generateUuid(),
    ];
    
    // ==== generate third dummy user
    self::$service->createUser(
      $thirdUser['username'],
      $thirdUser['password'],
      $thirdUser['name'],
      $thirdUser['surname'],
    );
    
    // ==== generate third dummy token
    $thirdUser['token'] = self::$service->login(
      $thirdUser['username'],
      $thirdUser['password'],
      $thirdUser['source'],
    )['token'];
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username']
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      $thirdUser['username']
    );
    
    self::$service->changeContactStatus(
      $thirdUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    $result = self::$service->getContactInformation(
      self::$firstUser['token'],
      $thirdUser['username'],
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
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    TestUtilities::deleteGeneratedTables($thirdUser['username']);
    
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username = :username',
      [
        ':username' => $thirdUser['username'],
      ]
    );
    
    return $result;
  }
  
  /**
   * @group   getContactInformation
   */
  public function testContactInformationRetrieveWithUnknownUser(): array {
    echo PHP_EOL . 'Testing contact information retrieve with unknown user...' . PHP_EOL;
    
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username']
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    $result = self::$service->getContactInformation(
      self::$firstUser['token'],
      'random_user'
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    return $result;
  }
  
  public function tearDown(): void {
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username = :first_username
                OR username = :second_username',
      [
        ':first_username'  => self::$firstUser['username'],
        ':second_username' => self::$secondUser['username'],
      ]
    );
    
    self::$firstUser['token'] = null;
    self::$secondUser['token'] = null;
  }
}