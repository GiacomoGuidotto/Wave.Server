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
  $username = $headers['username'];
  $password = $headers['password'];
  $name = $headers['name'];
  $surname = $headers['surname'];
  $phone = $headers['phone'];
  $picture = $headers['picture'];
  
  // ==== Null check =============================================================
  if ($username == null || $password == null || $name == null || $surname == null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      $service->generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ============================================================
  $result = $service->createUser($username, $password, $name, $surname, $phone, $picture);
  
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

// ==== GET case ===================================================================
// =================================================================================
if ($method == 'GET') {
  
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
  $result = $service->getUserInformation($token);
  
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
  $username = $headers['username'];
  $name = $headers['name'];
  $surname = $headers['surname'];
  $phone = $headers['phone'];
  $picture = $headers['picture'];
  $theme = $headers['theme'];
  $language = $headers['language'];
  
  // ==== Null check =============================================================
  if ($token == null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      $service->generateErrorMessage(NullAttributes::CODE)
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
  if ($result['error'] != null) {
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
  $result = $service->deleteUser($token);
  
  // ==== Error case =============================================================
  if ($result['error'] != null) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case ===========================================================
  return;
}