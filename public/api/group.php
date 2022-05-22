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

// ==== CorsService check ================================================================
CorsService::handle('group');

if ($method == 'OPTIONS') return;

// ==== Invalid methods checks ===========================================================
$validMethods = ['POST', 'GET', 'PATCH', 'PUT', 'DELETE'];

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
  $name = $headers['name'] ?? null;
  $info = $headers['info'] ?? null;
  $users = $headers['users'] ?? null;
  $picture = $body['picture'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($name)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $users = !is_null($users) ? array_map(
    fn($user): string => trim($user, "[] \n\r\t\v\x00"),
    explode(",", $users)
  ) : null;
  
  $result = $service->createGroup(
    $token,
    $name,
    $info,
    $picture,
    $users,
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
  
  // ==== Null check ===========================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->getGroupInformation(
    $token,
    $group,
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

// ==== PATCH case =======================================================================
// =======================================================================================
if ($method == 'PATCH') {
  // ==== Get parameters =======================================================
  $token = $headers['token'] ?? null;
  $group = $headers['group'] ?? null;
  $directive = $headers['directive'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group) || is_null($directive)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->changeGroupStatus(
    $token,
    $group,
    $directive
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
  $name = $headers['name'] ?? null;
  $info = $headers['info'] ?? null;
  $picture = $body['picture'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->changeGroupInformation(
    $token,
    $group,
    $name,
    $info,
    $picture
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
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($group)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->exitGroup(
    $token,
    $group,
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