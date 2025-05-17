<?php
header('Content-Type: application/json');

// Path to the JSON file (private directory)
$serviceAccountPath = 'private/service-account.json';

// Read input data
$data = json_decode(file_get_contents('php://input'), true);
$urls = $data['urls'] ?? [];

if (empty($urls)) {
    echo json_encode(['success' => false, 'message' => 'At least one URL is required.']);
    exit;
}

// Load the service account file
if (!file_exists($serviceAccountPath)) {
    echo json_encode(['success' => false, 'message' => 'Service account file not found.']);
    exit;
}

$serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);
$privateKey = $serviceAccount['private_key'];
$clientEmail = $serviceAccount['client_email'];
$tokenUrl = $serviceAccount['token_uri'];

// Create JWT
$jwt = createJwt($privateKey, $clientEmail, $tokenUrl);

// Request token
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

// Process each URL
$results = [];
foreach ($urls as $url) {
    $url = trim($url); // Remove leading/trailing spaces
    if ($url) {
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
        curl_close($ch);

        $results[] = json_decode($response, true);
    }
}

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
