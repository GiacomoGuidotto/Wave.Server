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
  
  public function testCorrectMediaInsertion(): string {
    echo PHP_EOL . 'Testing correct media insertion...' . PHP_EOL;
    
    $path = '../../../../assets/icons/favicon.png';
    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);
    $media = 'data:image/' . $type . ';base64,' . base64_encode($data);
    
    $filepath = '../../../../filesystem/tests/test';
    
    $result = MIMEModule::saveMedia($filepath, $media);
    
    echo 'Result: ' . $result . PHP_EOL;
    
    self::assertEquals(
      Success::CODE,
      UserImpl::validatePicture($result),
    );
    
    return $result;
  }
}
