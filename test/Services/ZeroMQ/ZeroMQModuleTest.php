<?php

namespace Wave\Tests\Services\ZeroMQ;

use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Wave\Services\ZeroMQ\ZeroMQModule;

/**
 * @author Giacomo Guidotto
 */
class ZeroMQModuleTest extends TestCase {
  private static LoopInterface $loop;
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== ZeroMQ module ===========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$loop = Loop::get();
  }
  
  public function testCorrectInstance() {
    echo PHP_EOL . 'Testing correct ZeroMQ sockets initialization...' . PHP_EOL;
    
    $result = ZeroMQModule::getInstance(self::$loop);
    
    self::assertInstanceOf(ZeroMQModule::class, $result);
  }
  
  public function testSendingMessage() {
    echo PHP_EOL . 'Testing correct procedure for sending messages...' . PHP_EOL;
    
    $result = ZeroMQModule::getInstance();
    
    $result->bindCallback([$this, 'simulatedCallback']);
    
    $result->sendData(['test' => 'data']);
    
    self::assertTrue(true);
  }
  
  public function simulatedCallback($packet) {
    echo 'ZeroMQ sent message: ' . $packet . PHP_EOL;
  }
  
  public static function tearDownAfterClass(): void {
    self::$loop->addTimer(1, function () {
      self::$loop->stop();
    });
  }
}
