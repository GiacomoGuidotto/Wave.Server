<?php /** @noinspection SqlResolve */

namespace Wave\Tests\Utilities;

use Wave\Services\Database\Module\DatabaseModule;

class TestUtilities {
  
  /**
   * Delete the generated tables from the contacts of the given user
   *
   * @param string $username The user's username
   * @param bool   $contact  If the chat were generated from a contact or a group
   * @return void
   */
  public static function deleteGeneratedTables(string $username, bool $contact = false): void {
    $userId = DatabaseModule::fetchOne(
      'SELECT user_id
             FROM users
             WHERE username = BINARY :username',
      [
        ':username' => $username,
      ]
    )['user_id'];
    
    if ($contact) {
      $chats = DatabaseModule::fetchAll(
        'SELECT chat
             FROM contacts
             WHERE (first_user = :user_id
                OR second_user = :user_id)',
        [
          ':user_id' => $userId,
        ]
      );
    } else {
      $chats = DatabaseModule::fetchAll(
        'SELECT chat
             FROM `groups`
              INNER JOIN groups_members on `groups`.group_id = groups_members.`group`
             WHERE groups_members.user = :user',
        [
          ':user' => $userId,
        ]
      );
    }
    
    foreach ($chats as $chat) {
      DatabaseModule::execute(
        'DROP TABLE `:name`',
        [
          ':name' => 'chat_' . $chat['chat'] . '_messages',
        ]
      );
      DatabaseModule::execute(
        'DROP TABLE `:name`',
        [
          ':name' => 'chat_' . $chat['chat'] . '_members',
        ]
      );
    }
  }
}