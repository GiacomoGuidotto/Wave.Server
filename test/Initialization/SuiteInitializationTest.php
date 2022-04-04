<?php

namespace Wave\Tests\Initialization;

use PHPUnit\Framework\TestCase;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use Wave\Services\ZeroMQ\ZeroMQModule;

class SuiteInitializationTest extends TestCase {
  private static LoopInterface $loop;
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== Wave test suite =========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$loop = Loop::get();
  }
  
  public function testZeroMQSocketsInstance() {
    echo PHP_EOL . 'Initialization ZeroMQ sockets...' . PHP_EOL;
    
    $result = ZeroMQModule::getInstance(self::$loop);
    
    self::assertInstanceOf(ZeroMQModule::class, $result);
  }
}