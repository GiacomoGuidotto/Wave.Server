<?php

namespace Wave\Model\Session;

/**
 * SessionInterface resource
 * Set of static methods for the session's attributes validations
 */
interface SessionInterface {
  /**
   * Checks the constrains of the token attribute
   *
   * @param string $token the token to check
   * @return int          either the error code or the success code
   */
  public static function validateToken(string $token): int;
  
  /**
   * Checks the constrains of the source token
   *
   * @param string $source the source to check
   * @return int           either the error code or the success code
   */
  public static function validateSource(string $source): int;
  
  /**
   * Checks the constrains of the creation timestamp attribute
   *
   * @param string $timestamp the timestamp to check
   * @return int              either the error code or the success code
   */
  public static function validateCreationTimestamp(string $timestamp): int;
  
  /**
   * Checks the constrains of the last update timestamp attribute
   *
   * @param string $timestamp the timestamp to check
   * @return int              either the error code or the success code
   */
  public static function validateLastUpdated(string $timestamp): int;
}