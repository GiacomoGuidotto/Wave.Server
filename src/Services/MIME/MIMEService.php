<?php

namespace Wave\Services\MIME;

/**
 * MIME service
 *
 * Save MIMEs, specifically images, in the filesystem and translate them in filepath
 */
interface MIMEService {
  
  /**
   * Retrieve an image from the filesystem
   *
   * Use the given filepath for locating and returning the image from the filesystem
   *
   * @param string $filepath The requested image's filepath
   * @return string|int      The decoded image or the error code
   */
  public static function retrieveImage(string $filepath): string|int;
  
  /**
   * Save a user's profile picture into the filesystem
   *
   * @param string $image    The decoded image
   * @param string $username The user's username
   * @return string|int      The generated filepath or the error code
   */
  public static function saveUserImage(string $image, string $username): string|int;
  
  /**
   * Save a group's picture into the filesystem
   *
   * @param string $image The decoded image
   * @param string $group The group's UUID
   * @return string|int   The generated filepath or the error code
   */
  public static function saveGroupImage(string $image, string $group): string|int;
  
  /**
   * Save a message's media into the filesystem
   *
   * @param string $image   The decoded image
   * @param string $message The message's key
   * @return string|int     The generated filepath or the error code
   */
  public static function saveMessageMedia(string $image, string $message): string|int;
}