<?php

namespace Wave\Tests\Services\Database\Module;

use PHPUnit\Framework\TestCase;
use Wave\Services\Database\Module\DatabaseModule;

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
}