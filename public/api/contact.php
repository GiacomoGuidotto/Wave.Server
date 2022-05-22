<?php
/**
 * @author Giacomo Guidotto
 */

require '../../vendor/autoload.php';

use Wave\Services\Cors\CorsService;
use Wave\Services\Database\DatabaseService;
use Wave\Specifications\ErrorCases\ErrorCases;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Utilities\Utilities;


$service = DatabaseService::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

// ==== CorsService check =================================================================
CorsService::handle('contact');

if ($method == 'OPTIONS') return;

// ==== Invalid methods checks =====================================================
$validMethods = ['POST', 'GET', 'PUT', 'DELETE'];

if (!in_array($method, $validMethods)) {
  http_response_code(405);
  return;
}

$headers = getallheaders();

// ==== POST case ==================================================================
// =================================================================================
if ($method == 'POST') {
  // ==== Get parameters =========================================================
  $token = $headers['token'] ?? null;
  $user = $headers['user'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($token) || is_null($user)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->contactRequest(
    $token,
    $user,
  );
  
  // ==== Error case =============================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  echo json_encode($result);
  return;
}

// ==== GET case ===================================================================
// =================================================================================
if ($method == 'GET') {
  // ==== Get parameters =========================================================
  $token = $headers['token'] ?? null;
  $user = $headers['user'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->getContactInformation(
    $token,
    $user,
  );
  
  // ==== Error case =============================================================
  if (isset($result['error'])) {
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
  $token = $headers['token'] ?? null;
  $user = $headers['user'] ?? null;
  $directive = $headers['directive'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($token) || is_null($user) || is_null($directive)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->changeContactStatus(
    $token,
    $user,
    $directive
  );
  
  // ==== Error case =============================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  if (!is_null($result)) echo json_encode($result);
  return;
}

// ==== DELETE case ================================================================
// =================================================================================
if ($method == 'DELETE') {
  // ==== Get parameters =========================================================
  $token = $headers['token'] ?? null;
  $user = $headers['user'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($token) || is_null($user)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->deleteContactRequest(
    $token,
    $user,
  );
  
  // ==== Error case =============================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  return;
}