<?php
require '../../vendor/autoload.php';

use Wave\Services\Database\DatabaseService;

if ($_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
  echo '
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
<hr>
<address>Apache/2.4.41 (Ubuntu) Server at wave.com Port 80</address>
</body></html>
';
  return;
}

$headers = getallheaders();

// ==== Authentication =============================================================================

$admin = $headers['admin'] ?? null;

if (is_null($admin)) {
  http_response_code(400);
  return;
}

// ==== Unknown admin ==============================================================================

$admins = [
  // giacomo
  'GbM/7PB6u3hPOD8G5/0utuyCZvBID9oIw6w6RGx3Bdx0G.QsXgL8q',
];

if (!in_array($admin, $admins)) {
  http_response_code(401);
  return;
}

// ==== Directives =================================================================================
// =================================================================================================

$directive = $headers['directive'] ?? null;

if (is_null($directive)) {
  http_response_code(400);
  return;
}

$service = DatabaseService::getInstance();

// ==== Purge database =============================================================================

if ($directive == '4PmB3be8Dd1azYT9IWTmeufGIMRVWVGNlmVBWOruj2tSLV6QoTpFe') {
  $service->purgeDatabase();
  return;
}

http_response_code(404);