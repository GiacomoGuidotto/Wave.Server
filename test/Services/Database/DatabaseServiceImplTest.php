<?php

namespace Services\Database;

use PHPUnit\Framework\TestCase;
use Wave\Model\Session\SessionImpl;
use Wave\Model\User\UserImpl;
use Wave\Services\Database\DatabaseServiceImpl;
use Wave\Services\Database\Module\Module;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\Success\Success;

class DatabaseServiceImplTest extends TestCase {
  
  protected DatabaseServiceImpl $service;
  
  protected function setUp(): void {
    $this->service = DatabaseServiceImpl::getInstance();
  }
  
  // ==== Utility methods ==========================================================================
  // ==== Set of private methods ===================================================================
  // ===============================================================================================
  
  protected string $validPassword = 'Fr6/ese342f';
  
  /**
   * Generate a random string for attribute testing
   *
   * @param int $length The optional string length
   * @return string     The generated string
   */
  private function generateString(int $length = 8): string {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
  }
  
  /**
   * Generate a version 4 uuid
   *
   * @return string The uuid
   */
  private function generateUuid(): string {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      
      // 32 bits for "time_low"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      
      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),
      
      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,
      
      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,
      
      // 48 bits for "node"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
  
  // ==== Service ==================================================================================
  // ==== Test all the possible use cases ==========================================================
  // ===============================================================================================
  
  public function testService(): void {
    $this->testAuthenticationCases();
    $this->testUserCases();
  }
  
  // ==== Authentication ===========================================================================
  // ==== Use cases related to the authentication process ==========================================
  // ===============================================================================================
  
  public function testAuthenticationCases(bool $mute = false): void {
    echo '==== Authentication ==========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    $this->testLogin($mute);
  }
  
  // ==== Login ====================================================================================
  // ===============================================================================================
  
  public function testLogin(bool $mute = false): void {
    echo PHP_EOL . '==== login ===================================================' . PHP_EOL;
    $groupUsername = 'giacomo';
    $groupSource = $this->generateUuid();
    
    // prepare tests
    $this->testCreateUserCorrectCreation($groupUsername, true);
    
    // execute group
    $this->testLoginCorrectProcedure($groupUsername, $groupSource, $mute);
    $this->testLoginWithActiveSession($groupUsername, $groupSource, $mute);
    $this->testLoginWithIncorrectPassword($groupUsername, $groupSource, $mute);
    $this->testLoginWithUnknownUsername($groupSource, $mute);
    
    // clear tests
    $this->clearLoginModifications($groupUsername);
  }
  
  // =================================================================
  
  private function testLoginCorrectProcedure(
    string $groupUsername,
    string $groupSource,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing correct login procedure...' . PHP_EOL;
    
    $result = $this->service->login(
      $groupUsername,
      $this->validPassword,
      $groupSource,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      SessionImpl::validateToken($result['token']),
    );
  }
  
  private function testLoginWithActiveSession(
    string $groupUsername,
    string $groupSource,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing login with active session...' . PHP_EOL;
    
    $result = $this->service->login(
      $groupUsername,
      $this->validPassword,
      $groupSource,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      SessionImpl::validateToken($result['token']),
    );
  }
  
  private function testLoginWithIncorrectPassword(
    string $groupUsername,
    string $groupSource,
    bool   $mute = false,
  ) {
    if (!$mute) echo PHP_EOL . 'Testing login with incorrect password...' . PHP_EOL;
    
    $randomPassword = 'Ft5/dg3D5gs$s';
    
    $result = $this->service->login(
      $groupUsername,
      $randomPassword,
      $groupSource,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
  }
  
  private function testLoginWithUnknownUsername(
    string $groupSource,
    bool   $mute = false,
  ) {
    if (!$mute) echo PHP_EOL . 'Testing login with unknown username...' . PHP_EOL;
    
    $randomUsername = 'random_user';
    
    $result = $this->service->login(
      $randomUsername,
      $this->validPassword,
      $groupSource,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NotFound::CODE,
      $result['error'],
    );
  }
  
  private function clearLoginModifications(
    string $groupUsername,
  ) {
    $this->clearCreateUserModification($groupUsername);
  }
  
  // ==== User =====================================================================================
  // ==== Use cases related to the user management =================================================
  // ===============================================================================================
  
  public function testUserCases(bool $mute = false): void {
    echo PHP_EOL . PHP_EOL . '==== User ====================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    $this->testCreateUser($mute);
  }
  
  // ==== createUser ===============================================================================
  // ===============================================================================================
  
  public function testCreateUser(bool $mute = false): void {
    echo PHP_EOL . '==== createUser ==============================================' . PHP_EOL;
    $groupUsername = 'giacomo';
    
    // execute group
    $this->testCreateUserCorrectCreation($groupUsername, $mute);
    $this->testCreateUserUniqueConstrains($groupUsername, $mute);
    
    // clear tests
    $this->clearCreateUserModification($groupUsername);
  }
  
  // =================================================================
  
  private function testCreateUserCorrectCreation(
    string $groupUsername,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing user correct creation...' . PHP_EOL;
    
    $generatedName = $this->generateString(12);
    $generatedSurname = $this->generateString();
    
    $result = $this->service->createUser(
      $groupUsername,
      $this->validPassword,
      $generatedName,
      $generatedSurname,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
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
  }
  
  private function testCreateUserUniqueConstrains(
    string $groupUsername,
    bool   $mute = false,
  ) {
    if (!$mute) echo PHP_EOL . 'Testing user unique constrains...' . PHP_EOL;
    
    $generatedName = $this->generateString(12);
    $generatedSurname = $this->generateString();
    
    $result = $this->service->createUser(
      $groupUsername,
      $this->validPassword,
      $generatedName,
      $generatedSurname,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      AlreadyExist::CODE,
      $result['error'],
    );
  }
  
  private function clearCreateUserModification(
    string $groupUsername,
  ): void {
    Module::execute(
      'DELETE FROM users
            WHERE username=:username',
      [
        ':username' => $groupUsername,
      ]
    );
  }
}
