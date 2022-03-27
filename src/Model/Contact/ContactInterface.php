<?php

namespace Wave\Model\Contact;

/**
 * ContactInterface resource
 * Set of static methods for the contact's attributes validations
 */
interface ContactInterface {
  /**
   * Check the constrains of the status attribute
   *
   * @param string $status the status to check
   * @return int           either the error code or the success code
   */
  public static function validateStatus(string $status): int;
}