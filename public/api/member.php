<?php
require '../../vendor/autoload.php';

use Wave\Services\Cors\CorsService;
use Wave\Services\Database\DatabaseService;
use Wave\Specifications\ErrorCases\ErrorCases;
use Wave\Specifications\ErrorCases\Generic\NullAttributes;
use Wave\Utilities\Utilities;


$service = DatabaseService::getInstance();

$method = $_SERVER['REQUEST_METHOD'];

// ==== CorsService check ================================================================
CorsService::handle('auth');

if ($method == 'OPTIONS') return;

// ==== Invalid methods checks ===========================================================
$validMethods = ['POST', 'GET', 'PUT', 'DELETE'];

if (!in_array($method, $validMethods)) {
  http_response_code(405);
  return;
}

$headers = getallheaders();
$body = json_decode(file_get_contents('php://input'), JSON_OBJECT_AS_ARRAY);

// ==== POST case ========================================================================
// =======================================================================================
if ($method == 'POST') {
  // ==== Get parameters =======================================================
  $token = $headers['token'] ?? null;
  $group = $headers['group'] ?? null;
  $user = $headers['user'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group) || is_null($user)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->addMember(
    $token,
    $group,
    $user,
  );
  
  // ==== Error case ===========================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case =========================================================
  echo json_encode($result);
  return;
}

// ==== GET case =========================================================================
// =======================================================================================
if ($method == 'GET') {
  // ==== Get parameters =======================================================
  $token = $headers['token'] ?? null;
  $group = $headers['group'] ?? null;
  $user = $headers['user'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->getMemberList(
    $token,
    $group,
    $user,
  );
  
  // ==== Error case ===========================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case =========================================================
  echo json_encode($result);
  return;
}

// ==== PUT case =========================================================================
// =======================================================================================
if ($method == 'PUT') {
  // ==== Get parameters =======================================================
  $token = $headers['token'] ?? null;
  $group = $headers['group'] ?? null;
  $user = $headers['user'] ?? null;
  $permission = $headers['permission'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group) || is_null($user) || is_null($permission)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->changeMemberPermission(
    $token,
    $group,
    $user,
    $permission
  );
  
  // ==== Error case ===========================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case =========================================================
  echo json_encode($result);
  return;
  
}

// ==== DELETE case ======================================================================
// =======================================================================================
if ($method == 'DELETE') {
  // ==== Get parameters =======================================================
  $token = $headers['token'] ?? null;
  $group = $headers['group'] ?? null;
  $user = $headers['user'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group) || is_null($user)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->removeMember(
    $token,
    $group,
    $user,
  );
  
  // ==== Error case ===========================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case =========================================================
  echo json_encode($result);
  return;
}