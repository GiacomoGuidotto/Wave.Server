<?php

namespace Wave\Services\Log;

/**
 * Log's intensity
 * The set of possible intensity, or states, of a log inside the .log files
 *
 * @author Giacomo Guidotto
 */
enum Intensity {
  case debug;
  case info;
  case notice;
  case warning;
  case error;
  case critical;
  case alert;
  case emergency;
}