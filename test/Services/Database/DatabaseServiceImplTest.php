<?php

namespace Services\Database;

use PHPUnit\Framework\TestCase;
use Wave\Model\Session\SessionImpl;
use Wave\Model\User\UserImpl;
use Wave\Services\Database\DatabaseServiceImpl;
use Wave\Services\Database\Module\Module;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\String\ExceedingMinLength;
use Wave\Specifications\ErrorCases\String\IncorrectPattern;
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
    if (!$mute) echo '==== Authentication ==========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    $this->testLogin($mute);
    $this->testPoke($mute);
    $this->testLogout($mute);
  }
  
  // ==== login ====================================================================================
  // ===============================================================================================
  
  public function testLogin(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . '==== login ===================================================' . PHP_EOL;
    
    // prepare tests
    $groupUsername = 'giacomo';
    $groupSource = $this->generateUuid();
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
  ): ?string {
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
    
    return $mute ? $result['token'] : null;
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
  ): void {
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
  ): void {
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
  ): void {
    $this->clearCreateUserModification($groupUsername);
  }
  
  // ==== poke =====================================================================================
  // ===============================================================================================
  
  public function testPoke(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . '==== poke ====================================================' . PHP_EOL;
    
    // prepare tests
    $groupUsername = 'giacomo';
    $groupSource = $this->generateUuid();
    $this->testCreateUserCorrectCreation($groupUsername, true);
    $groupToken = $this->testLoginCorrectProcedure($groupUsername, $groupSource, true);
    
    // execute group
    $this->testPokeCorrectProcedure($groupToken, $mute);
    $this->testPokeWithUnknownToken($mute);
    $this->testPokeWithWrongToken($mute);
    
    // clear tests
    $this->clearPokeModifications($groupUsername);
  }
  
  private function testPokeCorrectProcedure(
    string $groupToken,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing correct poke procedure...' . PHP_EOL;
    
    
    $result = $this->service->poke(
      $groupToken,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
  }
  
  private function testPokeWithUnknownToken(
    bool $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing poke with unknown token...' . PHP_EOL;
    
    $generatedToken = $this->generateUuid();
    
    $result = $this->service->poke(
      $generatedToken,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
  }
  
  private function testPokeWithWrongToken(
    bool $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing poke with wrong token...' . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = $this->service->poke(
      $wrongToken,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
  }
  
  private function clearPokeModifications(
    string $groupUsername,
  ): void {
    $this->clearCreateUserModification($groupUsername);
  }
  
  // ==== logout ===================================================================================
  // ===============================================================================================
  
  public function testLogout(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . '==== logout ==================================================' . PHP_EOL;
    
    // prepare tests
    $groupUsername = 'giacomo';
    $groupSource = $this->generateUuid();
    $this->testCreateUserCorrectCreation($groupUsername, true);
    $groupToken = $this->testLoginCorrectProcedure($groupUsername, $groupSource, true);
    
    // execute group
    $this->testLogoutCorrectProcedure($groupToken, $mute);
    $this->testLogoutWithUnknownToken($mute);
    $this->testLogoutWithWrongToken($mute);
    
    // clear tests
    $this->clearLogoutModifications($groupUsername);
  }
  
  private function testLogoutCorrectProcedure(
    string $groupToken,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing correct logout procedure...' . PHP_EOL;
    
    $result = $this->service->logout(
      $groupToken,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
  }
  
  private function testLogoutWithUnknownToken(
    bool $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing token with unknown token...' . PHP_EOL;
    
    $generatedToken = $this->generateUuid();
    
    $result = $this->service->logout(
      $generatedToken,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
  }
  
  private function testLogoutWithWrongToken(
    bool $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . 'Testing logout with wrong token...' . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = $this->service->logout(
      $wrongToken,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
  }
  
  private function clearLogoutModifications(
    string $groupUsername,
  ): void {
    $this->clearCreateUserModification($groupUsername);
  }
  
  // ==== User =====================================================================================
  // ==== Use cases related to the user management =================================================
  // ===============================================================================================
  
  public function testUserCases(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . PHP_EOL . '==== User ====================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    $this->testCreateUser($mute);
    $this->testGetUserInformation($mute);
    $this->testChangeUserInformation($mute);
  }
  
  // ==== createUser ===============================================================================
  // ===============================================================================================
  
  public function testCreateUser(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . '==== createUser ==============================================' . PHP_EOL;
    
    // execute group
    $groupUsername = 'giacomo';
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
    self::assertEquals(
      Success::CODE,
      UserImpl::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateLanguage($result['language']),
    );
  }
  
  private function testCreateUserUniqueConstrains(
    string $groupUsername,
    bool   $mute = false,
  ): void {
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
  
  // ==== getUserInformation =======================================================================
  // ===============================================================================================
  
  public function testGetUserInformation(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . '==== getUserInformation ======================================' . PHP_EOL;
    
    // prepare tests
    $groupUsername = 'giacomo';
    $groupSource = $this->generateUuid();
    $this->testCreateUserCorrectCreation($groupUsername, true);
    $groupToken = $this->testLoginCorrectProcedure($groupUsername, $groupSource, true);
    
    // execute group
    $this->testGetUserInformationCorrectProcedure($groupToken, $mute);
    $this->testGetUserInformationWithUnknownToken($mute);
    $this->testGetUserInformationWithWrongToken($mute);
    
    // clear tests
    $this->clearGetUserInformationModifications($groupUsername);
  }
  
  private function testGetUserInformationCorrectProcedure(
    string $groupToken,
    bool   $mute = false,
  ) {
    if (!$mute) echo PHP_EOL . "Testing correct retrieve of the user's data..." . PHP_EOL;
    
    $result = $this->service->getUserInformation($groupToken);
    
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
    self::assertEquals(
      Success::CODE,
      UserImpl::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateLanguage($result['language']),
    );
  }
  
  private function testGetUserInformationWithUnknownToken(
    bool $mute = false,
  ) {
    if (!$mute) echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $generatedToken = $this->generateUuid();
    
    $result = $this->service->getUserInformation($generatedToken);
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Unauthorized::CODE,
      $result['error'],
    );
  }
  
  private function testGetUserInformationWithWrongToken(
    bool $mute = false,
  ) {
    if (!$mute) echo PHP_EOL . "Testing retrieve of the user's with unknown token..." . PHP_EOL;
    
    $wrongToken = 'tokentokentokentokentokentokentokent';
    
    $result = $this->service->getUserInformation($wrongToken);
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectPattern::CODE,
      $result['error'],
    );
  }
  
  private function clearGetUserInformationModifications(
    string $groupUsername,
  ): void {
    $this->clearCreateUserModification($groupUsername);
  }
  
  // ==== changeUserInformation ====================================================================
  // ===============================================================================================
  
  public function testChangeUserInformation(bool $mute = false): void {
    if (!$mute) echo PHP_EOL . '==== changeUserInformation ======================================' . PHP_EOL;
    
    // prepare tests
    $groupUsername = 'giacomo';
    $groupSource = $this->generateUuid();
    $this->testCreateUserCorrectCreation($groupUsername, true);
    $groupToken = $this->testLoginCorrectProcedure($groupUsername, $groupSource, true);
    
    // execute group
    $this->testCorrectUserInformationChange($groupToken, $mute);
    $this->testChangeUserInformationWithoutParameters($groupToken, $mute);
    $this->testChangeUserUsernameWithExistingOne($groupToken, $mute);
    $this->testChangeUserInformationWithWrongParameters($groupToken, $mute);
    
    // clear tests
    $this->clearChangeUserInformationModifications($groupUsername);
  }
  
  private function testCorrectUserInformationChange(
    string $groupToken,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . "Testing correct user's information change..." . PHP_EOL;
    
    $newName = 'newName';
    $newSurname = 'New surname';
    $newTheme = 'D';
    
    $result = $this->service->changeUserInformation(
               $groupToken,
      name   : $newName,
      surname: $newSurname,
      theme  : $newTheme
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
    self::assertEquals(
      Success::CODE,
      UserImpl::validateTheme($result['theme']),
    );
    self::assertEquals(
      Success::CODE,
      UserImpl::validateLanguage($result['language']),
    );
  }
  
  private function testChangeUserInformationWithoutParameters(
    string $groupToken,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . "Testing change user's information without parameters..." . PHP_EOL;
    
    $result = $this->service->changeUserInformation($groupToken);
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NullAttributes::CODE,
      $result['error'],
    );
  }
  
  private function testChangeUserUsernameWithExistingOne(
    string $groupToken,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . "Testing change user's username with an existing one..." . PHP_EOL;
    
    $randomUsername = 'second_user';
    $generatedName = $this->generateString(12);
    $generatedSurname = $this->generateString();
    
    $this->service->createUser(
      $randomUsername,
      $this->validPassword,
      $generatedName,
      $generatedSurname,
    );
    
    $result = $this->service->changeUserInformation($groupToken, username: $randomUsername);
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
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
  
  private function testChangeUserInformationWithWrongParameters(
    string $groupToken,
    bool   $mute = false,
  ): void {
    if (!$mute) echo PHP_EOL . "Testing change user's information without parameters..." . PHP_EOL;
    
    $wrongPhone = '1234';
    
    $result = $this->service->changeUserInformation(
             $groupToken,
      phone: $wrongPhone,
    );
    
    if (!$mute) echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      ExceedingMinLength::CODE,
      $result['error'],
    );
  }
  
  private function clearChangeUserInformationModifications(
    string $groupUsername,
  ): void {
    $this->clearCreateUserModification($groupUsername);
  }
}
