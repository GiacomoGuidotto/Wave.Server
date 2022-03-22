<?php

namespace Services\Database\Module;

use PHPUnit\Framework\TestCase;
use Wave\Services\Database\Module\Module;

class ModuleTest extends TestCase {
  public function testCorrectInstance() {
    $testedModule = Module::getInstance();
    
    self::assertInstanceOf(Module::class, $testedModule);
  }
  
  public function testFetch() {
    $validModule = Module::getInstance();
    
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
    Module::beginTransaction();
    $databaseName = Module::fetchOne(
      'SELECT DATABASE()'
    )['DATABASE()'];
    Module::commitTransaction();
    
    self::assertEquals(
      'wave',
      $databaseName
    );
  }
}