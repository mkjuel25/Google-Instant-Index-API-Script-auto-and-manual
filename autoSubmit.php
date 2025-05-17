<?php
header('Content-Type: application/json');

// Path to JSON files
$jsonFilePath = 'private/new.json';
$historyFilePath = 'private/history.json';
$serviceAccountPath = 'private/service-account.json';

// Load URLs from `new.json`
if (!file_exists($jsonFilePath)) {
    echo json_encode(['success' => false, 'message' => 'No URLs to process.']);
    exit;
}

$urls = json_decode(file_get_contents($jsonFilePath), true);
if (empty($urls)) {
    echo json_encode(['success' => false, 'message' => 'No URLs to process.']);
    exit;
}

// Load history from `history.json`
$history = [];
if (file_exists($historyFilePath)) {
    $history = json_decode(file_get_contents($historyFilePath), true);
    if (!is_array($history)) {
        $history = [];
    }
}

// Filter out URLs already in history
$urlsToSubmit = array_diff($urls, $history);

if (empty($urlsToSubmit)) {
    echo json_encode(['success' => true, 'message' => 'No new URLs to submit.']);
    exit;
}

// Load service account
if (!file_exists($serviceAccountPath)) {
    echo json_encode(['success' => false, 'message' => 'Service account file not found.']);
    exit;
}

$serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
$privateKey = $serviceAccount['private_key'];
$clientEmail = $serviceAccount['client_email'];
$tokenUrl = $serviceAccount['token_uri'];

// Create JWT for authentication
$jwt = createJwt($privateKey, $clientEmail, $tokenUrl);

// Get Access Token
$postData = http_build_query([
    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
    'assertion' => $jwt
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (!$response) {
    echo json_encode(['success' => false, 'message' => 'cURL error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$tokenResponse = json_decode($response, true);

if (!isset($tokenResponse['access_token'])) {
    echo json_encode(['success' => false, 'message' => 'Failed to retrieve access token.', 'error' => $tokenResponse]);
    exit;
}

$accessToken = $tokenResponse['access_token'];

// Submit URLs to Indexing API
$results = [];
foreach ($urlsToSubmit as $url) {
    $url = trim($url);
    $apiUrl = 'https://indexing.googleapis.com/v3/urlNotifications:publish';
    $postData = json_encode([
        'url' => $url,
        'type' => 'URL_UPDATED'
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $apiResponse = json_decode($response, true);

    // Log the result
    $results[] = [
        'url' => $url,
        'httpCode' => $httpCode,
        'response' => $apiResponse
    ];

    // If successful, add URL to history
    if ($httpCode === 200) {
        $history[] = $url;
    }
}

// Limit history to the latest 60 URLs
$history = array_slice(array_unique($history), -60);

// Save updated history
file_put_contents($historyFilePath, json_encode($history, JSON_PRETTY_PRINT));

// Save remaining URLs to `new.json`
$remainingUrls = array_diff($urls, array_column($results, 'url'));
file_put_contents($jsonFilePath, json_encode($remainingUrls, JSON_PRETTY_PRINT));

// Return the results
echo json_encode(['success' => true, 'results' => $results]);

// Helper Functions
function createJwt($privateKey, $clientEmail, $tokenUrl)
{
    $header = base64UrlEncode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = base64UrlEncode(json_encode([
        'iss' => $clientEmail,
        'scope' => 'https://www.googleapis.com/auth/indexing',
        'aud' => $tokenUrl,
        'exp' => time() + 3600,
        'iat' => time()
    ]));
    $signature = base64UrlEncode(signData($header . '.' . $payload, $privateKey));
    return $header . '.' . $payload . '.' . $signature;
}

function signData($data, $privateKey)
{
    openssl_sign($data, $signature, $privateKey, 'sha256');
    return $signature;
}

function base64UrlEncode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}
