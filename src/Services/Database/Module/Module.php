<?php

namespace Wave\Services\Database\Module;

use PDO;
use PDOStatement;
use Wave\Model\Singleton\Singleton;
use Wave\Specifications\Database\Database;

/**
 * Database module
 *
 * Access point to the MySQL database
 */
class Module extends Singleton {
  private PDO $database;
  
  /**
   * Module constructor, called only on the first run.
   *
   * Initialize the database connection
   */
  protected function __construct() {
    $this->database = new PDO(
        "mysql:host=" . Database::SERVER_NAME . ";dbname=" . Database::DATABASE_NAME,
        Database::DATABASE_USER,
        Database::DATABASE_USER_PASSWORD
    );
    
    $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  
  
  private function executeQuery(string $query, array $params = null): bool|PDOStatement {
    $statement = $this->database->prepare($query);
    
    if ($params != null) {
      foreach ($params as $reference => &$value) {
        $statement->bindParam($reference, $value);
      }
    }
    
    $statement->execute();
    
    return $statement;
  }
  
  // ==== Access methods ===========================================================================
  
  /**
   * Begin the transaction on the private db reference
   *
   * @return bool
   */
  public function beginTransaction(): bool {
    return $this->database->beginTransaction();
  }
  
  /**
   * Commit the transaction on the private db reference
   *
   * @return bool
   */
  public function commitTransaction(): bool {
    return $this->database->commit();
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and return the first row fetched
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   * @return array|false the set of attributes in the row, false in case of empty
   */
  public function fetchOne(string $query, array $params = null): array|false {
    $statement = $this->executeQuery($query, $params);
    
    return $statement->fetch();
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and return all the rows fetched
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   * @return array|false the list of row, false in case of empty
   */
  public function fetchAll(string $query, array $params = null): array|false {
    $statement = $this->executeQuery($query, $params);
    
    return $statement->fetchAll();
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and doesn't return the result
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   */
  public function execute(string $query, array $params = null): void {
    $this->executeQuery($query, $params);
  }
  
  // ==== Static shortcuts =========================================================================
  
  /**
   * Begin the transaction on the private db reference
   *
   * @return bool
   */
  public static function staticTransaction(): bool {
    $module = static::getInstance();
    return $module->beginTransaction();
  }
  
  /**
   * Commit the transaction on the private db reference
   *
   * @return bool
   */
  public static function staticCommit(): bool {
    $module = static::getInstance();
    return $module->commitTransaction();
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and return the first row fetched
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   * @return array|false the set of attributes in the row, false in case of empty
   */
  public static function staticFetchOne(string $query, array $params = null): array|false {
    $module = static::getInstance();
    return $module->fetchOne($query, $params);
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and return all the rows fetched
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   * @return array|false the list of row, false in case of empty
   */
  public static function staticFetchAll(string $query, array $params = null): array|false {
    $module = static::getInstance();
    return $module->fetchAll($query, $params);
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and doesn't return the result
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   */
  public static function staticExecute(string $query, array $params = null): void {
    $module = static::getInstance();
    $module->execute($query, $params);
  }
}