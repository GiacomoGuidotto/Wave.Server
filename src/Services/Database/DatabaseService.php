<?php /** @noinspection SqlResolve */

namespace Wave\Services\Database;

use DateInterval;
use JetBrains\PhpStorm\ArrayShape;
use Wave\Model\Session\Session;
use Wave\Model\Singleton\Singleton;
use Wave\Model\User\User;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Services\MIME\MIMEService;
use Wave\Services\WebSocket\WebSocketService;
use Wave\Specifications\ErrorCases\Elaboration\BlockedByUser;
use Wave\Specifications\ErrorCases\Elaboration\WrongDirective;
use Wave\Specifications\ErrorCases\Elaboration\WrongStatus;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\NotFound;
use Wave\Specifications\ErrorCases\State\Timeout;
use Wave\Specifications\ErrorCases\State\Unauthorized;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\Wave\Wave;
use Wave\Utilities\Utilities;

/**
 * Database service class
 *
 * The implementation of the DatabaseServiceInterface interface made for the MySQL database
 */
class DatabaseService extends Singleton implements DatabaseServiceInterface {
  
  // TODO refactor to static methods and made them return the error code or null
  
  // ==== Utility methods ==========================================================================
  // ==== Set of private methods ===================================================================
  
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
    DatabaseModule::beginTransaction();
    
    // ==== Find token =======================
    $token_row = DatabaseModule::fetchOne(
      'SELECT last_updated
             FROM sessions
             WHERE session_token = :session_token
               AND active = TRUE',
      [
        ':session_token' => $token,
      ]
    );
    
    if ($token_row === false) {
      DatabaseModule::commitTransaction();
      return Unauthorized::CODE;
    }
    
    // ==== Update session TTL ===============
    $last_updated = $token_row['last_updated'];
    
    $current_timestamp = DatabaseModule::fetchOne(
      'SELECT CURRENT_TIMESTAMP()'
    )['CURRENT_TIMESTAMP()'];
    
    if (!$this->validateTimeout($current_timestamp, $last_updated)) {
      // ==== If timeout =======================
      DatabaseModule::execute(
        'UPDATE sessions
               SET active = FALSE
               WHERE session_token = :session_token',
        [
          ':session_token' => $token,
        ]
      );
      
      DatabaseModule::commitTransaction();
      return Timeout::CODE;
    }
    
    DatabaseModule::execute(
      'UPDATE sessions
             SET last_updated = CURRENT_TIMESTAMP()
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    );
    
    DatabaseModule::commitTransaction();
    return Success::CODE;
  }
  
  /**
   * Create the two chat tables given the uuid, and fill the member one with the given array
   *
   * @param string $uuid
   * @param array  $members
   * @return void
   */
  private function generateChatTable(string $uuid, array $members) {
    $previouslyInTransaction = DatabaseModule::inTransaction();
    if ($previouslyInTransaction) DatabaseModule::commitTransaction();
    
    // ==== Generate message table =======================================================
    DatabaseModule::execute(
      'CREATE TABLE `:name`
             (
               `message_id`  INTEGER       NOT NULL PRIMARY KEY AUTO_INCREMENT,
               `message_key` VARCHAR(36)   NOT NULL CHECK ( LENGTH(`message_key`) > 35 ),
               `timestamp`   TIMESTAMP     NOT NULL,
               `content`     VARCHAR(1)    NOT NULL,
               `text`        VARCHAR(1024) NOT NULL,
               `media`       VARCHAR(255),
               `author`      INTEGER       NOT NULL,
               `pinned`      VARCHAR(1)    NOT NULL,
               `active`      BOOLEAN       NOT NULL,
               FOREIGN KEY (author)
                   REFERENCES users (user_id)
                   ON DELETE CASCADE
             )',
      [
        ':name' => "chat_" . $uuid . "_messages",
      ]
    );
    
    // ==== Generate member table =======================================================
    DatabaseModule::execute(
      'CREATE TABLE `:name`
             (
               `member_id`         INTEGER     NOT NULL PRIMARY KEY AUTO_INCREMENT,
               `user`              INTEGER     NOT NULL,
               `last_seen_message` VARCHAR(36) CHECK ( LENGTH(`last_seen_message`) > 35 ),
               `permissions`       SMALLINT    NOT NULL,
               `active`            BOOLEAN     NOT NULL,
               FOREIGN KEY (user)
                 REFERENCES users (user_id)
                 ON DELETE CASCADE
             )',
      [
        ':name' => "chat_" . $uuid . '_members',
      ]
    );
    
    foreach ($members as $member) {
      $memberId = DatabaseModule::fetchOne(
        'SELECT user_id
               FROM users
               WHERE username = BINARY :username',
        [
          ':username' => $member,
        ]
      )['user_id'];
      
      DatabaseModule::execute(
        'INSERT
               INTO `:name` (user, last_seen_message, permissions, active)
               VALUES (
                 :user,
                 :last_seen_message,
                 :permission,
                 :active
               )',
        [
          ':name'              => "chat_" . $uuid . '_members',
          ':user'              => $memberId,
          ':last_seen_message' => null,
          ':permission'        => 127,
          ':active'            => true,
        ]
      );
    }
    
    if ($previouslyInTransaction) DatabaseModule::beginTransaction();
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
    $usernameValidation = User::validateUsername($username);
    $passwordValidation = User::validatePassword($password);
    $deviceValidation = Session::validateSource($source);
    
    if ($usernameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($usernameValidation);
    }
    if ($passwordValidation != Success::CODE) {
      return Utilities::generateErrorMessage($passwordValidation);
    }
    if ($deviceValidation != Success::CODE) {
      return Utilities::generateErrorMessage($deviceValidation);
    }
    
    // =======================================
    DatabaseModule::beginTransaction();
    
    // ==== correct username and password ====
    $storedPasswordRow = DatabaseModule::fetchOne(
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
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== password incorrect ===============
    $storedPassword = $storedPasswordRow['password'];
    if (!password_verify($password, $storedPassword)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== delete token already existing ====
    $userId = $storedPasswordRow['user_id'];
    DatabaseModule::execute(
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
    DatabaseModule::execute(
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
    
    $token = DatabaseModule::fetchOne(
      'SELECT session_token
             FROM sessions
             WHERE session_id = LAST_INSERT_ID()'
    )['session_token'];
    
    DatabaseModule::commitTransaction();
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
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    return null;
  }
  
  /**
   * @inheritDoc
   */
  public function logout(
    string $token,
  ): ?array {
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    // =======================================
    DatabaseModule::beginTransaction();
    
    // ==== Find token =======================
    $tokenRow = DatabaseModule::fetchOne(
      'SELECT last_updated
             FROM sessions
             WHERE session_token = :session_token
               AND active = TRUE',
      [
        ':session_token' => $token,
      ]
    );
    
    if ($tokenRow === false) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(Unauthorized::CODE);
    }
    
    // ==== Disable token ====================
    DatabaseModule::execute(
      'UPDATE sessions
             SET active = FALSE
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    );
    
    DatabaseModule::commitTransaction();
    return null;
  }
  
  // ==== User ============================================================================
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
    $usernameValidation = User::validateUsername($username);
    $passwordValidation = User::validatePassword($password);
    $nameValidation = User::validateName($name);
    $surnameValidation = User::validateSurname($surname);
    
    if ($usernameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($usernameValidation);
    }
    if ($passwordValidation != Success::CODE) {
      return Utilities::generateErrorMessage($passwordValidation);
    }
    if ($nameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($nameValidation);
    }
    if ($surnameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($surnameValidation);
    }
    
    if ($phone != null) {
      $phoneValidation = User::validatePhone($phone);
      
      if ($phoneValidation != Success::CODE) {
        return Utilities::generateErrorMessage($phoneValidation);
      }
    }
    
    // ===========================================
    DatabaseModule::beginTransaction();
    
    // ==== Already exist checks =================
    $user = DatabaseModule::fetchOne(
      'SELECT username, name
             FROM users
             WHERE username = BINARY :username
               AND active = TRUE',
      [
        ':username' => $username,
      ]
    );
    
    if ($user) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(AlreadyExist::CODE);
    }
    
    // ==== Save image into the fs ===============
    $filepath = null;
    
    if ($picture != null) {
      $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/user/$username";
      $filepath = MIMEService::createMedia($filepath, $picture);
      
      if (!is_string($filepath)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($filepath);
      }
    }
    
    // ==== Securing password ====================
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // ==== Insert query =========================
    DatabaseModule::execute(
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
        ':picture'  => $filepath,
        ':phone'    => $phone,
        ':active'   => true,
      ]
    );
    
    DatabaseModule::commitTransaction();
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
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // =======================================
    DatabaseModule::beginTransaction();
    
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $user = DatabaseModule::fetchOne(
      'SELECT username, name, surname, picture, phone, theme, language
             FROM users
             WHERE user_id = :user_id',
      [
        ':user_id' => $userId,
      ]
    );
    
    DatabaseModule::commitTransaction();
    
    // ==== Retrieve picture =====================
    $filepath = $user['picture'];
    $picture = !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
    
    return [
      'username' => $user['username'],
      'name'     => $user['name'],
      'surname'  => $user['surname'],
      'picture'  => $picture,
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
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    // ==== Modular validation and query preparation ==============
    $variableUpdateQuery =
      '# noinspection
      UPDATE users SET ';
    $variableAttributes = [];
    
    if ($username !== null) {
      $usernameValidation = User::validateUsername($username);
      
      if ($usernameValidation != Success::CODE) {
        return Utilities::generateErrorMessage($usernameValidation);
      }
      
      $variableUpdateQuery .= 'username = :username, ';
      $variableAttributes[':username'] = $username;
    }
    
    if ($name !== null) {
      $nameValidation = User::validateName($name);
      
      if ($nameValidation != Success::CODE) {
        return Utilities::generateErrorMessage($nameValidation);
      }
      
      $variableUpdateQuery .= 'name = :name, ';
      $variableAttributes[':name'] = $name;
    }
    
    if ($surname !== null) {
      $surnameValidation = User::validateSurname($surname);
      
      if ($surnameValidation != Success::CODE) {
        return Utilities::generateErrorMessage($surnameValidation);
      }
      
      $variableUpdateQuery .= 'surname = :surname, ';
      $variableAttributes[':surname'] = $surname;
    }
    
    if ($phone !== null) {
      $phoneValidation = User::validatePhone($phone);
      
      if ($phoneValidation != Success::CODE) {
        return Utilities::generateErrorMessage($phoneValidation);
      }
      
      $variableUpdateQuery .= 'phone = :phone, ';
      $variableAttributes[':phone'] = $phone;
    }
    
    if ($theme !== null) {
      $themeValidation = User::validateTheme($theme);
      
      if ($themeValidation != Success::CODE) {
        return Utilities::generateErrorMessage($themeValidation);
      }
      
      $variableUpdateQuery .= 'theme = :theme, ';
      $variableAttributes[':theme'] = $theme;
    }
    
    if ($language !== null) {
      $languageValidation = User::validateLanguage($language);
      
      if ($languageValidation != Success::CODE) {
        return Utilities::generateErrorMessage($languageValidation);
      }
      
      $variableUpdateQuery .= 'language = :language, ';
      $variableAttributes[':language'] = $language;
    }
    
    if (count($variableAttributes) == 0) {
      return Utilities::generateErrorMessage(NullAttributes::CODE);
    }
    
    // ==== Token authorization ==================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===========================================
    DatabaseModule::beginTransaction();
    
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    // ==== Already exist checks =================
    if ($username !== null) {
      $user = DatabaseModule::fetchOne(
        'SELECT username
               FROM users
               WHERE username = BINARY :username
                 AND active = TRUE',
        [
          ':username' => $username,
        ]
      );
      
      if ($user) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(AlreadyExist::CODE);
      }
    }
    
    $storedUsername = DatabaseModule::fetchOne(
      'SELECT username
             FROM users
             WHERE user_id = :user_id
               AND active = TRUE',
      [
        ':user_id' => $userId,
      ]
    )['username'];
    
    // ==== Save image into the fs ===============
    if ($picture !== null) {
      $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/user/$storedUsername";
      $filepath = MIMEService::updateMedia($filepath, $picture);
      
      if (!is_string($filepath)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($filepath);
      }
      
      $variableUpdateQuery .= 'picture = :picture, ';
      $variableAttributes[':picture'] = $filepath;
    }
    
    // ==== Update user ==========================
    
    $variableUpdateQuery = substr(
        $variableUpdateQuery,
        0,
        strlen($variableUpdateQuery) - 2
      ) . ' WHERE user_id = :user_id';
    $variableAttributes[':user_id'] = $userId;
    
    DatabaseModule::execute(
      $variableUpdateQuery,
      $variableAttributes
    );
    
    $user = DatabaseModule::fetchOne(
      'SELECT username, name, surname, picture, phone, theme, language
             FROM users
             WHERE user_id = :user_id',
      [
        ':user_id' => $userId,
      ]
    );
    
    DatabaseModule::commitTransaction();
    
    // ==== Send channel packet to all contacts if some shared attribute has been changed
    if ($username !== null || $name !== null || $surname !== null || $picture !== null) {
      WebSocketService::sendToWebSocket(
                 $user['username'],
                 'UPDATE',
                 'contact/information',
        headers: [
                   'old_username' => $storedUsername,
                 ],
        body   : [
                   'username' => $user['username'],
                   'name'     => $user['name'],
                   'surname'  => $user['surname'],
                   'picture'  => $picture,
                 ],
      );
    }
    
    return [
      'username' => $user['username'],
      'name'     => $user['name'],
      'surname'  => $user['surname'],
      'picture'  => $picture,
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
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    
    // =======================================
    DatabaseModule::beginTransaction();
    
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    // ==== Delete picture =======================
    $filepath = DatabaseModule::fetchOne(
      'SELECT picture
             FROM users
             WHERE user_id = :user_id',
      [
        ':user_id' => $userId,
      ]
    )['picture'];
    
    if (!is_null($filepath)) {
      $result = MIMEService::deleteMedia($filepath);
      if (!is_null($result)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($result);
      }
    }
    
    // ==== Delete user ==========================
    // TODO recursive deletion of contact relation and group participation, not the messages
    
    DatabaseModule::execute(
      'UPDATE users
             SET active = FALSE
             WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $userId,
      ]
    );
    
    DatabaseModule::commitTransaction();
    return null;
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
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    $usernameValidation = User::validateUsername($user);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    if ($usernameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($usernameValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    $originUserId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $originUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $originUserId,
      ]
    );
    
    // ==== Target existence check =======================================================
    
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if ($targetedUser === false) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Already exist check ==========================================================
    
    $contact = DatabaseModule::fetchOne(
      'SELECT status, active
             FROM contacts
             WHERE (first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user)',
      [
        ':first_user'  => $originUser['user_id'],
        ':second_user' => $targetedUser['user_id'],
      ]
    );
    
    if (is_array($contact)) {
      $contactStatus = $contact['status'];
      
      // ==== "Blocked by targeted user" case ============================================
      if ($contactStatus === 'B') {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(BlockedByUser::CODE);
      }
      
      // ==== Already active check ========================================================
      if ($contact['active']) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(AlreadyExist::CODE);
      }
      
      
      // ==== Reactivate existing entity =================================================
      DatabaseModule::execute(
        'UPDATE contacts
               SET status = :status,
                   chat = :chat,
                   active = TRUE
               WHERE (first_user = :first_user
                 AND second_user = :second_user)
                  OR (first_user = :second_user
                 AND second_user = :first_user)',
        [
          ':status'      => 'P',
          ':chat'        => null,
          ':first_user'  => $originUser['user_id'],
          ':second_user' => $targetedUser['user_id'],
        ]
      );
    } else {
      $contactStatus = 'P';
      
      // ==== Creating new entity ========================================================
      DatabaseModule::execute(
        'INSERT
               INTO contacts (first_user, second_user, status, chat, active)
               VALUES (
                 :first_user,
                 :second_user,
                 :status,
                 :chat,
                 :active
               )',
        [
          ':first_user'  => $originUser['user_id'],
          ':second_user' => $targetedUser['user_id'],
          ':status'      => $contactStatus,
          ':chat'        => null,
          ':active'      => true,
        ]
      );
    }
    DatabaseModule::commitTransaction();
    
    // ==== Send Channel packet with origin user's data to targeted user =================
    
    $originUserFilepath = $originUser['picture'];
    $originUserPicture = !is_null($originUserFilepath) ?
      MIMEService::researchMedia($originUserFilepath) :
      null;
    
    WebSocketService::sendToWebSocket(
               $originUser['username'],
               'CREATE',
               'contact',
      headers: [
                 'to' => $targetedUser['username'],
               ],
      body   : [
                 'username' => $originUser['username'],
                 'name'     => $originUser['name'],
                 'surname'  => $originUser['surname'],
                 'picture'  => $originUserPicture,
               ]
    );
    
    // ==== Return targeted user's data to origin user ===================================
    
    $targetedUserFilepath = $targetedUser['picture'];
    $targetedUserPicture = !is_null($targetedUserFilepath) ?
      MIMEService::researchMedia($targetedUserFilepath) :
      null;
    
    return [
      'username' => $targetedUser['username'],
      'name'     => $targetedUser['name'],
      'surname'  => $targetedUser['surname'],
      'picture'  => $targetedUserPicture,
      'status'   => $contactStatus,
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function deleteContactRequest(
    string $token,
    string $user,
  ): ?array {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    $usernameValidation = User::validateUsername($user);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    if ($usernameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($usernameValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    $originUserId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $originUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $originUserId,
      ]
    );
    
    // ==== Target existence check =======================================================
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if ($targetedUser === false) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Contact existence check ======================================================
    
    $contact = DatabaseModule::fetchOne(
      'SELECT status
             FROM contacts
             WHERE active = TRUE
               AND (first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user)',
      [
        ':first_user'  => $originUser['user_id'],
        ':second_user' => $targetedUser['user_id'],
      ]
    );
    
    if (!is_array($contact)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Safe zone ====================================================================
    
    if ($contact['status'] !== 'P') {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(WrongStatus::CODE);
    }
    
    DatabaseModule::execute(
      'UPDATE contacts
             SET active = FALSE
             WHERE (first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user)',
      [
        ':first_user'  => $originUser['user_id'],
        ':second_user' => $targetedUser['user_id'],
      ]
    );
    DatabaseModule::commitTransaction();
    
    // ==== Send Channel packet with deletion directive to targeted user =================
    
    WebSocketService::sendToWebSocket(
               $originUser['username'],
               "DELETE",
               "contact/status",
      headers: [
                 "to" => $targetedUser['username'],
               ]
    );
    
    return null;
  }
  
  /**
   * @inheritDoc
   */
  public function changeContactStatus(
    string $token,
    string $user,
    string $directive,
  ): ?array {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    $usernameValidation = User::validateUsername($user);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    if ($usernameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($usernameValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    $originUserId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $originUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $originUserId,
      ]
    );
    
    // ==== Target existence check =======================================================
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if ($targetedUser === false) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Contact existence check ======================================================
    
    $contact = DatabaseModule::fetchOne(
      'SELECT status, chat, blocked_by
             FROM contacts
             WHERE active = TRUE
               AND (first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user)',
      [
        ':first_user'  => $originUser['user_id'],
        ':second_user' => $targetedUser['user_id'],
      ]
    );
    
    if (!is_array($contact)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Safe zone ====================================================================
    
    $oldStatus = $contact['status'];
    $returnContact = true;
    
    switch ($directive) {
      // Accept
      case 'A':
        if ($oldStatus !== 'P') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongStatus::CODE);
        }
        
        $tablesUUID = DatabaseModule::fetchOne('SELECT UUID()')['UUID()'];
        
        $this->generateChatTable(
          $tablesUUID,
          [
            $originUser['username'],
            $targetedUser['username'],
          ]
        );
        
        $newStatus = 'A';
        DatabaseModule::execute(
          'UPDATE contacts
                 SET status = :status,
                     chat = :chat
                 WHERE (first_user = :first_user
                   AND second_user = :second_user)
                    OR (first_user = :second_user
                   AND second_user = :first_user)',
          [
            ':status'      => $newStatus,
            ':chat'        => $tablesUUID,
            ':first_user'  => $originUser['user_id'],
            ':second_user' => $targetedUser['user_id'],
          ]
        );
        break;
      
      // Decline
      case 'D':
        if ($oldStatus !== 'P') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongStatus::CODE);
        }
        
        DatabaseModule::execute(
          'UPDATE contacts
                 SET active = FALSE
                 WHERE (first_user = :first_user
                   AND second_user = :second_user)
                    OR (first_user = :second_user
                   AND second_user = :first_user)',
          [
            ':first_user'  => $originUser['user_id'],
            ':second_user' => $targetedUser['user_id'],
          ]
        );
        $returnContact = false;
        break;
      
      // Block
      case 'B':
        if ($oldStatus === 'B') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongStatus::CODE);
        }
        
        $newStatus = 'B';
        DatabaseModule::execute(
          'UPDATE contacts
                 SET status = :status,
                     blocked_by = :blocked_by
                 WHERE (first_user = :first_user
                   AND second_user = :second_user)
                    OR (first_user = :second_user
                   AND second_user = :first_user)',
          [
            ':status'      => $newStatus,
            ':blocked_by'  => $originUser['user_id'],
            ':first_user'  => $originUser['user_id'],
            ':second_user' => $targetedUser['user_id'],
          ]
        );
        break;
      
      // Remove
      case 'R':
        if ($oldStatus !== 'A') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongStatus::CODE);
        }
        
        $newStatus = 'P';
        DatabaseModule::execute(
          'UPDATE contacts
                 SET status = :status,
                     active = FALSE
                 WHERE (first_user = :first_user
                   AND second_user = :second_user)
                    OR (first_user = :second_user
                   AND second_user = :first_user)',
          [
            ':status'      => $newStatus,
            ':first_user'  => $originUser['user_id'],
            ':second_user' => $targetedUser['user_id'],
          ]
        );
        $returnContact = false;
        break;
      
      // Unblock
      case 'U':
        if ($oldStatus !== 'B') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongStatus::CODE);
        }
        
        if ($contact['blocked_by'] !== $originUser['user_id']) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(BlockedByUser::CODE);
        }
        
        if (is_null($contact['chat'])) {
          $newStatus = 'P';
        } else {
          $newStatus = 'A';
        }
        DatabaseModule::execute(
          'UPDATE contacts
               SET status = :status
               WHERE (first_user = :first_user
                 AND second_user = :second_user)
                  OR (first_user = :second_user
                 AND second_user = :first_user)',
          [
            ':status'      => $newStatus,
            ':first_user'  => $originUser['user_id'],
            ':second_user' => $targetedUser['user_id'],
          ]
        );
        break;
      
      default:
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(WrongDirective::CODE);
    }
    
    DatabaseModule::commitTransaction();
    // ==== Send Channel packet with deletion directive to targeted user =================
    
    WebSocketService::sendToWebSocket(
               $originUser['username'],
               "UPDATE",
               "contact/status",
      headers: [
                 "to"        => $targetedUser['username'],
                 "directive" => $directive,
               ]
    );
    
    // ==== Return targeted user's data to origin user ===================================
    
    if ($returnContact) {
      $targetedUserFilepath = $targetedUser['picture'];
      $targetedUserPicture = !is_null($targetedUserFilepath) ?
        MIMEService::researchMedia($targetedUserFilepath) :
        null;
      
      return [
        'username' => $targetedUser['username'],
        'name'     => $targetedUser['name'],
        'surname'  => $targetedUser['surname'],
        'picture'  => $targetedUserPicture,
        'status'   => $newStatus ?? $oldStatus,
      ];
    } else {
      return null;
    }
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
  
  // ==== Administration ===========================================================================
  // ==== Use cases related to the database administration =========================================
  
  /**
   * @inheritDoc
   */
  public function purgeDatabase(): void {
    
    // ==== purge users ==================================================================
    DatabaseModule::execute('DELETE FROM users WHERE active = FALSE');
    
    // ==== purge tokens =================================================================
    DatabaseModule::execute('DELETE FROM sessions WHERE active = FALSE');
    
    // ==== purge chat tables ============================================================
    $contactChats = DatabaseModule::fetchAll('SELECT chat FROM contacts WHERE active = FALSE');
    
    foreach ($contactChats as $contactChat) {
      DatabaseModule::execute(
        'DROP TABLE `:name`',
        [
          ':name' => 'chat_' . $contactChat['chat'] . '_messages',
        ]
      );
      DatabaseModule::execute(
        'DROP TABLE `:name`',
        [
          ':name' => 'chat_' . $contactChat['chat'] . '_members',
        ]
      );
    }
    
    // ==== purge contacts ===============================================================
    DatabaseModule::execute('DELETE FROM contacts WHERE active = FALSE');
    
    // ==== purge groups =================================================================
    DatabaseModule::execute('DELETE FROM `groups` WHERE active = FALSE');
  }
  
  // ==== Service methods ==========================================================================
  // ==== Set of public methods used from other services ===========================================
  
  /**
   * Validate a user, given its token
   *
   * @param string $token The token to check
   * @return string|int   The username of the user, or the error code
   */
  private function instanceValidateUser(
    string $token,
  ): string|int {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return $tokenValidation;
    }
    
    // ==== Token authorization ==============
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return $tokenAuthorization;
    }
    
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    return DatabaseModule::fetchOne(
      'SELECT username
             FROM users
             WHERE user_id = :user_id',
      [
        ':user_id' => $userId,
      ]
    )['username'];
  }
  
  /**
   * Validate a user, given its token
   *
   * @param string $token The token to check
   * @return string|int   The username of the user, or the error code
   */
  public static function validateUser(
    string $token,
  ): string|int {
    $service = DatabaseService::getInstance();
    return $service->instanceValidateUser($token);
  }
  
  
  /**
   * Retrieve the relations references from the database
   *
   * @return array[] The two series of references
   */
  #[ArrayShape(['contacts' => "array", 'groups' => "array"])]
  private function instanceRetrieveReference(): array {
    // ==== Retrieve contacts ============================================================
    $databaseContacts = DatabaseModule::fetchAll(
      'SELECT first_user, second_user
             FROM contacts
             WHERE active = TRUE'
    );
    
    $contacts = [];
    foreach ($databaseContacts as $databaseContact) {
      $firstUser = DatabaseModule::fetchOne(
        'SELECT username
               FROM users
               WHERE user_id = :user_id
                 AND active = TRUE',
        [
          ':user_id' => $databaseContact['first_user'],
        ]
      )['username'];
      
      
      $secondUser = DatabaseModule::fetchOne(
        'SELECT username
               FROM users
               WHERE user_id = :user_id
                 AND active = TRUE',
        [
          ':user_id' => $databaseContact['second_user'],
        ]
      )['username'];
      
      $contacts[$firstUser][] = $secondUser;
      $contacts[$secondUser][] = $firstUser;
    }
    
    // ==== Retrieve groups ==============================================================
    // TODO Retrieve groups
    $groups = [];
    
    return [
      'contacts' => $contacts,
      'groups'   => $groups,
    ];
  }
  
  /**
   * Retrieve the relations references from the database
   *
   * @return array[] The two series of references
   */
  #[ArrayShape(['contacts' => "array", 'groups' => "array"])]
  public static function retrieveReferences(): array {
    $service = DatabaseService::getInstance();
    return $service->instanceRetrieveReference();
  }
}