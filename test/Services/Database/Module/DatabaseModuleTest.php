<?php

namespace Services\Database\Module;

use PHPUnit\Framework\TestCase;
use Wave\Services\Database\Module\DatabaseModule;

class DatabaseModuleTest extends TestCase {
  public static function setUpBeforeClass(): void {
    echo '==== Database module =========================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
  }
  
  public function testCorrectInstance() {
    $testedModule = DatabaseModule::getInstance();
    
    self::assertInstanceOf(DatabaseModule::class, $testedModule);
  }
  
  public function testFetch() {
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
}