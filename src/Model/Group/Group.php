<?php

namespace Wave\Model\Group;

/**
 * Group resource
 * Set of static methods for the group's attributes validations
 */
interface Group {
  /**
   * Check the constrains of the uuid attribute.
   *
   * @param string $group The uuid to check.
   * @return int          Either the error code or the success code.
   */
  public static function validateGroup(string $group): int;
  
  /**
   * Check the constrains of the name attribute.
   *
   * @param string $name The name to check.
   * @return int         Either the error code or the success code.
   */
  public static function validateName(string $name): int;
  
  /**
   * Check the constrains of the info attribute.
   *
   * @param string $info The info to check.
   * @return int         Either the error code or the success code.
   */
  public static function validateInfo(string $info): int;
  
  /**
   * Check the constrains of the picture attribute.
   *
   * @param string $picture The picture to check.
   * @return int            Either the error code or the success code.
   */
  public static function validatePicture(string $picture): int;
  
  /**
   * Check the constrains of the chat attribute.
   *
   * @param string $chat The chat to check.
   * @return int         Either the error code or the success code.
   */
  public static function validateChat(string $chat): int;
  
  /**
   * Check the constrains of the state attribute.
   *
   * @param string $state The state to check.
   * @return int          Either the error code or the success code.
   */
  public static function validateState(string $state): int;
}