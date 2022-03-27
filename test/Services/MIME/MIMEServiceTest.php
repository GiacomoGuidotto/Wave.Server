<?php

namespace Services\MIME;

use PHPUnit\Framework\TestCase;
use Wave\Model\User\User;
use Wave\Services\MIME\MIMEService;
use Wave\Specifications\ErrorCases\Success\Success;

class MIMEServiceTest extends TestCase {
  public static function setUpBeforeClass(): void {
    echo '==== MIME module =============================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
  }
  
  public function testCorrectMediaRetrieve(): string {
    echo PHP_EOL . 'Testing correct media retrieve...' . PHP_EOL;
    
    $filepath = '../../../assets/icons/favicon.png';
    
    $result = MIMEService::researchMedia($filepath);
    
    echo 'Result: ' . substr($result, 0, 50);
    
    self::assertIsString($result);
    
    echo '...' . PHP_EOL;
    
    return $result;
  }
  
  /**
   * @depends testCorrectMediaRetrieve
   */
  public function testCorrectMediaInsertion(string $media): string {
    echo PHP_EOL . 'Testing correct media insertion...' . PHP_EOL;
    
    $newFilepath = '../../../filesystem/tests/test';
    
    $result = MIMEService::createMedia($newFilepath, $media);
    
    echo 'Result: ' . $result . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      User::validatePicture($result),
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
    
    $result = MIMEService::deleteMedia($filepath);
    
    echo 'Result: ' . $result . PHP_EOL;
    
    self::assertNull($result);
  }
}
