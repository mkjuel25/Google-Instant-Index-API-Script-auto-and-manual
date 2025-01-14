<?php
header('Content-Type: application/json');

// RSS Feed URL
$rssUrl = 'https://yoursite/rss.xml';

// JSON ফাইলের পাথ (প্রাইভেট ডিরেক্টরি)
$jsonFilePath = 'private/new.json';

// RSS ফিড থেকে ডাটা সংগ্রহ
$rssContent = file_get_contents($rssUrl);

if (!$rssContent) {
    echo json_encode(['success' => false, 'message' => 'Failed to fetch RSS feed.']);
    exit;
}

$xml = simplexml_load_string($rssContent);
$items = $xml->channel->item;

// নতুন URLs সংগ্রহ
$newUrls = [];
foreach ($items as $item) {
    $guid = (string) $item->guid;
    $isPermaLink = (string) $item->guid['isPermaLink'];

    if ($isPermaLink === 'true') {
        $newUrls[] = $guid;
    }
}

// সর্বশেষ ২০টি URL সংরক্ষণ
if ($newUrls) {
    // সর্বশেষ ২০টি URL রাখার জন্য
    $newUrls = array_slice(array_unique($newUrls), -20);

    // ফাইল আপডেট করা
    file_put_contents($jsonFilePath, json_encode($newUrls, JSON_PRETTY_PRINT));

    echo json_encode(['success' => true, 'message' => 'New URLs collected and limited to 20.', 'urls' => $newUrls]);
} else {
    echo json_encode(['success' => true, 'message' => 'No new URLs found.']);
}
