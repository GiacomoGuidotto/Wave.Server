<?php

namespace Wave\Services\Log\Module;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Wave\Services\Log\LogModule;

class LogModuleTest extends TestCase {
  private static ReflectionClass $reflection;
  
  public static function setUpBeforeClass(): void {
    echo PHP_EOL . '==== Logging module ==========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$reflection = new ReflectionClass(static::class);
  }
  
  public function testInfoLog() {
    echo PHP_EOL . 'Testing correct logging procedure...' . PHP_EOL;
    
    LogModule::log(
      self::$reflection->getShortName(),
      "testing logging procedure",
      "test message"
    );
    
    self::assertTrue(true);
  }
  
  public function testErrorLog() {
    echo PHP_EOL . 'Testing correct error logging procedure...' . PHP_EOL;
    
    LogModule::log(
      self::$reflection->getShortName(),
      "testing error logging procedure",
      "test message",
      true
    );
    
    self::assertTrue(true);
  }
}
