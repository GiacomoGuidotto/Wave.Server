<?php

namespace Wave\Model\Group;

/**
 * Group resource
 * Set of static methods for the group's attributes validations
 */
interface Group {
  /**
   * Check the constrains of the name attribute
   *
   * @param string $name the name to check
   * @return int         either the error code or the success code
   */
  public static function validateName(string $name): int;
  
  /**
   * Check the constrains of the info attribute
   *
   * @param string $info the info to check
   * @return int         either the error code or the success code
   */
  public static function validateInfo(string $info): int;
  
  /**
   * Check the constrains of the picture attribute
   *
   * @param string $picture the picture to check
   * @return int            either the error code or the success code
   */
  public static function validatePicture(string $picture): int;
  
  /**
   * Check the constrains of the chat attribute
   *
   * @param string $chat the chat to check
   * @return int         either the error code or the success code
   */
  public static function validateChat(string $chat): int;
  
  /**
   * Check the constrains of the state attribute
   *
   * @param string $state the state to check
   * @return int          either the error code or the success code
   */
  public static function validateState(string $state): int;
}