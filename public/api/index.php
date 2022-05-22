<?php
/**
 * @author Giacomo Guidotto
 */

require '../../vendor/autoload.php';

use Wave\Services\Cors\CorsService;

$method = $_SERVER['REQUEST_METHOD'];

// ==== CorsService check =================================================================

CorsService::handle('auth');

if ($method == 'OPTIONS') return;

echo json_encode(
  [
    'Title' => 'Wave REST API',
    'Info'  => 'This is the root of the server API, follow the documentation for browsing it',
  ]
);