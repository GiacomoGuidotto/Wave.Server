<?php

namespace Wave\Model\Member;

/**
 * Member resource
 * Set of static methods for the member's attributes validations
 *
 * @author Giacomo Guidotto
 */
interface MemberInterface {
  /**
   * Check the constrains of the permission attribute
   *
   * @param int $permission the permission to check
   * @return int            either the error code or the success code
   */
  public static function validatePermission(int $permission): int;
}