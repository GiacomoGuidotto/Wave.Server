<?php

namespace Wave\Model\User;

/**
 * UserInterface resource
 * Set of static methods for the user's attributes validations
 */
interface UserInterface {
  /**
   * Check the constrains of the username attribute
   *
   * @param string $username the username to check
   * @return int             either the error code or the success code
   */
  public static function validateUsername(string $username): int;
  
  /**
   * Check the constrains of the password attribute
   *
   * @param string $password the password to check
   * @return int             either the error code or the success code
   */
  public static function validatePassword(string $password): int;
  
  /**
   * Check the constrains of the name attribute
   *
   * @param string $name the name to check
   * @return int         either the error code or the success code
   */
  public static function validateName(string $name): int;
  
  /**
   * Check the constrains of the surname attribute
   *
   * @param string $surname the surname to check
   * @return int            either the error code or the success code
   */
  public static function validateSurname(string $surname): int;
  
  /**
   * Check the constrains of the picture attribute
   *
   * @param string $picture the picture to check
   * @return int            either the error code or the success code
   */
  public static function validatePicture(string $picture): int;
  
  /**
   * Check the constrains of the phone attribute
   *
   * @param string $phone the phone to check
   * @return int          either the error code or the success code
   */
  public static function validatePhone(string $phone): int;
  
  /**
   * Check the constrains of the theme attribute
   *
   * @param string $theme the theme to check
   * @return int          either the error code or the success code
   */
  public static function validateTheme(string $theme): int;
  
  /**
   * Check the constrains of the language attribute
   *
   * @param string $language the language to check
   * @return int             either the error code or the success code
   */
  public static function validateLanguage(string $language): int;
}