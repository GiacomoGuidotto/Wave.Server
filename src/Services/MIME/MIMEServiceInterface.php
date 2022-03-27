<?php

namespace Wave\Services\MIME;

/**
 * MIME service
 *
 * CRUD-based service for MultipurposeInternetMailExtension (MIME) management
 */
interface MIMEServiceInterface {
  /**
   * Validate, prepare, decode the media's data for its insertion into the filesystem
   *
   * @param string $filepath The desired filepath
   * @param string $media    The media's data
   * @return int|string      Its final filepath
   */
  public static function createMedia(
    string $filepath,
    string $media,
  ): int|string;
  
  /**
   * Encode and prepare a media retrieved from the filesystem of a specified validated filepath
   *
   * @param string $filepath The specified filepath
   * @return int|string      The encoded media
   */
  public static function researchMedia(
    string $filepath,
  ): int|string;
  
  /**
   * Substitute the media's data from the filesystem to the specified filepath
   *
   * @param string $filepath The desired filepath
   * @param string $media    The media's data
   * @return int|string      Its final filepath
   */
  public static function updateMedia(
    string $filepath,
    string $media,
  ): int|string;
  
  /**
   * Delete a media from the filesystem of a targeted validated filepath
   *
   * @param string $filepath The targeted filepath
   * @return int|null        The eventual error code
   */
  public static function deleteMedia(
    string $filepath,
  ): ?int;
}