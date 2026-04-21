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
    $tz = 'Europe/Berlin';
    $startDateTime = null;
    $endDateTime = null;
    if (!empty($t['date']) && !empty($t['time'])) {
        $start = $t['date'] . ' ' . $t['time'];
        $dt = new DateTime($start, new DateTimeZone($tz));
        $startDateTime = $dt->format(DateTime::RFC3339);
        // default duration 4 hours
        $dt->modify('+4 hours');
        $endDateTime = $dt->format(DateTime::RFC3339);
    }
    $event = [
        'summary' => $t['title'] ?? 'Turnier',
        'location' => $t['location'] ?? '',
        'description' => ($t['description'] ?? '') . "\n\nAnmeldung: " . ($t['registrationInfo'] ?? ''),
    ];
    if ($startDateTime) {
        $event['start'] = ['dateTime' => $startDateTime, 'timeZone' => $tz];
        $event['end'] = ['dateTime' => $endDateTime, 'timeZone' => $tz];
    }
    return $event;
}

// perform API call helpers
function http_request($method, $url, $body = null, $headers = []) {
    $h = $headers;
    if ($body !== null && !in_array('Content-Type: application/json', $h)) {
        $h[] = 'Content-Type: application/json';
    }
    $opts = [
        'http' => [
            'method' => $method,
            'header' => implode("\r\n", $h),
            'content' => $body !== null ? $body : null,
            'ignore_errors' => true,
        ]
    ];
    return file_get_contents($url, false, stream_context_create($opts));
}

$eventBody = tournament_to_event($t);

try {
    if ($action === 'create') {
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events';
        $resp = http_request('POST', $url, json_encode($eventBody), ["Authorization: Bearer $accessToken"]);
        $data = json_decode($resp, true);
        echo json_encode(['ok' => true, 'event' => $data]);
        exit;
    } elseif ($action === 'update') {
        if (empty($t['calendar_event_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing calendar_event_id for update']);
            exit;
        }
        $eventId = $t['calendar_event_id'];
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId);
        $resp = http_request('PUT', $url, json_encode($eventBody), ["Authorization: Bearer $accessToken"]);
        $data = json_decode($resp, true);
        echo json_encode(['ok' => true, 'event' => $data]);
        exit;
    } elseif ($action === 'delete') {
        if (empty($t['calendar_event_id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing calendar_event_id for delete']);
            exit;
        }
        $eventId = $t['calendar_event_id'];
        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId);
        $resp = http_request('DELETE', $url, null, ["Authorization: Bearer $accessToken"]);
        echo json_encode(['ok' => true, 'deleted' => true]);
        exit;
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Exception', 'message' => $e->getMessage()]);
    exit;
}

?>
