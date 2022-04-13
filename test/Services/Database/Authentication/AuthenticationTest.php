<?php

namespace Wave\Tests\Services\Database\Authentication;

use PHPUnit\Framework\TestCase;
use Wave\Model\Session\Session;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\IncorrectPattern;
use Wave\Utilities\Utilities;

class AuthenticationTest extends TestCase {
  private static DatabaseService $service;
  
  private static string $username = 'giacomo';
  private static string $password = 'Fr6/ese342f';
  private static string $source;
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== Authentication ==========================================' . PHP_EOL
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
  }
  
  // ==== login ====================================================================================
  // ===============================================================================================
  
  /**
   * @group login
   */
  public function testLoginCorrectProcedure(): array {
    echo PHP_EOL . '==== login ===================================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct login procedure...' . PHP_EOL;
    
    $result = self::$service->login(
      self::$username,
      self::$password,
      self::$source,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Session::validateToken($result['token']),
    );
    
    return $result;
  }
  
  /**
   * @group   login
   * @depends testLoginCorrectProcedure
   */
  public function testLoginWithActiveSession(): array {
    echo PHP_EOL . 'Testing login with active session...' . PHP_EOL;
    
    $result = self::$service->login(
      self::$username,
      self::$password,
      self::$source,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Session::validateToken($result['token']),
    );
    return $result;
  }
  
  /**
   * @group login
   */
  public function testLoginWithIncorrectPassword(): array {
    echo PHP_EOL . 'Testing login with incorrect password...' . PHP_EOL;
    
    $randomPassword = 'Ft5/dg3D5gs$s';
    
    $result = self::$service->login(
      self::$username,
      $randomPassword,
      self::$source,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group login
   */
  public function testLoginWithUnknownUsername(): array {
    echo PHP_EOL . 'Testing login with unknown username...' . PHP_EOL;
    
    $randomUsername = 'random_user';
    
    $result = self::$service->login(
      $randomUsername,
      self::$password,
      self::$source,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== poke =====================================================================================
  // ===============================================================================================
  
  /**
   * @group poke
   */
  public function testPokeCorrectProcedure(): void {
    echo PHP_EOL . '==== poke ====================================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct poke procedure...' . PHP_EOL;
    
    $token = self::$service->login(
      self::$username,
      self::$password,
      self::$source,
    )['token'];
    
    $result = self::$service->poke(
      $token,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
  }
  
  /**
   * @group poke
   */
  public function testPokeWithUnknownToken(): void {
    echo PHP_EOL . 'Testing poke with unknown token...' . PHP_EOL;
    
    $generatedToken = Utilities::generateUuid();
    
    $result = self::$service->poke(
      $generatedToken,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
  }
  
  /**
   * @group poke
   */
  public function testPokeWithWrongToken(): void {
    echo PHP_EOL . 'Testing poke with wrong token...' . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = self::$service->poke(
      $wrongToken,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
  }
  
  // ==== logout ===================================================================================
  // ===============================================================================================
  
  /**
   * @group logout
   */
  public function testLogoutCorrectProcedure(): void {
    echo PHP_EOL . '==== logout ==================================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing correct logout procedure...' . PHP_EOL;
    
    $token = self::$service->login(
      self::$username,
      self::$password,
      self::$source,
    )['token'];
    
    $result = self::$service->logout(
      $token,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
  }
  
  /**
   * @group logout
   */
  public function testLogoutWithUnknownToken(): void {
    echo PHP_EOL . 'Testing token with unknown token...' . PHP_EOL;
    
    $generatedToken = Utilities::generateUuid();
    
    $result = self::$service->logout(
      $generatedToken,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
  }
  
  /**
   * @group logout
   */
  public function testLogoutWithWrongToken(): void {
    echo PHP_EOL . 'Testing logout with wrong token...' . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = self::$service->logout(
      $wrongToken,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
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