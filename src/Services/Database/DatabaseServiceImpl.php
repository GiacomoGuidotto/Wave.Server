<?php

namespace Wave\Services\Database;

use DateInterval;
use JetBrains\PhpStorm\ArrayShape;
use Wave\Model\Session\SessionImpl;
use Wave\Model\Singleton\Singleton;
use Wave\Model\User\UserImpl;
use Wave\Services\Database\Module\Module;
use Wave\Specifications\ErrorCases\ErrorCases;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\NotFound;
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
      ->add(
        DateInterval::createFromDateString(
          Wave::SESSION_DURATION
        )
      );
    // Generate the difference between now and the moment of the last call in DateTime
    $timeDifference = date_create('midnight')
      ->add(
        $this->timeDifference(
          $currentTimestamp,
          $last_updated
        )
      );
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
    string $source,
  ): array {
    $usernameValidation = UserImpl::validateUsername($username);
    $passwordValidation = UserImpl::validatePassword($password);
    $deviceValidation = SessionImpl::validateSource($source);
    
    if ($usernameValidation != Success::CODE) {
      return $this->generateErrorMessage($usernameValidation);
    }
    if ($passwordValidation != Success::CODE) {
      return $this->generateErrorMessage($passwordValidation);
    }
    if ($deviceValidation != Success::CODE) {
      return $this->generateErrorMessage($deviceValidation);
    }
    
    // =======================================
    Module::beginTransaction();
    
    // ==== correct username and password ====
    $storedPasswordRow = Module::fetchOne(
      'SELECT user_id, password
            FROM users
            WHERE username = BINARY :username
              AND active = TRUE',
      [
        ':username' => $username,
      ]
    );
    
    // ==== username not found ===============
    if ($storedPasswordRow === false) {
      Module::commitTransaction();
      return $this->generateErrorMessage(NotFound::CODE);
    }
    
    // ==== password incorrect ===============
    $storedPassword = $storedPasswordRow['password'];
    if (!password_verify($password, $storedPassword)) {
      Module::commitTransaction();
      return $this->generateErrorMessage(NotFound::CODE);
    }
    
    // ==== delete token already existing ====
    $userId = $storedPasswordRow['user_id'];
    Module::execute(
      'UPDATE sessions
            SET active = FALSE
            WHERE user = :user_id
              AND source = :source',
      [
        ':user_id' => $userId,
        ':source'  => $source,
      ]
    );
    
    // ==== create token =====================
    Module::execute(
      'INSERT
            INTO sessions (session_token, source, user, creation_timestamp, last_updated, active)
            VALUES (
                    UUID(),
                    :source,
                    :user_id,
                    CURRENT_TIMESTAMP(),
                    CURRENT_TIMESTAMP(),
                    :active
            )',
      [
        ':source'  => $source,
        ':user_id' => $userId,
        'active'   => true,
      ]
    );
    
    $token = Module::fetchOne(
      'SELECT session_token
            FROM sessions
            WHERE session_id = LAST_INSERT_ID()'
    )['session_token'];
    
    Module::commitTransaction();
    return [
      'token' => $token,
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function poke(
    string $token,
  ): ?array {
    $tokenValidation = SessionImpl::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return $this->generateErrorMessage($tokenValidation);
    }
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return $this->generateErrorMessage($tokenAuthorization);
    }
    
    return null;
  }
  
  /**
   * @inheritDoc
   */
  public function logout(
    string $token,
  ): ?array {
    $tokenValidation = SessionImpl::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return $this->generateErrorMessage($tokenValidation);
    }
    
    // =======================================
    Module::beginTransaction();
    
    // ==== Find token =======================
    $tokenRow = Module::fetchOne(
      'SELECT last_updated
            FROM sessions
            WHERE session_token = :session_token
              AND active = TRUE',
      [
        ':session_token' => $token,
      ]
    );
    
    if ($tokenRow === false) {
      Module::commitTransaction();
      return $this->generateErrorMessage(Unauthorized::CODE);
    }
    
    // ==== Disable token ====================
    Module::execute(
      'UPDATE sessions
            SET active = FALSE
            WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    );
    
    Module::commitTransaction();
    return null;
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
    ?string $phone = null,
    ?string $picture = null,
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
        ':picture'  => null, //TODO replace with file path
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
    $tokenValidation = SessionImpl::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return $this->generateErrorMessage($tokenValidation);
    }
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return $this->generateErrorMessage($tokenAuthorization);
    }
    
    // =======================================
    Module::beginTransaction();
    
    $user_id = Module::fetchOne(
      'SELECT user
            FROM sessions
            WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $user = Module::fetchOne(
      'SELECT username, name, surname, picture, phone, theme, language
            FROM users
            WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $user_id,
      ]
    );
    
    Module::commitTransaction();
    // TODO get picture from fs
    return [
      'username' => $user['username'],
      'name'     => $user['name'],
      'surname'  => $user['surname'],
      'picture'  => $user['picture'],
      'phone'    => $user['phone'],
      'theme'    => $user['theme'],
      'language' => $user['language'],
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function changeUserInformation(
    string  $token,
    ?string $username = null,
    ?string $name = null,
    ?string $surname = null,
    ?string $phone = null,
    ?string $picture = null,
    ?string $theme = null,
    ?string $language = null,
  ): array {
    $tokenValidation = SessionImpl::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return $this->generateErrorMessage($tokenValidation);
    }
    
    // ==== Modular validation and query preparation ==============
    $variableUpdateQuery =
      '# noinspection
      UPDATE users SET ';
    $variableAttributes = [];
    
    if ($username !== null) {
      $usernameValidation = UserImpl::validateUsername($username);
      
      if ($usernameValidation != Success::CODE) {
        return $this->generateErrorMessage($usernameValidation);
      }
      
      $variableUpdateQuery .= 'username = :username, ';
      $variableAttributes[':username'] = $username;
    }
    
    if ($name !== null) {
      $nameValidation = UserImpl::validateName($name);
      
      if ($nameValidation != Success::CODE) {
        return $this->generateErrorMessage($nameValidation);
      }
      
      $variableUpdateQuery .= 'name = :name, ';
      $variableAttributes[':name'] = $name;
    }
    
    if ($surname !== null) {
      $surnameValidation = UserImpl::validateSurname($surname);
      
      if ($surnameValidation != Success::CODE) {
        return $this->generateErrorMessage($surnameValidation);
      }
      
      $variableUpdateQuery .= 'surname = :surname, ';
      $variableAttributes[':surname'] = $surname;
    }
    
    if ($phone !== null) {
      $phoneValidation = UserImpl::validatePhone($phone);
      
      if ($phoneValidation != Success::CODE) {
        return $this->generateErrorMessage($phoneValidation);
      }
      
      $variableUpdateQuery .= 'phone = :phone, ';
      $variableAttributes[':phone'] = $phone;
    }
    
    if ($picture !== null) {
      $pictureValidation = UserImpl::validatePicture($picture);
      
      if ($pictureValidation != Success::CODE) {
        return $this->generateErrorMessage($pictureValidation);
      }
      
      $variableUpdateQuery .= 'picture = :picture, ';
      $variableAttributes[':picture'] = $picture;
    }
    
    if ($theme !== null) {
      $themeValidation = UserImpl::validateTheme($theme);
      
      if ($themeValidation != Success::CODE) {
        return $this->generateErrorMessage($themeValidation);
      }
      
      $variableUpdateQuery .= 'theme = :theme, ';
      $variableAttributes[':theme'] = $theme;
    }
    
    if ($language !== null) {
      $languageValidation = UserImpl::validateLanguage($language);
      
      if ($languageValidation != Success::CODE) {
        return $this->generateErrorMessage($languageValidation);
      }
      
      $variableUpdateQuery .= 'language = :language, ';
      $variableAttributes[':language'] = $language;
    }
    
    if (count($variableAttributes) == 0) {
      return $this->generateErrorMessage(NullAttributes::CODE);
    }
    
    $variableUpdateQuery = substr(
        $variableUpdateQuery,
        0,
        strlen($variableUpdateQuery) - 2
      ) . ' WHERE user_id = BINARY :user_id';
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return $this->generateErrorMessage($tokenAuthorization);
    }
    
    // =======================================
    Module::beginTransaction();
    
    // ==== Already exist checks =============
    if ($username !== null) {
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
    }
    
    $user_id = Module::fetchOne(
      'SELECT user
            FROM sessions
            WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $variableAttributes[':user_id'] = $user_id;
    
    Module::execute(
      $variableUpdateQuery,
      $variableAttributes
    );
    
    $user = Module::fetchOne(
      'SELECT username, name, surname, picture, phone, theme, language
            FROM users
            WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $user_id,
      ]
    );
    
    Module::commitTransaction();
    // TODO get picture from fs
    // TODO send ws packet
    return [
      'username' => $user['username'],
      'name'     => $user['name'],
      'surname'  => $user['surname'],
      'picture'  => $user['picture'],
      'phone'    => $user['phone'],
      'theme'    => $user['theme'],
      'language' => $user['language'],
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function deleteUser(
    string $token,
  ): ?array {
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
  ): ?array {
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
    ?string $user = null,
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
    ?string $info = null,
    ?string $picture = null,
    ?array  $users = null,
  ): array {
    // TODO: Implement createGroup() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function getGroupInformation(
    string  $token,
    ?string $group = null,
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
    ?string $name = null,
    ?string $info = null,
    ?string $picture = null,
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
    ?string $user = null,
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
    ?string $group = null,
    ?string $contact = null,
    ?string $from = null,
    ?string $to = null,
    ?bool   $pinned = null,
    ?string $message = null,
  ): array {
    // TODO: Implement getMessages() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function writeMessage(
    string  $token,
    ?string $group = null,
    ?string $contact = null,
    ?string $content = null,
    ?string $text = null,
    ?string $media = null,
  ): array {
    // TODO: Implement writeMessage() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function changeMessage(
    string  $token,
    string  $message,
    ?string $group = null,
    ?string $contact = null,
    ?string $content = null,
    ?string $text = null,
    ?string $media = null,
    ?bool   $pinned = null,
  ): array {
    // TODO: Implement changeMessage() method.
    return [];
  }
  
  /**
   * @inheritDoc
   */
  public function deleteMessage(
    string  $token,
    string  $message,
    ?string $group = null,
    ?string $contact = null,
  ): ?array {
    // TODO: Implement deleteMessage() method.
    return [];
  }
}