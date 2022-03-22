<?php

namespace Wave\Services\Database;

use DateInterval;
use JetBrains\PhpStorm\ArrayShape;
use Wave\Model\Singleton\Singleton;
use Wave\Model\User\UserImpl;
use Wave\Services\Database\Module\Module;
use Wave\Specifications\ErrorCases\ErrorCases;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\Timeout;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\Wave\Wave;

/**
 * Database service class
 * The implementation of the DatabaseService interface made for the MySQL database
 */
class DatabaseServiceImpl extends Singleton implements DatabaseService {
  
  
  // ==== Utility methods ==========================================================================
  // ==== Set of private methods ===================================================================
  
  /**
   * Generate the error message associate in the error cases, given the code
   *
   * @param int $code The error code
   * @return array    The object as array of the error message
   */
  #[ArrayShape([
    'timestamp' => "string",
    'error'     => "int",
    'message'   => "string",
    'details'   => "string",
  ])] public function generateErrorMessage(int $code): array {
    return [
      'timestamp' => date('Y-m-d H:i:s'),
      'error'     => $code,
      'message'   => ErrorCases::ERROR_MESSAGES[$code],
      'details'   => ErrorCases::ERROR_DETAILS[$code],
    ];
  }
  
  
  /**
   * Calculate the time different between two date in full-time format
   *
   * @param string $initialTime The first time in chronological order
   * @param string $finalTime   The second time in chronological order
   * @return DateInterval       The time difference in full-time format
   */
  private function timeDifference(
    string $initialTime,
    string $finalTime,
  ): DateInterval {
    $finalDate = date_create($finalTime);
    $initialDate = date_create($initialTime);
    
    return $finalDate->diff($initialDate, true);
  }
  
  /**
   * Internal check for validating if the last call from this session is not old enough for the
   * Specification-defined SESSION_DURATION
   *
   * @param string $currentTimestamp The actual timestamp
   * @param string $last_updated     The timestamp of the last call
   * @return bool                    The validation, true if the call didn't exceeded the
   *                                 SESSION_DURATION
   * @see Wave::SESSION_DURATION
   */
  private function validateTimeout(
    string $currentTimestamp,
    string $last_updated
  ): bool {
    // Convert the TTL string in DateTime
    $timeToLive = date_create('midnight')
      ->add(DateInterval::createFromDateString(
        Wave::SESSION_DURATION
      ));
    // Generate the difference between now and the moment of the last call in DateTime
    $timeDifference = date_create('midnight')
      ->add($this->timeDifference(
        $currentTimestamp,
        $last_updated
      ));
    // Validate
    return $timeDifference < $timeToLive;
  }
  
  /**
   * Validate the existence of the token.
   *
   *
   * Also, update the last_updated attribute, if it didn't exceeded the SESSION_DURATION
   *
   * @param string $token The token to verify
   * @return int          Either the error code or the success code
   */
  private function authorizeToken(string $token): int {
    // =======================================
    Module::beginTransaction();
    
    // ==== Find token =======================
    $token_row = Module::fetchOne(
      'SELECT last_updated
             FROM sessions
             WHERE session_token = :session_token
               AND active = TRUE',
      [
        ':session_token' => $token,
      ]
    );
    
    if ($token_row === false) {
      Module::commitTransaction();
      return Unauthorized::CODE;
    }
    
    // ==== Update session TTL ===============
    $last_updated = $token_row['last_updated'];
    
    $current_timestamp = Module::fetchOne(
      'SELECT CURRENT_TIMESTAMP()'
    )['CURRENT_TIMESTAMP()'];
    
    if (!$this->validateTimeout($current_timestamp, $last_updated)) {
      // ==== If timeout =======================
      Module::execute(
        'UPDATE sessions
               SET active = FALSE
               WHERE session_token = :session_token',
        [
          ':session_token' => $token,
        ]
      );
      
      Module::commitTransaction();
      return Timeout::CODE;
    }
    
    Module::execute(
      'UPDATE sessions
             SET last_updated = CURRENT_TIMESTAMP()
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    );
    
    Module::commitTransaction();
    return Success::CODE;
  }
  
  // ==== Authentication ===========================================================================
  // ==== Use cases related to the authentication process ==========================================
  
  /**
   * @inheritDoc
   */
  public function login(
    string $username,
    string $password,
    string $device,
  ): array {
    // TODO: Implement login() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function poke(
    string $token,
  ): array|null {
    // TODO: Implement poke() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function logout(
    string $token,
  ): array|null {
    // TODO: Implement logout() method.
    return [];
  }
  
  // ==== User =====================================================================================
  // ==== Use cases related to the user management =================================================
  
  /**
   * @inheritDoc
   */
  public function createUser(
    string  $username,
    string  $password,
    string  $name,
    string  $surname,
    ?string $phone,
    ?string $picture,
  ): array {
    $usernameValidation = UserImpl::validateUsername($username);
    $passwordValidation = UserImpl::validatePassword($password);
    $nameValidation = UserImpl::validateName($name);
    $surnameValidation = UserImpl::validateSurname($surname);
    
    if ($usernameValidation != Success::CODE) {
      return $this->generateErrorMessage($usernameValidation);
    }
    if ($passwordValidation != Success::CODE) {
      return $this->generateErrorMessage($passwordValidation);
    }
    if ($nameValidation != Success::CODE) {
      return $this->generateErrorMessage($nameValidation);
    }
    if ($surnameValidation != Success::CODE) {
      return $this->generateErrorMessage($surnameValidation);
    }
    
    if ($phone != null) {
      $phoneValidation = UserImpl::validatePhone($phone);
      
      if ($phoneValidation != Success::CODE) {
        return $this->generateErrorMessage($phoneValidation);
      }
    }
    
    if ($picture != null) {
      // TODO safe picture in fs
      $pictureValidation = UserImpl::validatePicture($picture);
      
      if ($pictureValidation != Success::CODE) {
        return $this->generateErrorMessage($pictureValidation);
      }
    }
    
    // =======================================
    Module::beginTransaction();
    
    // ==== Already exist checks =============
    $user = Module::fetchOne(
      'SELECT username, name
             FROM users
             WHERE username = BINARY :username AND active = TRUE',
      [
        ':username' => $username,
      ]
    );
    
    if ($user) {
      Module::commitTransaction();
      return $this->generateErrorMessage(AlreadyExist::CODE);
    }
    
    // ==== Securing password ================
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // ==== Insert query =====================
    Module::execute(
      'INSERT
            INTO users (username, password, name, surname, picture, phone, active)
            VALUES (
                :username,
                :password,
                :name,
                :surname,
                :picture,
                :phone,
                :active
            )',
      [
        ':username' => $username,
        ':password' => $hashedPassword,
        ':name'     => $name,
        ':surname'  => $surname,
        ':picture'  => $picture,
        ':phone'    => $phone,
        ':active'   => true,
      ]
    );
    
    Module::commitTransaction();
    return [
      'username' => $username,
      'name'     => $name,
      'surname'  => $surname,
      'picture'  => $picture,
      'phone'    => $phone,
      'theme'    => 'L',
      'language' => 'EN',
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function getUserInformation(
    string $token,
  ): array {
    // TODO: Implement getUserInformation() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeUserInformation(
    string  $token,
    ?string $username,
    ?string $name,
    ?string $surname,
    ?string $phone,
    ?string $picture,
    ?string $theme,
    ?string $language,
  ): array {
    // TODO: Implement changeUserInformation() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function deleteUser(
    string $token,
  ): array|null {
    // TODO: Implement deleteUser() method.
    return [];
  }
  
  // ==== Contact ==================================================================================
  // ==== Use cases related to the contacts management =============================================
  
  /**
   * @inheritDoc
   */
  public function contactRequest(
    string $token,
    string $user,
  ): array {
    // TODO: Implement contactRequest() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function deleteContactRequest(
    string $token,
    string $user,
  ): array|null {
    // TODO: Implement deleteContactRequest() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeContactStatus(
    string $token,
    string $user,
    string $directive,
  ): array {
    // TODO: Implement changeContactStatus() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function getContactInformation(
    string  $token,
    ?string $user,
  ): array {
    // TODO: Implement getContactInformation() method.
    return [];
  }
  
  // ==== Group ====================================================================================
  // ==== Use cases related to the groups management ===============================================
  
  /**
   * @inheritDoc
   */
  public function createGroup(
    string  $token,
    string  $name,
    ?string $info,
    ?string $picture,
    ?array  $users,
  ): array {
    // TODO: Implement createGroup() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function getGroupInformation(
    string  $token,
    ?string $group,
  ): array {
    // TODO: Implement getGroupInformation() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeGroupStatus(
    string $token,
    string $group,
    string $directive,
  ): array {
    // TODO: Implement changeGroupStatus() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeGroupInformation(
    string  $token,
    string  $group,
    ?string $name,
    ?string $info,
    ?string $picture,
  ): array {
    // TODO: Implement changeGroupInformation() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function exitGroup(
    string $token,
    string $group,
  ): array {
    // TODO: Implement exitGroup() method.
    return [];
  }
  
  // ==== Member ===================================================================================
  
  /**
   * @inheritDoc
   */
  public function addMember(
    string $token,
    string $group,
    string $user,
  ): array {
    // TODO: Implement addMember() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function getMemberList(
    string  $token,
    string  $group,
    ?string $user,
  ): array {
    // TODO: Implement getMemberList() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeMemberPermission(
    string $token,
    string $group,
    string $user,
    string $permission,
  ): array {
    // TODO: Implement changeMemberPermission() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function removeMember(
    string $token,
    string $group,
    string $user,
  ): array {
    // TODO: Implement removeMember() method.
    return [];
  }
  
  // ==== Message ==================================================================================
  // ==== Use cases related to the messages management =============================================
  
  /**
   * @inheritDoc
   */
  public function getMessages(
    string  $token,
    ?string $group,
    ?string $contact,
    ?string $from,
    ?string $to,
    ?bool   $pinned,
    ?string $message,
  ): array {
    // TODO: Implement getMessages() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function writeMessage(
    string  $token,
    ?string $group,
    ?string $contact,
    string  $content,
    ?string $text,
    ?string $media,
  ): array {
    // TODO: Implement writeMessage() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeMessage(
    string  $token,
    ?string $group,
    ?string $contact,
    string  $message,
    ?string $content,
    ?string $text,
    ?string $media,
    ?bool   $pinned,
  ): array {
    // TODO: Implement changeMessage() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function deleteMessage(
    string  $token,
    ?string $group,
    ?string $contact,
    string  $message,
  ): array|null {
    // TODO: Implement deleteMessage() method.
    return [];
  }
}