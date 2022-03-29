<?php

namespace Wave\Services\Log;

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