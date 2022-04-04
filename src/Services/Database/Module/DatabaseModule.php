<?php /** @noinspection PhpMissingParentConstructorInspection */

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
class DatabaseModule extends Singleton {
  private PDO $database;
  
  /**
   * DatabaseModule constructor, called only on the first run.
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
      // remove exceeding params
      $params = array_slice($params, 0, substr_count($query, ':'));
      
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
  public function instanceBeginTransaction(): bool {
    return $this->database->beginTransaction();
  }
  
  
  /**
   * Check if a transaction is in progress
   *
   * @return bool
   */
  public function instanceInTransaction(): bool {
    return $this->database->inTransaction();
  }
  
  /**
   * Commit the transaction on the private db reference
   *
   * @return bool
   */
  public function instanceCommitTransaction(): bool {
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
  public function instanceFetchOne(string $query, array $params = null): array|false {
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
  public function instanceFetchAll(string $query, array $params = null): array|false {
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
  public function instanceExecute(string $query, array $params = null): void {
    $this->executeQuery($query, $params);
  }
  
  // ==== Static shortcuts =========================================================================
  
  /**
   * Begin the transaction on the private db reference
   *
   * @return bool
   */
  public static function beginTransaction(): bool {
    $module = static::getInstance();
    return $module->instanceBeginTransaction();
  }
  
  /**
   * Check if a transaction is in progress
   *
   * @return bool
   */
  public static function inTransaction(): bool {
    $module = static::getInstance();
    return $module->instanceInTransaction();
  }
  
  /**
   * Commit the transaction on the private db reference
   *
   * @return bool
   */
  public static function commitTransaction(): bool {
    $module = static::getInstance();
    return $module->instanceCommitTransaction();
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
  public static function fetchOne(string $query, array $params = null): array|false {
    $module = static::getInstance();
    return $module->instanceFetchOne($query, $params);
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
  public static function fetchAll(string $query, array $params = null): array|false {
    $module = static::getInstance();
    return $module->instanceFetchAll($query, $params);
  }
  
  /**
   * Execute the query passed as parameters
   * with the optional parameters as array
   * and doesn't return the result
   *
   * @param string     $query  the query to execute
   * @param array|null $params the optional parameters
   */
  public static function execute(string $query, array $params = null): void {
    $module = static::getInstance();
    $module->instanceExecute($query, $params);
  }
}