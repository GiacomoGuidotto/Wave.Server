<?php

namespace Wave\Model\Member;

use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaximum;
use Wave\Specifications\ErrorCases\Type\ExceedingMinimum;

/**
 * Member resource class
 * The implementation of the Member interface
 */
class MemberImpl implements Member {
  
  /**
   * @inheritDoc
   */
  public static function validatePermission(int $permission): int {
    if ($permission > 127) {
      return ExceedingMaximum::CODE;
    }
    if ($permission < 0) {
      return ExceedingMinimum::CODE;
    }
    
    return Success::CODE;
  }
}