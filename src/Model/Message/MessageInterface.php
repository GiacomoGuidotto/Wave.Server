<?php

namespace Wave\Model\Message;

/**
 * MessageInterface resource
 * Set of static methods for the user's attributes validations
 */
interface MessageInterface {
  /**
   * Check the constrains of the key attribute
   *
   * @param string $key the key to check
   * @return int        either the error code or the success code
   */
  public static function validateKey(string $key): int;
  
  /**
   * Check the constrains of the timestamp attribute
   *
   * @param string $timestamp the timestamp to check
   * @return int              either the error code or the success code
   */
  public static function validateTimestamp(string $timestamp): int;
  
  /**
   * Check the constrains of the content attribute
   *
   * @param string $content the content to check
   * @return int            either the error code or the success code
   */
  public static function validateContent(string $content): int;
  
  /**
   * Check the constrains of the text attribute
   *
   * @param string $text the text to check
   * @return int         either the error code or the success code
   */
  public static function validateText(string $text): int;
  
  /**
   * Check the constrains of the media attribute
   *
   * @param string $media the media to check
   * @return int          either the error code or the success code
   */
  public static function validateMedia(string $media): int;
}