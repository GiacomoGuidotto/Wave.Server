<?php

namespace Wave\Utilities;

use FilesystemIterator;
use JetBrains\PhpStorm\ArrayShape;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Wave\Specifications\ErrorCases\ErrorCases;

class Utilities {
  
  /**
   * Generate the error message associate in the error cases, given the code
   *
   * @param int $code The error code
   * @return array    The object as array of the error message
   */
  #[ArrayShape([
    'timestamp' => "string",
    'error'     => "int",
    'message'   => "string",
    'details'   => "string",
  ])] public static function generateErrorMessage(int $code): array {
    return [
      'timestamp' => date('Y-m-d H:i:s'),
      'error'     => $code,
      'message'   => ErrorCases::ERROR_MESSAGES[$code],
      'details'   => ErrorCases::ERROR_DETAILS[$code],
    ];
  }
  
  /**
   * Generate a random string for attribute testing
   *
   * @param int $length The optional string length
   * @return string     The generated string
   */
  public static function generateString(int $length = 8): string {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
  }
  
  /**
   * Generate a version 4 uuid
   *
   * @return string The uuid
   */
  public static function generateUuid(): string {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      
      // 32 bits for "time_low"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      
      // 16 bits for "time_mid"
      mt_rand(0, 0xffff),
      
      // 16 bits for "time_hi_and_version",
      // four most significant bits holds version number 4
      mt_rand(0, 0x0fff) | 0x4000,
      
      // 16 bits, 8 bits for "clk_seq_hi_res",
      // 8 bits for "clk_seq_low",
      // two most significant bits holds zero and one for variant DCE1.1
      mt_rand(0, 0x3fff) | 0x8000,
      
      // 48 bits for "node"
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
  
  public static function deleteDirectory($path) {
    $it = new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator(
      $it,
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
      if ($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }
    rmdir($path);
  }
}