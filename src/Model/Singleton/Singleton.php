<?php

namespace Wave\Model\Singleton;

use Exception;

/**
 * Singleton base class
 *
 * Define the basic features of the singleton pattern, like the getInstance and the list of
 * instances, while moving the actual business logic to subclasses.
 *
 * @see https://refactoring.guru/design-patterns/singleton/php/example
 */
class Singleton {

  /**
   * Array of instances, resides inside static fields.
   * Each subclass of Singleton stores its own instance.
   */
  private static array $instances = [];

  /**
   * Singleton's constructor, protected to allow subclassing.
   */
  protected function __construct() {}


  /**
   * Cloning not allowed for singletons.
   */
  protected function __clone() {}

  /**
   * Unserialization not allowed for singletons.
   *
   * @throws Exception
   */
  public function __wakeup() {
    throw new Exception("Cannot unserialize singleton");
  }

  /**
   * Retrieve the Singleton's instance.
   */
  public static function getInstance() {
    $subclass = static::class;

    // first run
    if (!isset(self::$instances[$subclass])) {
      // "static" keyword replace literally the subclass name
      self::$instances[$subclass] = new static();
    }

    return self::$instances[$subclass];
  }
}