<?php

namespace Wave\Tests\Services\Database\Module;

use PHPUnit\Framework\TestCase;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Utilities\Utilities;

/**
 * @author Giacomo Guidotto
 */
class DatabaseModuleTest extends TestCase {
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== Database module =========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
  }
  
  public function testCorrectInstance() {
    echo PHP_EOL . 'Testing correct database connection...' . PHP_EOL;
    
    $testedModule = DatabaseModule::getInstance();
    
    self::assertInstanceOf(DatabaseModule::class, $testedModule);
  }
  
  public function testFetch() {
    echo PHP_EOL . 'Testing database queries...' . PHP_EOL;
    
    $validModule = DatabaseModule::getInstance();
    
    $validModule->instanceBeginTransaction();
    $databaseName = $validModule->instanceFetchOne(
      'SELECT DATABASE()'
    )['DATABASE()'];
    $validModule->instanceCommitTransaction();
    
    self::assertEquals(
      'wave',
      $databaseName
    );
  }
  
  public function testFetchShortcut() {
    echo PHP_EOL . 'Testing database queries for module shortcuts...' . PHP_EOL;
    
    DatabaseModule::beginTransaction();
    $databaseName = DatabaseModule::fetchOne(
      'SELECT DATABASE()'
    )['DATABASE()'];
    DatabaseModule::commitTransaction();
    
    self::assertEquals(
      'wave',
      $databaseName
    );
  }
  
  public function testChatGeneration() {
    echo PHP_EOL . 'Testing chat table generation...' . PHP_EOL;
    
    $chatMessagesUUID = "chat_messages_" . Utilities::generateUuid();
    $chatMembersUUID = "chat_members_" . Utilities::generateUuid();
    
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
        ':name' => $chatMessagesUUID,
      ]
    );
    
    DatabaseModule::execute(
      'CREATE TABLE `:name`
            (
              `member_id`         INTEGER     NOT NULL PRIMARY KEY AUTO_INCREMENT,
              `user`              INTEGER     NOT NULL,
              `last_seen_message` VARCHAR(36) NOT NULL CHECK ( LENGTH(`last_seen_message`) > 35 ),
              `permissions`       SMALLINT    NOT NULL,
              `active`            BOOLEAN     NOT NULL,
              FOREIGN KEY (user)
                  REFERENCES users (user_id)
                  ON DELETE CASCADE
            )',
      [
        ':name' => $chatMembersUUID,
      ]
    );
    
    DatabaseModule::execute(
      'DROP TABLE `:name`',
      [
        ':name' => $chatMessagesUUID,
      ]
    );
    
    DatabaseModule::execute(
      'DROP TABLE `:name`',
      [
        ':name' => $chatMembersUUID,
      ]
    );
    self::assertTrue(true);
  }
}