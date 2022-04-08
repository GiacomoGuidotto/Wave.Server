<?php /** @noinspection SqlResolve */

namespace Wave\Tests\Utilities;

use Wave\Services\Database\Module\DatabaseModule;

class TestUtilities {
  
  /**
   * Delete the generated tables from the contacts of the given user
   *
   * @param string $username The user's username
   * @return void
   */
  public static function deleteGeneratedTables(string $username): void {
    $userId = DatabaseModule::fetchOne(
      'SELECT user_id
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $username,
      ]
    )['user_id'];
    
    $contactChat = DatabaseModule::fetchOne(
      'SELECT chat
             FROM contacts
             WHERE (first_user = :user_id
                OR second_user = :user_id)',
      [
        ':user_id' => $userId,
      ]
    )['chat'];
    
    DatabaseModule::execute(
      'DROP TABLE `:name`',
      [
        ':name' => 'chat_' . $contactChat . '_messages',
      ]
    );
    DatabaseModule::execute(
      'DROP TABLE `:name`',
      [
        ':name' => 'chat_' . $contactChat . '_members',
      ]
    );
  }
}