<?php
require_once __DIR__ . '/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Expect JSON body with { action: 'create'|'update'|'delete', tournament: { ... } }
$payload = json_decode(file_get_contents('php://input'), true);
if (!$payload || empty($payload['action']) || empty($payload['tournament'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

$action = $payload['action'];
$t = $payload['tournament'];

// load credentials if available
if (file_exists(__DIR__ . '/credentials.php')) {
    require_once __DIR__ . '/credentials.php';
}

$servicePath = __DIR__ . '/service-account.json';
if (!file_exists($servicePath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Service account JSON not found']);
    exit;
}

$service = json_decode(file_get_contents($servicePath), true);
if (!$service) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid service account JSON']);
    exit;
}

// helper: base64url
function b64u($data) { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }

// create JWT assertion for service account
$now = time();
$jwtHeader = ['alg' => 'RS256', 'typ' => 'JWT'];
$scope = 'https://www.googleapis.com/auth/calendar';
$claims = [
    'iss' => $service['client_email'],
    'scope' => $scope,
    'aud' => $service['token_uri'],
    'exp' => $now + 3600,
    'iat' => $now,
];

$unsigned = b64u(json_encode($jwtHeader)) . '.' . b64u(json_encode($claims));
$signed = '';
openssl_sign($unsigned, $signed, $service['private_key'], OPENSSL_ALGO_SHA256);
$assertion = $unsigned . '.' . b64u($signed);

// exchange for access token
function http_post($url, $body, $headers = []) {
    $opts = [
        'http' => [
            'method' => 'POST',
            'header' => implode("\r\n", array_merge(['Content-Type: application/x-www-form-urlencoded'], $headers)),
            'content' => $body,
            'ignore_errors' => true,
        ]
    ];
    return file_get_contents($url, false, stream_context_create($opts));
}

$tokenResp = http_post($service['token_uri'], http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $assertion,
]));
$tokenData = json_decode($tokenResp, true);
if (empty($tokenData['access_token'])) {
    file_put_contents(__DIR__ . '/sync_calendar.log', date('c') . ' token_error ' . $tokenResp . PHP_EOL, FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to obtain access token', 'detail' => $tokenData]);
    exit;
}

$accessToken = $tokenData['access_token'];

// determine calendar id
$calendarId = $GOOGLE_CALENDAR_ID ?? '';
if (empty($calendarId)) {
    http_response_code(500);
    echo json_encode(['error' => 'Calendar ID not configured in api/credentials.php']);
    exit;
}

// build event body from tournament
function tournament_to_event($t) {
    // Build description with time and fee
    $description = $t['description'] ?? '';
    
    if (!empty($t['time'])) {
        $description .= "\n\nStartzeit: " . $t['time'];
    }
    
    if (!empty($t['fee'])) {
        $description .= "\nPreis: " . $t['fee'] . " €";
    }
    
    if (!empty($t['registrationInfo'])) {
        $description .= "\n\nAnmeldung: " . $t['registrationInfo'];
    }
    
    $event = [
        'summary' => $t['title'] ?? 'Turnier',
        'location' => $t['location'] ?? '',
        'description' => trim($description),
    ];
    
    // Set as all-day event
    if (!empty($t['date'])) {
        $event['start'] = ['date' => $t['date']];
        $event['end'] = ['date' => $t['date']];
    }
    
    return $event;
}
