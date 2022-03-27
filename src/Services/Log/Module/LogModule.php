<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\Log\Module;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Wave\Model\Singleton\Singleton;

class LogModule extends Singleton {
  private static StreamHandler $defaultStream;
  private static StreamHandler $errorStream;
  
  private static array $defaultLoggers = [];
  private static array $errorLoggers = [];
  
  protected function __construct() {
    self::$defaultStream = new StreamHandler(
      $_SERVER['DOCUMENT_ROOT'] . 'filesystem/logs/wave.log'
    );
    self::$errorStream = new StreamHandler(
      $_SERVER['DOCUMENT_ROOT'] . 'filesystem/logs/error.log'
    );
  }
  
  private static function writeLog(Logger $logger, string $intensity, string $message) {
    switch (strtolower($intensity)) {
      case 'debug':
        $logger->debug($message);
        break;
      case 'info':
        $logger->info($message);
        break;
      case 'notice':
        $logger->notice($message);
        break;
      case 'warning':
        $logger->warning($message);
        break;
      case 'error':
        $logger->error($message);
        break;
      case 'critical':
        $logger->critical($message);
        break;
      case 'alert':
        $logger->alert($message);
        break;
      case 'emergency':
        $logger->emergency($message);
        break;
      default:
        break;
    }
    
  }
  
  /**
   * Log into wave.log through the channel defined from the source
   *
   * @param string $source    The call class, that will be translated in a specialized channel
   * @param string $method    The definer of the use case
   * @param string $message   The log message
   * @param string $intensity The optional intensity of the message
   * @return void
   */
  public static function log(
    string $source,
    string $method,
    string $message,
    string $intensity = 'info'
  ) {
    LogModule::getInstance();
    
    // if new source, create a new default logger for it
    if (!isset(self::$defaultLoggers[$source])) {
      self::$defaultLoggers[$source] = new Logger($source);
      self::$defaultLoggers[$source]->pushHandler(self::$defaultStream);
    }
    
    $logger = self::$defaultLoggers[$source];
    
    self::writeLog($logger, $intensity, "[$method] \t$message");
  }
  
  /**
   * Log the errors into error.log through the channel defined from the source
   *
   * @param string $source    The call class, that will be translated in a specialized channel
   * @param string $method    The definer of the use case
   * @param string $message   The log message
   * @param string $intensity The optional intensity of the message
   * @return void
   */
  public static function errorLog(
    string $source,
    string $method,
    string $message,
    string $intensity = 'info'
  ) {
    LogModule::getInstance();
    
    // if new source, create a new default logger for it
    if (!isset(self::$errorLoggers[$source])) {
      self::$errorLoggers[$source] = new Logger($source);
      self::$errorLoggers[$source]->pushHandler(self::$errorStream);
    }
    
    $logger = self::$errorLoggers[$source];
    
    self::writeLog($logger, $intensity, "[$method] \t$message");
  }
}