<?php

namespace Wave\Model\Member;

use Wave\Specifications\ErrorCases\Success\Success;
use Wave\Specifications\ErrorCases\Type\ExceedingMaximum;
use Wave\Specifications\ErrorCases\Type\ExceedingMinimum;
use Wave\Specifications\Wave\Wave;

/**
 * Member resource class
 * The implementation of the Member interface
 *
 * @author Giacomo Guidotto
 */
class Member implements MemberInterface {
  
  /**
   * @inheritDoc
   */
  public static function validatePermission(int $permission): int {
    if ($permission > Wave::MAX_GROUP_PERMISSION) {
      return ExceedingMaximum::CODE;
    }
    if ($permission < 0) {
      return ExceedingMinimum::CODE;
    }
    
    return Success::CODE;
  }
}