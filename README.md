# Google-Instant-Index-API-Script-auto-and-manual
This project demonstrates how to use the Google Indexing API to automate the indexing of URLs, either via RSS feed or manual URL submission. It allows users to submit website URLs to Google's Instant Indexing API efficiently, improving content visibility on search engines. 


Here's the **README.md** file based on your detailed guide:

---

# Google Instant Index API Script

This project demonstrates how to automate the submission of URLs to Google Instant Index API. It supports both **automatic** (via RSS feeds) and **manual** (via user input) submission methods.

---
What this project includes, the first method is to automate the indexing of URLs using cron Jon and the Google Indexing API, and the second method is to manually submit URLs.

For those who use 3rd party scripts or any other blogging platform/PHP script, it is highlighted how to use auto index - you can try it in a subdomain or folder as a directory.

Note : Before running this script, create Google Index API for the website you will use the feed URL of and set site ownership in search console.

Rename the Json file created from the Google Index API to service-account.json .
---

## Features

1. Automatically fetches URLs from an RSS feed and submits them to Google's Indexing API.
2. Stores and manages submission history to prevent duplicate submissions.
3. Manual method allows batch URL submission through a user-friendly interface.
4. Supports cron jobs for periodic execution.

---

## Setup Instructions

### 1. Prerequisites

- **Google Index API Setup**:
  - Ensure your website is verified in **Google Search Console**.
  - Create a Google Index API project and download the service account JSON file.
  - Rename the JSON file to `service-account.json`.

### 2. File Structure

```plaintext
project-root/
|--- private/
|    |--- service-account.json
|    |--- new.json
|    |--- history.json
|
|--- collect.php
|--- autoSubmit.php
|--- index.html
|--- submit.php
```

### 3. Automatic Submission (Using RSS Feed)

#### Configure `collect.php`

1. Update the RSS feed URL:

```php
$rssUrl = 'https://yourwebsite.com/rss.xml';
```

2. Place this file in your project root.

Note: What you need to edit in collect.php ($rssUrl = 'https://yoursite/rss.xml'; ) is to add the URL of your website.

(If the site's feed url has isPermaLink=”false” then set it to false otherwise it won't work, normal is true [Find if it's false in rss and write false here, if ($isPermaLink === 'false') in collect.php)

Key points –

 Collect RSS feed from given feed (https://website.com/feed) URL. ✓✓
The content of the RSS feed is parsed using simplexml_load_string(). ✓✓
Checks whether the guid element of the RSS item has isPermaLink=”true”. ✓✓
Collect all valid URLs and save them in a JSON file, containing only the 20 most recent URLs. ✓✓

#### How It Works

- Collects URLs from the RSS feed.
- Saves the latest 20 unique URLs in `new.json`.

#### Cron Job Setup

Run `collect.php` periodically using a cron job or a service like **cron-job.org**:

```plaintext
0 * * * * /usr/bin/php /path/to/collect.php
```

---

### 4. Auto Index Submission

#### Configure `autoSubmit.php`

1. Place `autoSubmit.php` in your project root.

#### How It Works

- Reads URLs from `new.json`.
- Filters out previously submitted URLs (stored in `history.json`).
- Submits new URLs to the Google Indexing API.
- Updates `history.json` with successful submissions (limits to 60 URLs).

#### Cron Job Setup

Run `autoSubmit.php` periodically using a cron job (or, use cron-job.org:

```plaintext
0 * * * * /usr/bin/php /path/to/autoSubmit.php
```

---

### 5. Manual Submission

#### Setup `index.html` and `submit.php`

1. Place both files in your project root.
2. Use the browser to open `index.html`.
3. Enter URLs (one per line) and submit.

---

## Notes

1. Ensure the `private/service-account.json` file is correctly configured.
2. Use tools like **cron-job.org** for periodic execution.
3. Verify the feed’s `isPermaLink` attribute in `collect.php` (default: `true`).

---

## Key Points

- Collects URLs from RSS feeds (`collect.php`).
- Submits new URLs to Google Indexing API (`autoSubmit.php`).
- Manual submissions through a user interface (`index.html` + `submit.php`).

---
### Use Cases:  
- Automate content indexing for blogs or news sites using RSS feeds.  
- Improve website SEO by instantly notifying Google about new or updated URLs.  
- Manage URL submissions efficiently through a secure and scalable system.  

This project is ideal for developers looking to streamline their Google indexing workflow using a combination of automated and manual methods.  

For more projects and updates, visit and also you can download zip here [Owntweet](https://owntweet.com/thread/233921).
