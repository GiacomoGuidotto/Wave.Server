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
CorsService::handle('message');

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
  $contact = $headers['contact'] ?? null;
  $content = $headers['content'] ?? null;
  $text = $headers['text'] ?? null;
  $media = $body['media'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->writeMessage(
    $token,
    $group,
    $contact,
    $content,
    $text,
    $media
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
  $contact = $headers['contact'] ?? null;
  $from = $headers['from'] ?? null;
  $to = $headers['to'] ?? null;
  $pinned = $headers['pinned'] ?? null;
  $message = $headers['message'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $pinned = !is_null($pinned) ?
    filter_var($pinned, FILTER_VALIDATE_BOOLEAN) :
    null;
  
  $result = $service->getMessages(
    $token,
    $group,
    $contact,
    $from,
    $to,
    $pinned,
    $message,
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
  $message = $headers['message'] ?? null;
  $group = $headers['group'] ?? null;
  $contact = $headers['contact'] ?? null;
  $content = $headers['content'] ?? null;
  $text = $headers['text'] ?? null;
  $media = $body['media'] ?? null;
  $pinned = $headers['pinned'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($message)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $pinned = !is_null($pinned) ?
    filter_var($pinned, FILTER_VALIDATE_BOOLEAN) :
    null;
  
  $result = $service->changeMessage(
    $token,
    $message,
    $group,
    $contact,
    $content,
    $text,
    $media,
    $pinned,
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
  $message = $headers['message'] ?? null;
  $group = $headers['group'] ?? null;
  $contact = $headers['contact'] ?? null;
  
  // ==== Null check ===========================================================
  if (is_null($token) || is_null($message)) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[NullAttributes::CODE]);
    echo json_encode(
      Utilities::generateErrorMessage(NullAttributes::CODE)
    );
    return;
  }
  
  // ==== Elaboration ==========================================================
  $result = $service->deleteMessage(
    $token,
    $message,
    $group,
    $contact,
  );
  
  // ==== Error case ===========================================================
  if (isset($result['error'])) {
    http_response_code(ErrorCases::CODES_ASSOCIATIONS[$result['error']]);
    echo json_encode($result);
    return;
  }
  
  // ==== Success case =========================================================
  return;
}
