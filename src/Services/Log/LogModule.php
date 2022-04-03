<?php /** @noinspection PhpMissingParentConstructorInspection */

namespace Wave\Services\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Wave\Model\Singleton\Singleton;
use Wave\Specifications\Logging\Logging;

/**
 * Logging module
 *
 * Module for the management of the .log files
 */
class LogModule extends Singleton {
  private static StreamHandler $defaultStream;
  private static StreamHandler $errorStream;
  
  private static array $defaultLoggers = [];
  private static array $errorLoggers = [];
  
  protected function __construct() {
    // Create .log files streams
    self::$defaultStream = new StreamHandler(
      $_SERVER['DOCUMENT_ROOT'] . 'filesystem/logs/wave.log',
      Logger::DEBUG
    );
    self::$errorStream = new StreamHandler(
      $_SERVER['DOCUMENT_ROOT'] . 'filesystem/logs/error.log',
      Logger::DEBUG
    );
    // Set .log files format
    $formatter = new LineFormatter(Logging::LOG_MESSAGE_FORMAT, Logging::LOG_DATE_FORMAT);
    self::$defaultStream->setFormatter($formatter);
    self::$errorStream->setFormatter($formatter);
  }
  
  private static function writeLog(
    Logger    $logger,
    Intensity $intensity,
    string    $message
  ): void {
    switch ($intensity) {
      case Intensity::debug:
        $logger->debug($message);
        break;
      case Intensity::info:
        $logger->info($message);
        break;
      case Intensity::notice:
        $logger->notice($message);
        break;
      case Intensity::warning:
        $logger->warning($message);
        break;
      case Intensity::error:
        $logger->error($message);
        break;
      case Intensity::critical:
        $logger->critical($message);
        break;
      case Intensity::alert:
        $logger->alert($message);
        break;
      case Intensity::emergency:
        $logger->emergency($message);
        break;
    }
  }
  
  /**
   * Log into wave.log through the channel defined from the source
   *
   * @param string    $source    The call class, that will be translated in a specialized channel
   * @param string    $method    The definer of the use case
   * @param string    $message   The log message
   * @param bool      $error     The flag for write in the error.log file
   * @param Intensity $intensity The optional intensity of the message
   * @return void
   */
  public static function log(
    string    $source,
    string    $method,
    string    $message,
    bool      $error = false,
    Intensity $intensity = Intensity::info
  ): void {
    LogModule::getInstance();
    
    // if it isn't an error bind the default logger array and stream
    if (!$error) {
      $loggers = &self::$defaultLoggers;
      $stream = &self::$defaultStream;
    } else {
      $loggers = &self::$errorLoggers;
      $stream = &self::$errorStream;
      $intensity = Intensity::error;
    }
    
    // if new source, create a new default logger for it
    if (!isset($loggers[$source])) {
      $loggers[$source] = new Logger($source);
      $loggers[$source]->pushHandler($stream);
    }
    
    $logger = $loggers[$source];
    
    self::writeLog(
      $logger,
      $intensity,
      "[$method] | $message"
    );
  }
}