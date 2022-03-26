<?php

namespace Services\MIME\Module;

use PHPUnit\Framework\TestCase;
use Wave\Model\User\UserImpl;
use Wave\Services\MIME\Module\MIMEModule;
use Wave\Specifications\ErrorCases\Success\Success;

class MIMEModuleTest extends TestCase {
  public static function setUpBeforeClass(): void {
    echo '==== MIME module =============================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
  }
  
  public function testCorrectMediaRetrieve(): string {
    echo PHP_EOL . 'Testing correct media retrieve...' . PHP_EOL;
    
    $filepath = '../../../../assets/icons/favicon.png';
    
    $result = MIMEModule::retrieveMedia($filepath);
    
    echo 'Result: ' . substr($result, 0, 50) . '...' . PHP_EOL;
    
    self::assertIsString($result);
    
    return $result;
  }
  
  /**
   * @depends testCorrectMediaRetrieve
   */
  public function testCorrectMediaInsertion(string $media): string {
    echo PHP_EOL . 'Testing correct media insertion...' . PHP_EOL;
    
    $newFilepath = '../../../../filesystem/tests/test';
    
    $result = MIMEModule::saveMedia($newFilepath, $media);
    
    echo 'Result: ' . $result . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      UserImpl::validatePicture($result),
    );
    
    return $result;
  }
  
  /**
   * @depends testCorrectMediaInsertion
   */
  public function testCorrectMediaDeletion(
    string $filepath
  ) {
    echo PHP_EOL . 'Testing correct media deletion...' . PHP_EOL;
    
    $result = MIMEModule::deleteMedia($filepath);
    
    echo 'Result: ' . $result . PHP_EOL;
    
    self::assertNull($result);
  }
}
