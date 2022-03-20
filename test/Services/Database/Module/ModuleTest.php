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

    $validModule->beginTransaction();
    $databaseName = $validModule->fetchOne(
        'SELECT DATABASE()'
    )['DATABASE()'];
    $validModule->commitTransaction();

    self::assertEquals(
        'wave',
        $databaseName
    );
  }

  public function testFetchShortcut() {
    Module::staticTransaction();
    $databaseName = Module::staticFetchOne(
        'SELECT DATABASE()'
    )['DATABASE()'];
    Module::staticCommit();

    self::assertEquals(
        'wave',
        $databaseName
    );
  }
}
