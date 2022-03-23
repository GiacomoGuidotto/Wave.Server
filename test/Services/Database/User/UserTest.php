<?php

namespace Services\Database\User;

use PHPUnit\Framework\TestCase;
use Wave\Model\User\UserImpl;
use Wave\Services\Database\DatabaseServiceImpl;
use Wave\Services\Database\Module\Module;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\String\ExceedingMinLength;
use Wave\Specifications\ErrorCases\String\IncorrectPattern;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Utilities\Utilities;

class UserTest extends TestCase {
  protected static DatabaseServiceImpl $service;
  
  private static string $username = 'giacomo';
  private static string $password = 'Fr6/ese342f';
  private static string $source;
  private static string $token;
  
  public static function setUpBeforeClass(): void {
    echo '==== User ====================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$service = DatabaseServiceImpl::getInstance();
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
      UserImpl::validateUsername($result['username']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateName($result['name']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateSurname($result['surname']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateLanguage($result['language']),
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
    
    Module::execute(
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
  public function testGetUserInformationCorrectProcedure() {
    echo PHP_EOL . "Testing correct retrieve of the user's data..." . PHP_EOL;
    
    $result = self::$service->getUserInformation(self::$token);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      UserImpl::validateUsername($result['username']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateName($result['name']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateSurname($result['surname']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateLanguage($result['language']),
    );
  }
  
  /**
   * @group getUserInformation
   */
  public function testGetUserInformationWithUnknownToken(
    bool $mute = false,
  ) {
    echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $generatedToken = Utilities::generateUuid();
    
    $result = self::$service->getUserInformation($generatedToken);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
  }
  
  /**
   * @group getUserInformation
   */
  public function testGetUserInformationWithWrongToken() {
    echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = self::$service->getUserInformation($wrongToken);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
  }
  
  // ==== changeUserInformation ====================================================================
  // ===============================================================================================
  
  /**
   * @group changeUserInformation
   */
  public function testCorrectUserInformationChange(): void {
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
      UserImpl::validateUsername($result['username']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateName($result['name']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateSurname($result['surname']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateLanguage($result['language']),
    );
  }
  
  /**
   * @group changeUserInformation
   */
  public function testChangeUserInformationWithoutParameters(): void {
    echo PHP_EOL . "Testing change user's information without parameters..." . PHP_EOL;
    
    $result = self::$service->changeUserInformation(self::$token);
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NullAttributes::CODE,
      $result['error'],
    );
  }
  
  /**
   * @group changeUserInformation
   */
  public function testChangeUserUsernameWithExistingOne(): void {
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
    
    Module::execute(
      'DELETE FROM users
            WHERE username=:username',
      [
        ':username' => $randomUsername,
      ]
    );
  }
  
  /**
   * @group changeUserInformation
   */
  public function testChangeUserInformationWithWrongParameters(): void {
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
  }
  
  public static function tearDownAfterClass(): void {
    Module::execute(
      'DELETE FROM users
            WHERE username=:username',
      [
        ':username' => self::$username,
      ]
    );
  }
}