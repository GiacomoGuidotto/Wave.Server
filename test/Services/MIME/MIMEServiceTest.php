<?php

namespace Wave\Tests\Services\MIME;

use PHPUnit\Framework\TestCase;
use Wave\Model\User\User;
use Wave\Services\MIME\MIMEService;
use Wave\Specifications\ErrorCases\Success\Success;

/**
 * @author Giacomo Guidotto
 */
class MIMEServiceTest extends TestCase {
  private static string $documentRoot;
  
  public static function setUpBeforeClass(): void {
    echo '==== MIME module =============================================' . PHP_EOL
      . '==============================================================' . PHP_EOL;
    
    self::$documentRoot = explode('/test', __DIR__)[0];
  }
  
  public function testCorrectMediaRetrieve(): string {
    echo PHP_EOL . 'Testing correct media retrieve...' . PHP_EOL;
    
    $filepath = self::$documentRoot . '/public/assets/icons/favicon.png';
    
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
    
    $newFilepath = self::$documentRoot . '/filesystem/tests/test';
    
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
