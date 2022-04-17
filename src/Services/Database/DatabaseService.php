<?php /** @noinspection PhpConditionAlreadyCheckedInspection */

/** @noinspection SqlResolve */

namespace Wave\Services\Database;

use DateInterval;
use Wave\Model\Group\Group;
use Wave\Model\Member\Member;
use Wave\Model\Member\Permission;
use Wave\Model\Session\Session;
use Wave\Model\Singleton\Singleton;
use Wave\Model\User\User;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Services\MIME\MIMEService;
use Wave\Services\WebSocket\WebSocketModule;
use Wave\Specifications\ErrorCases\Elaboration\BlockedByUser;
use Wave\Specifications\ErrorCases\Elaboration\DirectiveNotAllowed;
use Wave\Specifications\ErrorCases\Elaboration\SelfRequest;
use Wave\Specifications\ErrorCases\Elaboration\WrongDirective;
use Wave\Specifications\ErrorCases\Elaboration\WrongState;
use Wave\Specifications\ErrorCases\Elaboration\WrongStatus;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\State\AlreadyExist;
use Wave\Specifications\ErrorCases\State\Forbidden;
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
    
    if (!is_array($token_row)) {
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
   * @param string $uuid        The identifier of the chat to generate
   * @param array  $members     The list of members to insert
   * @param array  $permissions The permissions of this members
   * @return void
   */
  private function generateChatTable(string $uuid, array $members, array $permissions) {
    $previouslyInTransaction = DatabaseModule::inTransaction();
    if ($previouslyInTransaction) DatabaseModule::commitTransaction();
    
    $messagesTableName = "chat_" . $uuid . "_messages";
    $membersTableName = "chat_" . $uuid . "_members";
    
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
        ':name' => $messagesTableName,
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
        ':name' => $membersTableName,
      ]
    );
    
    if ($previouslyInTransaction) DatabaseModule::beginTransaction();
    
    for ($i = 0; $i < count($members); $i++) {
      $memberId = DatabaseModule::fetchOne(
        'SELECT user_id
               FROM users
               WHERE username = BINARY :username',
        [
          ':username' => $members[$i],
        ]
      )['user_id'];
      
      DatabaseModule::execute(
        "INSERT
               INTO `:name` (`user`, `last_seen_message`, `permissions`, `active`)
               VALUES (
                 :user,
                 :last_seen_message,
                 :permission,
                 :active
               )",
        [
          ':name'              => $membersTableName,
          ':user'              => $memberId,
          ':last_seen_message' => null,
          ':permission'        => $permissions[$i],
          ':active'            => true,
        ]
      );
    }
  }
  
  private function authorizeChatMember(
    string           $uuid,
    int              $userId,
    array|Permission $permissions,
  ): bool {
    $permission = DatabaseModule::fetchOne(
      "SELECT permissions
             FROM `:name`
             WHERE active = TRUE
               AND user = :user",
      [
        ":name" => "chat_" . $uuid . "_members",
        ":user" => $userId,
      ]
    )['permissions'];
    
    // convert single element in array
    if (!is_array($permissions)) $permissions = [$permissions];
    
    // remove null value
    $permissions = array_filter($permissions);
    
    // extract int
    $permissions = array_map(fn($permission): int => $permission->value, $permissions);
    
    $permissions = array_sum($permissions);
    return ($permission & $permissions) === $permissions;
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
    if (!is_array($storedPasswordRow)) {
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
    
    if (!is_array($tokenRow)) {
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
    
    if (count($variableAttributes) == 0) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NullAttributes::CODE);
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
    
    // ==== Send channel packet to all contacts if some shared attribute has been changed
    if ($username !== null || $name !== null || $surname !== null || $picture !== null) {
      
      $contacts = DatabaseModule::fetchAll(
        'SELECT first_user, second_user
               FROM contacts
               WHERE active = TRUE
               AND (first_user = :first_user
                OR second_user = :first_user)',
        [
          ':first_user' => $userId,
        ]
      );
      
      if (count($contacts)) {
        $contacts = array_map(
          function ($contact) use ($userId): string {
            return DatabaseModule::fetchOne(
              "SELECT username
                     FROM users
                     WHERE user_id = :user_id",
              [
                ":user_id" => $contact['first_user'] === $userId ?
                  $contact['second_user'] :
                  $contact['first_user'],
              ]
            )['username'];
          },
          $contacts
        );
        
        WebSocketModule::sendChannelPacket(
          directive: 'UPDATE',
          topic    : 'contact/information',
          origin   : $user['username'],
          target_s : $contacts,
          body     : [
                       'username' => $user['username'],
                       'name'     => $user['name'],
                       'surname'  => $user['surname'],
                       'picture'  => $picture,
                     ],
        );
      }
    }
    
    DatabaseModule::commitTransaction();
    
    // ==== Retrieve picture and return ==================================================
    $filepath = $user['picture'];
    $picture ??= !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
    
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
    
    if ($originUser['username'] === $user) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(SelfRequest::CODE);
    }
    
    // ==== Target existence check =======================================================
    
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if (!is_array($targetedUser)) {
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
      
      // ==== Already active check =======================================================
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
    
    WebSocketModule::sendChannelPacket(
      directive: 'CREATE',
      topic    : 'contact',
      origin   : $originUser['username'],
      target_s : $targetedUser['username'],
      body     : [
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
    
    if ($originUser['username'] === $user) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(SelfRequest::CODE);
    }
    
    // ==== Target existence check =======================================================
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if (!is_array($targetedUser)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Contact existence check ======================================================
    
    $contact = DatabaseModule::fetchOne(
      'SELECT second_user, status
             FROM contacts
             WHERE active = TRUE
               AND ((first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user))',
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
    
    if ($contact['second_user'] !== $targetedUser['user_id']) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(DirectiveNotAllowed::CODE);
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
    
    WebSocketModule::sendChannelPacket(
      directive: "DELETE",
      topic    : "contact/status",
      origin   : $originUser['username'],
      target_s : $targetedUser['username'],
      headers  : [
                   'username' => $originUser['username'],
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
    
    if ($originUser['username'] === $user) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(SelfRequest::CODE);
    }
    
    // ==== Target existence check =======================================================
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if (!is_array($targetedUser)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Contact existence check ======================================================
    
    $contact = DatabaseModule::fetchOne(
      'SELECT second_user, status, chat, blocked_by
             FROM contacts
             WHERE active = TRUE
               AND ((first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user))',
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
        
        // check if the second user is the target, since at insertion the target has been
        // positioned in the second_user column
        if ($contact['second_user'] !== $originUser['user_id']) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(DirectiveNotAllowed::CODE);
        }
        
        $tablesUUID = DatabaseModule::fetchOne('SELECT UUID()')['UUID()'];
        
        $this->generateChatTable(
          $tablesUUID,
          [
            $originUser['username'],
            $targetedUser['username'],
          ],
          [
            Wave::MAX_GROUP_PERMISSION,
            Wave::MAX_GROUP_PERMISSION,
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
        
        if ($contact['second_user'] !== $originUser['user_id']) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(DirectiveNotAllowed::CODE);
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
        
        if ($contact['second_user'] !== $originUser['user_id'] && is_null($contact['chat'])) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(DirectiveNotAllowed::CODE);
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
               SET status = :status,
                   blocked_by = null
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
    
    WebSocketModule::sendChannelPacket(
      directive: "UPDATE",
      topic    : "contact/status",
      origin   : $originUser['username'],
      target_s : $targetedUser['username'],
      headers  : [
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
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
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
    
    if (is_null($user)) {
      
      // ==== Contact existence check ======================================================
      
      $contacts = DatabaseModule::fetchAll(
        'SELECT first_user, second_user, status
             FROM contacts
             WHERE active = TRUE
               AND (first_user = :first_user
                OR second_user = :first_user)',
        [
          ':first_user' => $originUser['user_id'],
        ]
      );
      
      if (!is_array($contacts) || count($contacts) === 0) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      $refactoredContacts = [];
      
      foreach ($contacts as $contact) {
        if ($contact['first_user'] === $originUser['user_id']) {
          $databaseContact = DatabaseModule::fetchOne(
            'SELECT username, name, surname, picture
                   FROM users
                   WHERE user_id = :user_id',
            [
              ':user_id' => $contact['second_user'],
            ]
          );
          
          $contactFilepath = $databaseContact['picture'];
          $contactPicture = !is_null($contactFilepath) ?
            MIMEService::researchMedia($contactFilepath) :
            null;
          
          $refactoredContacts[] = [
            'username' => $databaseContact['username'],
            'name'     => $databaseContact['name'],
            'surname'  => $databaseContact['surname'],
            'picture'  => $contactPicture,
            'status'   => $contact['status'],
          ];
        }
      }
      
      DatabaseModule::commitTransaction();
      return $refactoredContacts;
    } else {
      $usernameValidation = User::validateUsername($user);
      
      if ($usernameValidation != Success::CODE) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($usernameValidation);
      }
      
      // ==== Target existence check =====================================================
      $targetedUser = DatabaseModule::fetchOne(
        'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
        [
          ':username' => $user,
        ]
      );
      
      if (!is_array($targetedUser)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      // ==== Contact existence check ====================================================
      $contact = DatabaseModule::fetchOne(
        'SELECT status, chat, blocked_by
             FROM contacts
             WHERE active = TRUE
               AND ((first_user = :first_user
               AND second_user = :second_user)
                OR (first_user = :second_user
               AND second_user = :first_user))',
        [
          ':first_user'  => $originUser['user_id'],
          ':second_user' => $targetedUser['user_id'],
        ]
      );
      
      DatabaseModule::commitTransaction();
      
      if (!is_array($contact)) {
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      $contactFilepath = $targetedUser['picture'];
      $contactPicture = !is_null($contactFilepath) ?
        MIMEService::researchMedia($contactFilepath) :
        null;
      
      return [
        'username' => $targetedUser['username'],
        'name'     => $targetedUser['name'],
        'surname'  => $targetedUser['surname'],
        'picture'  => $contactPicture,
        'status'   => $contact['status'],
      ];
    }
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
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    $nameValidation = Group::validateName($name);
    if ($nameValidation != Success::CODE) {
      return Utilities::generateErrorMessage($nameValidation);
    }
    
    if ($info != null) {
      $infoValidation = Group::validateInfo($info);
      
      if ($infoValidation != Success::CODE) {
        return Utilities::generateErrorMessage($infoValidation);
      }
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    $creatorId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $creator = DatabaseModule::fetchOne(
      'SELECT username, name, surname, picture
             FROM users
             WHERE user_id = BINARY :user_id',
      [
        ':user_id' => $creatorId,
      ]
    );
    
    // Retrieve creator picture
    $creatorFilepath = $creator['picture'];
    $creatorPicture = !is_null($creatorFilepath) ?
      MIMEService::researchMedia($creatorFilepath) : null;
    
    $membersId = [$creatorId];
    $members = [
      [
        "username" => $creator['username'],
        "name"     => $creator['name'],
        "surname"  => $creator['surname'],
        "picture"  => $creatorPicture,
      ],
    ];
    if (!is_null($users)) {
      // ==== Clear from duplicates ======================================================
      $users = array_unique($users, SORT_STRING);
      
      foreach ($users as $user) {
        // ==== Parameter validation =====================================================
        $userValidation = User::validateUsername($user);
        
        if ($userValidation != Success::CODE) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage($userValidation);
        }
        
        // ==== Existence check ==========================================================
        $member = DatabaseModule::fetchOne(
          'SELECT user_id, username, name, surname, picture
                 FROM users
                 WHERE username = BINARY :username',
          [
            ':username' => $user,
          ]
        );
        
        if (!is_array($member)) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(NotFound::CODE);
        }
        
        // ==== Retrieve member picture ==================================================
        $memberFilepath = $member['picture'];
        $memberPicture = !is_null($memberFilepath) ? MIMEService::researchMedia(
          $memberFilepath
        ) : null;
        
        $membersId[] = $member['user_id'];
        $members[] = [
          "username" => $member['username'],
          "name"     => $member['name'],
          "surname"  => $member['surname'],
          "picture"  => $memberPicture,
        ];
      }
    }
    
    // ==== Unique identifier creation ===================================================
    $groupUUID = DatabaseModule::fetchOne("SELECT UUID()")['UUID()'];
    
    // ==== Save image into the fs =======================================================
    $filepath = null;
    
    if (!is_null($picture)) {
      $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/group/$groupUUID";
      $filepath = MIMEService::createMedia($filepath, $picture);
      
      if (!is_string($filepath)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($filepath);
      }
    }
    
    // ==== Generate chat table and insert creator and users =============================
    
    $this->generateChatTable(
      $groupUUID,
      array_map(fn($member) => $member['username'], $members),
      [
        Wave::MAX_GROUP_PERMISSION,
        ...array_fill(0, count($members) - 1, Wave::NEW_MEMBER_PERMISSION),
      ]
    );
    
    // ==== Create a new group entity ====================================================
    
    DatabaseModule::execute(
      "INSERT
             INTO `groups` (name, info, picture, chat, active)
             VALUES (
               :name,
               :info,
               :picture,
               :chat,
               :active
             )",
      [
        ":name"    => $name,
        ":info"    => $info,
        ":picture" => $filepath,
        ":chat"    => $groupUUID,
        ":active"  => true,
      ]
    );
    
    // ==== Create new user/group relations ==============================================
    
    $defaultState = 'N';
    $defaultMuted = false;
    
    $groupId = DatabaseModule::fetchOne(
      "SELECT group_id
               FROM `groups`
               WHERE chat = :group",
      [
        ":group" => $groupUUID,
      ]
    )['group_id'];
    
    foreach ($membersId as $memberId) {
      DatabaseModule::execute(
        "INSERT
               INTO groups_members (user, `group`, state, muted, active)
               VALUES (
                 :user,
                 :group,
                 :state,
                 FALSE,
                 :active
               )",
        [
          ":user"   => $memberId,
          ":group"  => $groupId,
          ":state"  => $defaultState,
          //          ":muted"  => $defaultMuted,
          ":active" => true,
        ]
      );
    }
    
    DatabaseModule::commitTransaction();
    
    // ==== Send channel packet to all targeted members ==================================
    if (!is_null($users)) {
      WebSocketModule::sendChannelPacket(
        directive: "CREATE",
        topic    : "group",
        origin   : $creator['username'],
        target_s : $users,
        body     : [
                     "uuid"    => $groupUUID,
                     "name"    => $name,
                     "info"    => $info,
                     "picture" => $picture,
                     "members" => $members,
                   ]
      );
    }
    
    return [
      "uuid"    => $groupUUID,
      "name"    => $name,
      "info"    => $info,
      "picture" => $picture,
      "state"   => $defaultState,
      "muted"   => $defaultMuted,
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function getGroupInformation(
    string  $token,
    ?string $group = null,
  ): array {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    if (is_null($group)) {
      // ==== Groups existence check ======================================================
      $groups = DatabaseModule::fetchAll(
        'SELECT `group`, state, muted
             FROM groups_members
             WHERE active = TRUE
               AND user = :user',
        [
          ':user' => $userId,
        ]
      );
      
      if (!is_array($groups)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      $refactoredGroups = [];
      
      foreach ($groups as $group) {
        $databaseGroup = DatabaseModule::fetchOne(
          "SELECT name, info, picture, chat
                 FROM `groups`
                 WHERE active = TRUE
                   AND group_id = :group_id",
          [
            "group_id" => $group['group'],
          ]
        );
        
        $groupFilepath = $databaseGroup['picture'];
        $groupPicture = !is_null($groupFilepath) ? MIMEService::researchMedia(
          $groupFilepath
        ) : null;
        
        $refactoredGroups[] = [
          'uuid'    => $databaseGroup['chat'],
          'name'    => $databaseGroup['name'],
          'info'    => $databaseGroup['info'],
          'picture' => $groupPicture,
          'state'   => $group['state'],
          'muted'   => $group['muted'],
        ];
        
      }
      
      DatabaseModule::commitTransaction();
      return $refactoredGroups;
    } else {
      $groupValidation = Group::validateGroup($group);
      
      if ($groupValidation != Success::CODE) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($groupValidation);
      }
      
      // ==== Groups existence check ======================================================
      $group = DatabaseModule::fetchOne(
        'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
        [
          ':chat' => $group,
        ]
      );
      
      if (!is_array($group)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      // ==== Relation existence check ===================================================
      $membership = DatabaseModule::fetchOne(
        'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group',
        [
          ':group' => $group['group_id'],
        ]
      );
      
      DatabaseModule::commitTransaction();
      
      if (!is_array($membership)) {
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      // ==== Image retrieve and return ==================================================
      $groupFilepath = $group['picture'];
      $groupPicture ??= !is_null($groupFilepath) ? MIMEService::researchMedia(
        $groupFilepath
      ) : null;
      
      return [
        'uuid'    => $group['chat'],
        'name'    => $group['name'],
        'info'    => $group['info'],
        'picture' => $groupPicture,
        'state'   => $membership['state'],
        'muted'   => !!$membership['muted'],
      ];
    }
  }
  
  /**
   * @inheritDoc
   */
  public function changeGroupStatus(
    string $token,
    string $group,
    string $directive,
  ): array {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    $groupValidation = Group::validateGroup($group);
    
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    if ($groupValidation != Success::CODE) {
      return Utilities::generateErrorMessage($groupValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    // ==== Groups existence check ======================================================
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group,
      ]
    );
    
    if (!is_array($group)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Relation existence check ===================================================
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $membership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $userId,
      ]
    );
    
    if (!is_array($membership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Safe zone ====================================================================
    
    $oldState = $membership['state'];
    $oldMuted = !!$membership['muted'];
    
    switch ($directive) {
      // Archive
      case 'A':
        if ($oldState === 'A') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongState::CODE);
        }
        
        $newState = 'A';
        DatabaseModule::execute(
          'UPDATE groups_members
                 SET state = :state
                 WHERE active = TRUE
                   AND `group` = :group',
          [
            ":state" => $newState,
            ':group' => $group['group_id'],
          ]
        );
        break;
      
      // Pin
      case 'P':
        if ($oldState === 'P') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongState::CODE);
        }
        
        $newState = 'P';
        DatabaseModule::execute(
          'UPDATE groups_members
                 SET state = :state
                 WHERE active = TRUE
                   AND `group` = :group',
          [
            ":state" => $newState,
            ':group' => $group['group_id'],
          ]
        );
        break;
      
      // Mute
      case 'M':
        if ($oldMuted) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongState::CODE);
        }
        
        $newMuted = true;
        DatabaseModule::execute(
          'UPDATE groups_members
                 SET muted = :muted
                 WHERE active = TRUE
                   AND `group` = :group',
          [
            ":muted" => $newMuted,
            ':group' => $group['group_id'],
          ]
        );
        break;
      
      // Unarchive
      case 'Ua':
        if ($oldState !== 'A') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongState::CODE);
        }
        
        $newState = 'N';
        DatabaseModule::execute(
          'UPDATE groups_members
                 SET state = :state
                 WHERE active = TRUE
                   AND `group` = :group',
          [
            ":state" => $newState,
            ':group' => $group['group_id'],
          ]
        );
        break;
      
      // Unpin
      case 'Up':
        if ($oldState !== 'P') {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongState::CODE);
        }
        
        $newState = 'N';
        DatabaseModule::execute(
          'UPDATE groups_members
                 SET state = :state
                 WHERE active = TRUE
                   AND `group` = :group',
          [
            ":state" => $newState,
            ':group' => $group['group_id'],
          ]
        );
        break;
      
      // Unmute
      case 'Um':
        if (!$oldMuted) {
          DatabaseModule::commitTransaction();
          return Utilities::generateErrorMessage(WrongState::CODE);
        }
        
        $newMuted = false;
        DatabaseModule::execute(
          'UPDATE groups_members
                 SET muted = FALSE
                 WHERE active = TRUE
                   AND `group` = :group',
          [
            ':group' => $group['group_id'],
          ]
        );
        break;
      
      default:
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(WrongDirective::CODE);
    }
    
    DatabaseModule::commitTransaction();
    
    $groupFilepath = $group['picture'];
    $groupPicture = !is_null($groupFilepath) ? MIMEService::researchMedia($groupFilepath) : null;
    
    return [
      'uuid'    => $group['chat'],
      'name'    => $group['name'],
      'info'    => $group['info'],
      'picture' => $groupPicture,
      'state'   => $newState ?? $oldState,
      'muted'   => $newMuted ?? $oldMuted,
    ];
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
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    $groupValidation = Group::validateGroup($group);
    if ($groupValidation != Success::CODE) {
      return Utilities::generateErrorMessage($groupValidation);
    }
    
    // ==== Modular parameters validation and query preparation ==========================
    $variableUpdateQuery =
      '# noinspection
      UPDATE `groups` SET ';
    $variableAttributes = [];
    
    if ($name !== null) {
      $nameValidation = Group::validateName($name);
      
      if ($nameValidation != Success::CODE) {
        return Utilities::generateErrorMessage($nameValidation);
      }
      
      $variableUpdateQuery .= 'name = :name, ';
      $variableAttributes[':name'] = $name;
    }
    
    if ($info !== null) {
      $infoValidation = Group::validateInfo($info);
      
      if ($infoValidation != Success::CODE) {
        return Utilities::generateErrorMessage($infoValidation);
      }
      
      $variableUpdateQuery .= 'info = :info, ';
      $variableAttributes[':info'] = $info;
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    // ==== Groups existence check ======================================================
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group,
      ]
    );
    
    if (!is_array($group)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Relation existence check ===================================================
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $membership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $userId,
      ]
    );
    
    if (!is_array($membership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Save picture into the fs =====================================================
    if ($picture !== null) {
      $filepath = $_SERVER['DOCUMENT_ROOT'] . "filesystem/images/group/" . $group['chat'];
      $filepath = MIMEService::updateMedia($filepath, $picture);
      
      if (!is_string($filepath)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage($filepath);
      }
      
      $variableUpdateQuery .= 'picture = :picture, ';
      $variableAttributes[':picture'] = $filepath;
    }
    
    if (count($variableAttributes) == 0) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NullAttributes::CODE);
    }
    
    // ==== Origin permission check ======================================================
    
    if (!$this->authorizeChatMember(
      $group['chat'],
      $userId,
      [
        is_null($name) ? null : Permission::ChangeName,
        is_null($info) ? null : Permission::ChangeInfo,
        is_null($picture) ? null : Permission::ChangePicture,
      ]
    )) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(Forbidden::CODE);
    }
    
    // ==== Safe zone ====================================================================
    
    // cut last ", " and append WHERE clause
    $variableUpdateQuery = substr(
        $variableUpdateQuery,
        0,
        strlen($variableUpdateQuery) - 2
      ) . ' WHERE chat = :chat';
    $variableAttributes[':chat'] = $group['chat'];
    
    DatabaseModule::execute(
      $variableUpdateQuery,
      $variableAttributes
    );
    
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group['chat'],
      ]
    );
    
    // ==== Retrieve group members for channel ===========================================
    $origin = DatabaseModule::fetchOne(
      "SELECT username
             FROM users
             WHERE user_id = :user_id",
      [
        ":user_id" => $userId,
      ]
    )['username'];
    
    $members = DatabaseModule::fetchAll(
      "SELECT username
             FROM users
               INNER JOIN groups_members on users.user_id = groups_members.user
             WHERE groups_members.`group` = :group",
      [
        ":group" => $group['group_id'],
      ]
    );
    
    DatabaseModule::commitTransaction();
    
    $members = array_map(fn($member): string => $member['username'], $members);
    
    // ==== Image retrieve ===============================================================
    $groupFilepath = $group['picture'];
    $picture ??= !is_null($groupFilepath) ? MIMEService::researchMedia($groupFilepath) : null;
    
    // ==== Send channel packet to all members ===========================================
    WebSocketModule::sendChannelPacket(
      directive: "UPDATE",
      topic    : "group/information",
      origin   : $origin,
      target_s : $members,
      body     : [
                   "uuid"    => $group['chat'],
                   "name"    => $group['name'],
                   "info"    => $group['info'],
                   "picture" => $picture,
                 ]
    );
    
    // ==== Return new data to origin ====================================================
    return [
      'uuid'    => $group['chat'],
      'name'    => $group['name'],
      'info'    => $group['info'],
      'picture' => $picture,
      'state'   => $membership['state'],
      'muted'   => !!$membership['muted'],
    ];
  }
  
  /**
   * @inheritDoc
   */
  public function exitGroup(
    string $token,
    string $group,
  ): array {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    $groupValidation = Group::validateGroup($group);
    if ($groupValidation != Success::CODE) {
      return Utilities::generateErrorMessage($groupValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    // ==== Groups existence check ======================================================
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group,
      ]
    );
    
    if (!is_array($group)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Relation existence check ===================================================
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $membership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $userId,
      ]
    );
    
    if (!is_array($membership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Safe zone ====================================================================
    
    $members = DatabaseModule::fetchAll(
      "SELECT user_id, username
             FROM users
               INNER JOIN groups_members on users.user_id = groups_members.user
             WHERE groups_members.active = TRUE
               AND groups_members.`group` = :group",
      [
        ":group" => $group['group_id'],
      ]
    );
    
    // If origin is the last one
    if (count($members) === 1) {
      // ==== Delete group =====================================================
      DatabaseModule::execute(
        "UPDATE `groups`
               SET active = FALSE
               WHERE chat = :chat",
        [
          ":chat" => $group['chat'],
        ]
      );
    } else {
      // ==== Remove member ====================================================
      DatabaseModule::execute(
        "UPDATE `:name`
               SET active = FALSE
               WHERE user = :user",
        [
          ":name" => "chat_" . $group['chat'] . "_members",
          ":user" => $userId,
        ]
      );
      
      // ==== Send channel packet to remaining members =========================
      $origin = DatabaseModule::fetchOne(
        "SELECT username
             FROM users
             WHERE user_id = :user_id",
        [
          ":user_id" => $userId,
        ]
      )['username'];
      
      $members = array_map(fn($member): string => $member['username'], $members);
      $members = array_filter($members, fn($member): bool => $member !== $origin);
      
      WebSocketModule::sendChannelPacket(
        directive: "DELETE",
        topic    : "group/member",
        origin   : $origin,
        target_s : $members,
        body     : [
                     "uuid"     => $group['chat'],
                     "username" => $origin,
                   ]
      );
    }
    
    DatabaseModule::execute(
      "UPDATE groups_members
             SET active = FALSE
             WHERE user = :user
               AND `group` = :group",
      [
        ":user"  => $userId,
        ":group" => $group['group_id'],
      ]
    );
    
    DatabaseModule::commitTransaction();
    return $this->getGroupInformation($token);
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
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    $groupValidation = Group::validateGroup($group);
    if ($groupValidation != Success::CODE) {
      return Utilities::generateErrorMessage($groupValidation);
    }
    
    $userValidation = User::validateUsername($user);
    if ($userValidation != Success::CODE) {
      return Utilities::generateErrorMessage($userValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    // ==== Groups existence check ======================================================
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group,
      ]
    );
    
    if (!is_array($group)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Relation existence check ===================================================
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $membership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $userId,
      ]
    );
    
    if (!is_array($membership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Origin permission check ======================================================
    
    if (!$this->authorizeChatMember($group['chat'], $userId, Permission::AddPeople)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(Forbidden::CODE);
    }
    
    // ==== Target existence check =======================================================
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if (!is_array($targetedUser)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Target relation existence check ==============================================
    $targetMembership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $targetedUser['user_id'],
      ]
    );
    
    if (is_array($targetMembership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(AlreadyExist::CODE);
    }
    
    // ==== Safe zone ====================================================================
    $membersChatName = "chat_" . $group['chat'] . "_members";
    
    DatabaseModule::execute(
      "INSERT
               INTO `:name` (`user`, `last_seen_message`, `permissions`, `active`)
               VALUES (
                 :user,
                 :last_seen_message,
                 :permission,
                 :active
               )",
      [
        ':name'              => $membersChatName,
        ':user'              => $targetedUser['user_id'],
        ':last_seen_message' => null,
        ':permission'        => Wave::NEW_MEMBER_PERMISSION,
        ':active'            => true,
      ]
    );
    
    DatabaseModule::execute(
      "INSERT
               INTO groups_members (user, `group`, state, muted, active)
               VALUES (
                 :user,
                 :group,
                 :state,
                 FALSE,
                 :active
               )",
      [
        ":user"   => $targetedUser['user_id'],
        ":group"  => $group['group_id'],
        ":state"  => 'N',
        //          ":muted"  => $defaultMuted,
        ":active" => true,
      ]
    );
    
    // ==== New group members list retrieve ==============================================
    
    $members = DatabaseModule::fetchAll(
      "SELECT user, permissions, last_seen_message
             FROM `:name`
             WHERE active = TRUE",
      [
        ":name" => $membersChatName,
      ]
    );
    
    $members = array_map(function ($member) {
      $user = DatabaseModule::fetchOne(
        "SELECT username, name, surname, picture
               FROM users
               WHERE user_id = :user_id",
        [
          ":user_id" => $member['user'],
        ]
      );
      
      $filepath = $user['picture'];
      $picture = !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
      
      return [
        "username"          => $user['username'],
        "name"              => $user['name'],
        "surname"           => $user['surname'],
        "picture"           => $picture,
        "permissions"       => $member["permissions"],
        "last_seen_message" => $member["last_seen_message"],
      ];
    }, $members);
    
    DatabaseModule::commitTransaction();
    
    // ==== Send channel packet to the old group members =================================
    $oldMembers = array_map(
      fn($member): string => $member['username'],
      array_filter(
        $members,
        fn($member) => $member['username'] != $user
      )
    );
    
    $origin = DatabaseModule::fetchOne(
      "SELECT username
             FROM users
             WHERE user_id = :user_id",
      [
        ":user_id" => $userId,
      ]
    )['username'];
    
    $filepath = $targetedUser['picture'];
    $picture = !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
    
    WebSocketModule::sendChannelPacket(
      directive: "CREATE",
      topic    : "group/member",
      origin   : $origin,
      target_s : $oldMembers,
      body     : [
                   "username" => $targetedUser['username'],
                   "name"     => $targetedUser['name'],
                   "surname"  => $targetedUser['surname'],
                   "picture"  => $picture,
                 ]
    );
    
    // ==== Send channel packet to targeted user =========================================
    
    $groupFilepath = $group['picture'];
    $groupPicture = !is_null($groupFilepath) ? MIMEService::researchMedia($groupFilepath) : null;
    
    WebSocketModule::sendChannelPacket(
      directive: "CREATE",
      topic    : "group",
      origin   : $origin,
      target_s : $user,
      body     : [
                   "uuid"    => $group['chat'],
                   "name"    => $group['name'],
                   "info"    => $group['info'],
                   "picture" => $groupPicture,
                   "members" => $members,
                 ]
    );
    
    return $members;
  }
  
  /**
   * @inheritDoc
   */
  public function getMemberList(
    string  $token,
    string  $group,
    ?string $user = null,
  ): array {
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    $groupValidation = Group::validateGroup($group);
    if ($groupValidation != Success::CODE) {
      return Utilities::generateErrorMessage($groupValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    // ==== Groups existence check ======================================================
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group,
      ]
    );
    
    if (!is_array($group)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Relation existence check ===================================================
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $membership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $userId,
      ]
    );
    
    if (!is_array($membership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Safe zone ====================================================================
    $membersChatName = "chat_" . $group['chat'] . "_members";
    
    if (is_null($user)) {
      $members = DatabaseModule::fetchAll(
        "SELECT user, permissions, last_seen_message
             FROM `:name`
             WHERE active = TRUE",
        [
          ":name" => $membersChatName,
        ]
      );
      
      $members = array_map(function ($member) {
        $user = DatabaseModule::fetchOne(
          "SELECT username, name, surname, picture
               FROM users
               WHERE user_id = :user_id",
          [
            ":user_id" => $member['user'],
          ]
        );
        
        $filepath = $user['picture'];
        $picture = !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
        
        return [
          "username"          => $user['username'],
          "name"              => $user['name'],
          "surname"           => $user['surname'],
          "picture"           => $picture,
          "permissions"       => $member["permissions"],
          "last_seen_message" => $member["last_seen_message"],
        ];
      }, $members);
      
      DatabaseModule::commitTransaction();
      return $members;
    } else {
      // ==== Target existence check =======================================================
      $targetedUser = DatabaseModule::fetchOne(
        'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
        [
          ':username' => $user,
        ]
      );
      
      if (!is_array($targetedUser)) {
        DatabaseModule::commitTransaction();
        return Utilities::generateErrorMessage(NotFound::CODE);
      }
      
      $member = DatabaseModule::fetchOne(
        "SELECT permissions, last_seen_message
             FROM `:name`
             WHERE active = TRUE
               AND user = :user",
        [
          ":name" => $membersChatName,
          ":user" => $targetedUser['user_id'],
        ]
      );
      
      DatabaseModule::commitTransaction();
      
      $filepath = $targetedUser['picture'];
      $picture = !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
      
      return [
        "username"          => $targetedUser['username'],
        "name"              => $targetedUser['name'],
        "surname"           => $targetedUser['surname'],
        "picture"           => $picture,
        "permissions"       => $member["permissions"],
        "last_seen_message" => $member["last_seen_message"],
      ];
    }
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
    // ==== Parameters validation ========================================================
    $tokenValidation = Session::validateToken($token);
    if ($tokenValidation != Success::CODE) {
      return Utilities::generateErrorMessage($tokenValidation);
    }
    
    $groupValidation = Group::validateGroup($group);
    if ($groupValidation != Success::CODE) {
      return Utilities::generateErrorMessage($groupValidation);
    }
    
    $userValidation = User::validateUsername($user);
    if ($userValidation != Success::CODE) {
      return Utilities::generateErrorMessage($userValidation);
    }
    
    $permissionValidation = Member::validatePermission($permission);
    if ($permissionValidation != Success::CODE) {
      return Utilities::generateErrorMessage($permissionValidation);
    }
    
    // ==== Token authorization ==========================================================
    $tokenAuthorization = $this->authorizeToken($token);
    
    if ($tokenAuthorization != Success::CODE) {
      return Utilities::generateErrorMessage($tokenAuthorization);
    }
    
    // ===================================================================================
    DatabaseModule::beginTransaction();
    
    // ==== Groups existence check ======================================================
    $group = DatabaseModule::fetchOne(
      'SELECT group_id, name, info, picture, chat
             FROM `groups`
             WHERE active = TRUE
               AND chat = :chat',
      [
        ':chat' => $group,
      ]
    );
    
    if (!is_array($group)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Relation existence check ===================================================
    $userId = DatabaseModule::fetchOne(
      'SELECT user
             FROM sessions
             WHERE session_token = :session_token',
      [
        ':session_token' => $token,
      ]
    )['user'];
    
    $membership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $userId,
      ]
    );
    
    if (!is_array($membership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Target existence check =======================================================
    $targetedUser = DatabaseModule::fetchOne(
      'SELECT user_id, username, name, surname, picture
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $user,
      ]
    );
    
    if (!is_array($targetedUser)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Target relation existence check ==============================================
    $targetMembership = DatabaseModule::fetchOne(
      'SELECT state, muted
             FROM groups_members
             WHERE active = TRUE
               AND `group` = :group
               AND user = :user',
      [
        ':group' => $group['group_id'],
        ':user'  => $targetedUser['user_id'],
      ]
    );
    
    if (!is_array($targetMembership)) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(NotFound::CODE);
    }
    
    // ==== Origin permission check ======================================================
    
    if (!$this->authorizeChatMember(
      $group['chat'],
      $userId,
      Permission::ChangeOthersPermission
    )) {
      DatabaseModule::commitTransaction();
      return Utilities::generateErrorMessage(Forbidden::CODE);
    }
    
    // ==== Safe zone ====================================================================
    $membersChatName = "chat_" . $group['chat'] . "_members";
    
    DatabaseModule::execute(
      "UPDATE `:name`
             SET permissions = :permissions
             WHERE active = TRUE
               AND user = :user",
      [
        ":name"        => $membersChatName,
        ":permissions" => $permission,
        ":user"        => $targetedUser['user_id'],
      ]
    );
    
    // ==== Retrieve target data =========================================================
    $member = DatabaseModule::fetchOne(
      "SELECT permissions, last_seen_message
             FROM `:name`
             WHERE active = TRUE
               AND user = :user",
      [
        ":name" => $membersChatName,
        ":user" => $targetedUser['user_id'],
      ]
    );
    
    $filepath = $targetedUser['picture'];
    $picture = !is_null($filepath) ? MIMEService::researchMedia($filepath) : null;
    
    // ==== Send channel packet to all group members =====================================
    $origin = DatabaseModule::fetchOne(
      "SELECT username
             FROM users
             WHERE user_id = :user_id",
      [
        ":user_id" => $userId,
      ]
    )['username'];
    
    DatabaseModule::commitTransaction();
    
    $members = $this->getMemberList($token, $group['chat']);
    
    WebSocketModule::sendChannelPacket(
      directive: "UPDATE",
      topic    : "group/member",
      origin   : $origin,
      target_s : $members,
      headers  : [
                   "group"      => $group,
                   "username"   => $user,
                   "permission" => $permission,
                 ]
    );
    
    return [
      "username"          => $targetedUser['username'],
      "name"              => $targetedUser['name'],
      "surname"           => $targetedUser['surname'],
      "picture"           => $picture,
      "permissions"       => $member["permissions"],
      "last_seen_message" => $member["last_seen_message"],
    ];
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
    
    // ==== purge contact chat tables ====================================================
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
    
    // ==== purge contact chat tables ====================================================
    $groupChats = DatabaseModule::fetchAll('SELECT chat FROM `groups` WHERE active = FALSE');
    
    foreach ($groupChats as $groupChat) {
      DatabaseModule::execute(
        'DROP TABLE `:name`',
        [
          ':name' => 'chat_' . $groupChat['chat'] . '_messages',
        ]
      );
      DatabaseModule::execute(
        'DROP TABLE `:name`',
        [
          ':name' => 'chat_' . $groupChat['chat'] . '_members',
        ]
      );
    }
    
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
}