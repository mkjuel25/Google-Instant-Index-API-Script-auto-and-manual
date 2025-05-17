<?php
header('Content-Type: application/json');

// RSS Feed URL
$rssUrl = 'https://yoursite/rss.xml';

// Path to the JSON file (private directory)
$jsonFilePath = 'private/new.json';

// Fetch data from the RSS feed
$rssContent = file_get_contents($rssUrl);

if (!$rssContent) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch RSS feed.']);
    exit;
}

$xml = simplexml_load_string($rssContent);
$items = $xml->channel->item;

// Collect new URLs
$newUrls = [];
foreach ($items as $item) {
    $guid = (string) $item->guid;
    $isPermaLink = (string) $item->guid['isPermaLink'];

    if ($isPermaLink === 'true') {
        $newUrls[] = $guid;
    }
}

// Store the latest 20 URLs
if ($newUrls) {
    // Keep only the latest 20 unique URLs
    $newUrls = array_slice(array_unique($newUrls), -20);

    // Update the file
    file_put_contents($jsonFilePath, json_encode($newUrls, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'message' => 'New URLs collected and limited to 20.', 'urls' => $newUrls]);
} else {
    echo json_encode(['success' => true, 'message' => 'No new URLs found.']);
}
