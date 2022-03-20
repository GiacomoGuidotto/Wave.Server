<?php

namespace Wave\Model\Member;

use Wave\Specifications\ErrorCases\Integer\ExceedingMaxRange;
use Wave\Specifications\ErrorCases\Integer\ExceedingMinRange;
use Wave\Specifications\ErrorCases\Success\Success;

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
      return ExceedingMaxRange::CODE;
    }
    if ($permission < 0) {
      return ExceedingMinRange::CODE;
    }
    
    return Success::CODE;
  }
}