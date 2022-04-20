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
CorsService::handle('auth');

if ($method == 'OPTIONS') return;

// ==== Invalid methods checks =====================================================
$validMethods = ['POST', 'GET', 'PUT', 'DELETE'];

if (!in_array($method, $validMethods)) {
  http_response_code(405);
  return;
}

$headers = getallheaders();
$body = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

// ==== POST case ==================================================================
// =================================================================================
if ($method == 'POST') {
  
  // ==== Get parameters =========================================================
  $username = $headers['username'] ?? null;
  $password = $headers['password'] ?? null;
  $name = $headers['name'] ?? null;
  $surname = $headers['surname'] ?? null;
  $picture = $body['picture'] ?? null;
  $phone = $headers['phone'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($username) || is_null($password) || is_null($name) || is_null($surname)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->createUser(
    $username,
    $password,
    $name,
    $surname,
    $phone,
    $picture,
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
  
  // ==== Null check =============================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->getUserInformation($token);
  
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
  $username = $headers['username'] ?? null;
  $name = $headers['name'] ?? null;
  $surname = $headers['surname'] ?? null;
  $phone = $headers['phone'] ?? null;
  $picture = $body['picture'] ?? null;
  $theme = $headers['theme'] ?? null;
  $language = $headers['language'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->changeUserInformation(
    $token,
    $username,
    $name,
    $surname,
    $phone,
    $picture,
    $theme,
    $language,
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

// ==== DELETE case ================================================================
// =================================================================================
if ($method == 'DELETE') {
  
  // ==== Get parameters =========================================================
  $token = $headers['token'] ?? null;
  
  // ==== Null check =============================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->deleteUser($token);
  
  // ==== Error case =============================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  return;
}