<?php /** @noinspection PhpArrayIndexImmediatelyRewrittenInspection */

namespace Wave\Tests\Services\Database\User;

use PHPUnit\Framework\TestCase;
use Wave\Model\Contact\Contact;
use Wave\Model\Group\Group;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMinLength;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;
use Wave\Tests\Utilities\TestUtilities;
use Wave\Utilities\Utilities;

class UserTest extends TestCase {
  protected static DatabaseService $service;
  
  private static string $username = 'giacomo';
  private static string $password = 'Fr6/ese342f';
  private static string $source;
  private static string $token;
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== User ====================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$service = DatabaseService::getInstance();
    self::$source = Utilities::generateUuid();
    
    // ==== generate dummy user
    $generatedName = Utilities::generateString(12);
    $generatedSurname = Utilities::generateString();
    
    self::$service->createUser(
      self::$username,
      self::$password,
      $generatedName,
      $generatedSurname,
    );
    
    // ==== generate dummy token
    self::$token = self::$service->login(
      self::$username,
      self::$password,
      self::$source,
    )['token'];
  }
  
  // ==== createUser ===============================================================================
  // ===============================================================================================
  
  /**
   * @group createUser
   */
  public function testCreateUserCorrectCreation(): array {
    echo PHP_EOL . '==== createUser ==============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing user correct creation...' . PHP_EOL;
    
    $randomUsername = 'test_user';
    $generatedName = Utilities::generateString(12);
    $generatedSurname = Utilities::generateString();
    
    $result = self::$service->createUser(
      $randomUsername,
      self::$password,
      $generatedName,
      $generatedSurname,
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
      User::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateLanguage($result['language']),
    );
    
    return $result;
  }
  
  /**
   * @group createUser
   */
  public function testCreateUserUniqueConstrains(): array {
    echo PHP_EOL . 'Testing user unique constrains...' . PHP_EOL;
    
    $randomUsername = 'test_user';
    $generatedName = Utilities::generateString(12);
    $generatedSurname = Utilities::generateString();
    
    $result = self::$service->createUser(
      $randomUsername,
      self::$password,
      $generatedName,
      $generatedSurname,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      AlreadyExist::CODE,
      $result['error'],
    );
    
    DatabaseModule::execute(
      'DELETE FROM users
            WHERE username=:username',
      [
        ':username' => $randomUsername,
      ]
    );
    
    return $result;
  }
  
  // ==== getUserInformation =======================================================================
  // ===============================================================================================
  
  /**
   * @group getUserInformation
   */
  public function testGetUserInformationCorrectProcedure(): array {
    echo PHP_EOL . "Testing correct retrieve of the user's data..." . PHP_EOL;
    
    $result = self::$service->getUserInformation(self::$token);
    
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
      User::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateLanguage($result['language']),
    );
    
    return $result;
  }
  
  /**
   * @group getUserInformation
   */
  public function testGetUserInformationWithUnknownToken(): array {
    echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $generatedToken = Utilities::generateUuid();
    
    $result = self::$service->getUserInformation($generatedToken);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group getUserInformation
   */
  public function testGetUserInformationWithWrongToken(): array {
    echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = self::$service->getUserInformation($wrongToken);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== changeUserInformation ====================================================================
  // ===============================================================================================
  
  /**
   * @group changeUserInformation
   */
  public function testCorrectUserInformationChange(): array {
    echo PHP_EOL . "Testing correct user's information change..." . PHP_EOL;
    
    $newName = 'newName';
    $newSurname = 'New surname';
    $newTheme = 'D';
    
    $result = self::$service->changeUserInformation(
               self::$token,
      name   : $newName,
      surname: $newSurname,
      theme  : $newTheme
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
      User::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateLanguage($result['language']),
    );
    
    return $result;
  }
  
  /**
   * @group changeUserInformation
   */
  public function testChangeUserInformationWithoutParameters(): array {
    echo PHP_EOL . "Testing change user's information without parameters..." . PHP_EOL;
    
    $result = self::$service->changeUserInformation(self::$token);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NullAttributes::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group changeUserInformation
   */
  public function testChangeUserUsernameWithExistingOne(): array {
    echo PHP_EOL . "Testing change user's username with an existing one..." . PHP_EOL;
    
    $randomUsername = 'second_user';
    $generatedName = Utilities::generateString(12);
    $generatedSurname = Utilities::generateString();
    
    self::$service->createUser(
      $randomUsername,
      self::$password,
      $generatedName,
      $generatedSurname,
    );
    
    $result = self::$service->changeUserInformation(
                self::$token,
      username: $randomUsername,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      AlreadyExist::CODE,
      $result['error'],
    );
    
    DatabaseModule::execute(
      'DELETE FROM users
            WHERE username=:username',
      [
        ':username' => $randomUsername,
      ]
    );
    
    return $result;
  }
  
  /**
   * @group changeUserInformation
   */
  public function testChangeUserInformationWithWrongParameters(): array {
    echo PHP_EOL . "Testing change user's information without parameters..." . PHP_EOL;
    
    $wrongPhone = '1234';
    
    $result = self::$service->changeUserInformation(
             self::$token,
      phone: $wrongPhone,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      ExceedingMinLength::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  
  // ==== deleteUser ===============================================================================
  // ===============================================================================================
  
  /**
   * @group deleteUser
   */
  public function testDeleteUserCorrectProcedure(): ?array {
    echo PHP_EOL . "Testing correct user's deletion..." . PHP_EOL;
    
    $result = self::$service->deleteUser(self::$token);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
    
    return $result;
  }
  
  /**
   * @group deleteUser
   */
  public function testDeleteUserWithUnknownToken(): array {
    echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $generatedToken = Utilities::generateUuid();
    
    $result = self::$service->deleteUser($generatedToken);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group deleteUser
   */
  public function testDeleteUserWithWrongToken(): array {
    echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = self::$service->deleteUser($wrongToken);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group deleteUser
   */
  public function testDeleteUserProperty(): ?array {
    echo PHP_EOL . "Testing user's property deletion..." . PHP_EOL;
    
    // ==== Integration test preparation =======================================
    $firstUser = [
      'username' => 'first_user',
      'password' => 'Fr6/ese342f',
      'name'     => 'first',
      'surname'  => 'user',
      'source'   => null,
      'token'    => null,
    ];
    
    $secondUser = [
      'username' => 'second_user',
      'password' => 'Fr6/ese342f',
      'name'     => 'second',
      'surname'  => 'user',
      'source'   => null,
      'token'    => null,
    ];
    
    // ==== generate second user dummy source
    $firstUser['source'] = Utilities::generateUuid();
    
    // ==== generate first user dummy source
    $secondUser['source'] = Utilities::generateUuid();
    
    // ==== generate first dummy user
    self::$service->createUser(
      $firstUser['username'],
      $firstUser['password'],
      $firstUser['name'],
      $firstUser['surname'],
    );
    
    // ==== generate first dummy token
    $firstUser['token'] = self::$service->login(
      $firstUser['username'],
      $firstUser['password'],
      $firstUser['source'],
    )['token'];
    
    // ==== generate second dummy user
    self::$service->createUser(
      $secondUser['username'],
      $secondUser['password'],
      $secondUser['name'],
      $secondUser['surname'],
    );
    
    // ==== generate second dummy token
    $secondUser['token'] = self::$service->login(
      $secondUser['username'],
      $secondUser['password'],
      $secondUser['source'],
    )['token'];
    
    // ==== Integration test execution =========================================
    
    // contact request
    $result = self::$service->contactRequest(
      $firstUser['token'],
      "insomnia_agent",
    );
    
    $result["picture"] = substr($result['picture'], 0, 50) . "...";
    
    echo 'Contact request result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
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
    
    // accept contact
    self::$service->contactRequest(
      $firstUser['token'],
      $secondUser['username'],
    );
    
    $result = self::$service->changeContactStatus(
      $secondUser['token'],
      $firstUser['username'],
      'A'
    );
    
    echo 'Contact accept result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
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
    
    self::assertTrue(true);
    
    // create group with member
    $randomGroupName = "test_group";
    $generatedInfo = Utilities::generateString(22);
    
    $result = self::$service->createGroup(
      $firstUser['token'],
      $randomGroupName,
      $generatedInfo,
      null,
      [
        $secondUser['username'],
      ]
    );
    
    echo 'Create group with member result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
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
    
    // create group alone
    $result = self::$service->createGroup(
      $firstUser['token'],
      $randomGroupName,
      $generatedInfo,
    );
    
    echo 'Create group alone result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
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
    
    // delete user
    $result = self::$service->deleteUser($firstUser['token']);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
    
    // ==== Integration test clean up ==========================================
    
    TestUtilities::deleteGeneratedTables($firstUser['username'], true);
    TestUtilities::deleteGeneratedTables($firstUser['username']);
    
    DatabaseModule::execute(
      'DELETE FROM `groups`
             WHERE name = :group_name',
      [
        ':group_name' => $randomGroupName,
      ]
    );
    
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username = :first_username
                OR username = :second_username',
      [
        ':first_username'  => $firstUser['username'],
        ':second_username' => $secondUser['username'],
      ]
    );
    
    return $result;
  }
  
  
  public static function tearDownAfterClass(): void {
    DatabaseModule::execute(
      'DELETE FROM users
            WHERE username=:username',
      [
        ':username' => self::$username,
      ]
    );
  }
}