<?php
require '../../vendor/autoload.php';

use Wave\Services\Cors\Cors;
use Wave\Services\Database\DatabaseServiceImpl;
use Wave\Specifications\ErrorCases\ErrorCases;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;


$service = DatabaseServiceImpl::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

// ==== Cors check =================================================================
Cors::handle('auth');

if ($method == 'OPTIONS') return;

// ==== Invalid methods checks =====================================================
$validMethods = ['POST', 'PUT', 'DELETE'];

if (!in_array($method, $validMethods)) {
  http_response_code(405);
  return;
}

$headers = getallheaders();

// ==== POST case ==================================================================
// =================================================================================
if ($method == 'POST') {
  
  // ==== Get parameters =========================================================
  $username = $headers['username'];
  $password = $headers['password'];
  $source = $headers['source'];
  
  // ==== Null check =============================================================
  if ($username == null || $password == null || $source == null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      $service->generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->login($username, $password, $source);
  
  // ==== Error case =============================================================
  if ($result['error'] != null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  echo json_encode($result);
  return;
}

// ==== PUT case ===================================================================
// =================================================================================
if ($method == 'PUT') {
  
  // ==== Get parameters =========================================================
  $token = $headers['token'];
  
  // ==== Null check =============================================================
  if ($token == null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      $service->generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->poke($token);
  
  // ==== Error case =============================================================
  if ($result['error'] != null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  return;
}

// ==== DELETE case ================================================================
// =================================================================================
if ($method == 'DELETE') {
  
  // ==== Get parameters =========================================================
  $token = $headers['token'];
  
  // ==== Null check =============================================================
  if ($token == null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      $service->generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->logout($token);
  
  // ==== Error case =============================================================
  if ($result['error'] != null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  return;
}