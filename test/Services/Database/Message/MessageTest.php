<?php

namespace Wave\Tests\Services\Database\Message;

use PHPUnit\Framework\TestCase;
use Wave\Model\Message\Message;
use Wave\Model\User\User;
use Wave\Services\Database\DatabaseService;
use Wave\Services\Database\Module\DatabaseModule;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Specifications\ErrorCases\Mime\IncorrectFileType;
use Wave\Specifications\ErrorCases\State\Forbidden;
use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Tests\Utilities\TestUtilities;
use Wave\Utilities\Utilities;

/**
 * @author Giacomo Guidotto
 */
class MessageTest extends TestCase {
  protected static DatabaseService $service;
  
  private static array $firstUser = [
    'username' => 'first_user',
    'password' => 'Fr6/ese342f',
    'name'     => 'first',
    'surname'  => 'user',
    'source'   => null,
    'token'    => null,
  ];
  
  private static array $secondUser = [
    'username' => 'second_user',
    'password' => 'Fr6/ese342f',
    'name'     => 'second',
    'surname'  => 'user',
    'source'   => null,
    'token'    => null,
  ];
  
  private static array $group = [
    'uuid' => null,
    'name' => 'test_group',
    'info' => 'Testing group',
  ];
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== Contact =================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$service = DatabaseService::getInstance();
    
    // ==== generate second user dummy source
    self::$firstUser['source'] = Utilities::generateUuid();
    
    // ==== generate first user dummy source
    self::$secondUser['source'] = Utilities::generateUuid();
  }
  
  protected function setUp(): void {
    // ==== generate first dummy user
    self::$service->createUser(
      self::$firstUser['username'],
      self::$firstUser['password'],
      self::$firstUser['name'],
      self::$firstUser['surname'],
    );
    
    // ==== generate first dummy token
    self::$firstUser['token'] = self::$service->login(
      self::$firstUser['username'],
      self::$firstUser['password'],
      self::$firstUser['source'],
    )['token'];
    
    // ==== generate second dummy user
    self::$service->createUser(
      self::$secondUser['username'],
      self::$secondUser['password'],
      self::$secondUser['name'],
      self::$secondUser['surname'],
    );
    
    // ==== generate second dummy token
    self::$secondUser['token'] = self::$service->login(
      self::$secondUser['username'],
      self::$secondUser['password'],
      self::$secondUser['source'],
    )['token'];
    
    // ==== generate dummy contact
    self::$service->contactRequest(
      self::$firstUser['token'],
      self::$secondUser['username'],
    );
    
    self::$service->changeContactStatus(
      self::$secondUser['token'],
      self::$firstUser['username'],
      'A'
    );
    
    // ==== generate dummy group
    self::$group['uuid'] = self::$service->createGroup(
      self::$firstUser['token'],
      self::$group['name'],
      self::$group['info'],
      null,
      [
        self::$secondUser['username'],
      ]
    )['uuid'];
  }
  
  // ==== writeMessage =============================================================================
  // ===============================================================================================
  
  /**
   * @group writeMessage
   */
  public function testWriteMessageInContactCorrectProcedure(): array {
    echo PHP_EOL . '==== writeMessage ============================================' . PHP_EOL;
    
    echo PHP_EOL . 'Testing write message in contact correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $result = self::$service->writeMessage(
      self::$firstUser['token'],
      null,
      self::$secondUser['username'],
      null,
      $randomMessage
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Message::validateKey($result['key']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateTimestamp($result['timestamp']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateContent($result['content']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateText($result['text']),
    );
    self::assertNull($result['media']);
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['authorUsername']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['authorName']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['authorSurname']),
    );
    self::assertNull($result['authorPicture']);
    self::assertIsBool($result['pinned']);
    
    return $result;
  }
  
  /**
   * @group writeMessage
   */
  public function testWriteMessageInGroupCorrectProcedure(): array {
    echo PHP_EOL . 'Testing write message in group correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $result = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Message::validateKey($result['key']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateTimestamp($result['timestamp']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateContent($result['content']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateText($result['text']),
    );
    self::assertNull($result['media']);
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['authorUsername']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['authorName']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['authorSurname']),
    );
    self::assertNull($result['authorPicture']);
    self::assertIsBool($result['pinned']);
    
    return $result;
  }
  
  /**
   * @group writeMessage
   */
  public function testWriteMessageInBothGroupAndContact(): array {
    echo PHP_EOL . 'Testing write message in group correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $result = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      self::$secondUser['username'],
      null,
      $randomMessage
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      NullAttributes::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group writeMessage
   */
  public function testWriteMessageWithMContent(): array {
    echo PHP_EOL . 'Testing write message with content...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $result = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      "M",
      $randomMessage
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Message::validateKey($result['key']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateTimestamp($result['timestamp']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateContent($result['content']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateText($result['text']),
    );
    self::assertNull($result['media']);
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['authorUsername']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['authorName']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['authorSurname']),
    );
    self::assertNull($result['authorPicture']);
    self::assertIsBool($result['pinned']);
    
    return $result;
  }
  
  /**
   * @group writeMessage
   */
  public function testWriteMessageWithImageContent(): array {
    echo PHP_EOL . 'Testing write message in group correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $result = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      "I",
      $randomMessage,
      Utilities::generateString()
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectFileType::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  // ==== getMessages ==============================================================================
  // ===============================================================================================
  
  /**
   * @group getMessages
   */
  public function testGetMessageCorrectProcedure(): ?array {
    echo PHP_EOL . 'Testing get message correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->getMessages(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      null,
      null,
      $message
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Message::validateKey($result['key']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateTimestamp($result['timestamp']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateContent($result['content']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateText($result['text']),
    );
    self::assertNull($result['media']);
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['authorUsername']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['authorName']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['authorSurname']),
    );
    self::assertNull($result['authorPicture']);
    self::assertIsBool($result['pinned']);
    
    return $result;
  }
  
  /**
   * @group getMessages
   */
  public function testGetMessagesCorrectProcedure(): ?array {
    echo PHP_EOL . 'Testing get message list correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    $secondRandomMessage = 'Second random message';
    
    $now = DatabaseModule::fetchOne("SELECT CURRENT_TIMESTAMP()")['CURRENT_TIMESTAMP()'];
    // isolate DD from "YYYY-MM-DD HH-MM-SS"
    $day = intval(explode("-", explode(" ", $now)[0])[2]);
    
    $from = str_replace("$day ", $day - 1 . " ", $now);
    $to = str_replace("$day ", $day + 1 . " ", $now);
    
    self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    );
    
    self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $secondRandomMessage
    );
    
    $result = self::$service->getMessages(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      $from,
      $to,
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    return $result;
  }
  
  /**
   * @group getMessages
   */
  public function testGetPinnedMessagesCorrectProcedure(): ?array {
    echo PHP_EOL . 'Testing get pinned messages correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    $secondRandomMessage = 'Second random message';
    
    self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    );
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $secondRandomMessage
    )['key'];
    
    self::$service->changeMessage(
      self::$firstUser['token'],
      $message,
      self::$group['uuid'],
      null,
      null,
      null,
      null,
      true,
    );
    
    $result = self::$service->getMessages(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      null,
      true
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    return $result;
  }
  
  /**
   * @group getMessages
   */
  public function testGetPinnedRangeMessagesCorrectProcedure(): ?array {
    echo PHP_EOL . 'Testing get message list correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    $secondRandomMessage = 'Second random message';
    
    $now = DatabaseModule::fetchOne("SELECT CURRENT_TIMESTAMP()")['CURRENT_TIMESTAMP()'];
    // isolate DD from "YYYY-MM-DD HH-MM-SS"
    $day = intval(explode("-", explode(" ", $now)[0])[2]);
    
    $from = str_replace("$day ", $day - 1 . " ", $now);
    $to = str_replace("$day ", $day + 1 . " ", $now);
    
    self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    );
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $secondRandomMessage
    )['key'];
    
    self::$service->changeMessage(
      self::$firstUser['token'],
      $message,
      self::$group['uuid'],
      null,
      null,
      null,
      null,
      true,
    );
    
    $result = self::$service->getMessages(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      $from,
      $to,
      true
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertIsArray($result);
    
    return $result;
  }
  
  // ==== changeMessage ============================================================================
  // ===============================================================================================
  
  /**
   * @group changeMessage
   */
  public function testChangeMessageCorrectProcedure(): array {
    echo PHP_EOL . 'Testing change message correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->changeMessage(
      self::$firstUser['token'],
      $message,
      self::$group['uuid'],
      null,
      null,
      "New random message"
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Message::validateKey($result['key']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateTimestamp($result['timestamp']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateContent($result['content']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateText($result['text']),
    );
    self::assertNull($result['media']);
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['authorUsername']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['authorName']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['authorSurname']),
    );
    self::assertNull($result['authorPicture']);
    self::assertIsBool($result['pinned']);
    
    return $result;
  }
  
  /**
   * @group changeMessage
   */
  public function testChangeMessageWithImageContent(): array {
    echo PHP_EOL . 'Testing write message in group correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      null,
      self::$secondUser['username'],
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->changeMessage(
      self::$firstUser['token'],
      $message,
      null,
      self::$secondUser['username'],
      "I",
      null,
      Utilities::generateString()
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      IncorrectFileType::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group changeMessage
   */
  public function testChangeMessageWithUnauthorizedUser(): array {
    echo PHP_EOL . 'Testing change message with unauthorized user...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->changeMessage(
      self::$secondUser['token'],
      $message,
      self::$group['uuid'],
      null,
      "M",
      "New random message",
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Forbidden::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  /**
   * @group changeMessage
   */
  public function testPinMessageCorrectProcedure(): array {
    echo PHP_EOL . 'Testing change message correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->changeMessage(
      self::$firstUser['token'],
      $message,
      self::$group['uuid'],
      null,
      null,
      null,
      null,
      true
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      Message::validateKey($result['key']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateTimestamp($result['timestamp']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateContent($result['content']),
    );
    self::assertEquals(
      Success::CODE,
      Message::validateText($result['text']),
    );
    self::assertNull($result['media']);
    self::assertEquals(
      Success::CODE,
      User::validateUsername($result['authorUsername']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateName($result['authorName']),
    );
    self::assertEquals(
      Success::CODE,
      User::validateSurname($result['authorSurname']),
    );
    self::assertNull($result['authorPicture']);
    self::assertIsBool($result['pinned']);
    
    return $result;
  }
  
  // ==== deleteMessage ============================================================================
  // ===============================================================================================
  
  /**
   * @group deleteMessage
   */
  public function testDeleteMessageCorrectProcedure(): ?array {
    echo PHP_EOL . 'Testing delete message correct procedure...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->deleteMessage(
      self::$firstUser['token'],
      $message,
      self::$group['uuid'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertNull($result);
    
    return $result;
  }
  
  /**
   * @group deleteMessage
   */
  public function testDeleteMessageWithUnauthorizedUser(): array {
    echo PHP_EOL . 'Testing delete message with unauthorized user...' . PHP_EOL;
    
    $randomMessage = 'Random message';
    
    $message = self::$service->writeMessage(
      self::$firstUser['token'],
      self::$group['uuid'],
      null,
      null,
      $randomMessage
    )['key'];
    
    $result = self::$service->deleteMessage(
      self::$secondUser['token'],
      $message,
      self::$group['uuid'],
    );
    
    echo 'Result: ' . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    
    self::assertEquals(
      Forbidden::CODE,
      $result['error'],
    );
    
    return $result;
  }
  
  protected function tearDown(): void {
    TestUtilities::deleteGeneratedTables(self::$firstUser['username'], true);
    TestUtilities::deleteGeneratedTables(self::$firstUser['username']);
    
    DatabaseModule::execute(
      'DELETE FROM `groups`
             WHERE name = :group_name',
      [
        ':group_name' => self::$group['name'],
      ]
    );
    
    DatabaseModule::execute(
      'DELETE FROM users
             WHERE username = :first_username
                OR username = :second_username',
      [
        ':first_username' => self::$firstUser['username'],
        ':second_username' => self::$secondUser['username'],
      ]
    );
  }
}