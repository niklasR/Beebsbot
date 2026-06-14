# Beebsbot

> A hack project to give Facebook Messenger users "breaking news" notifications when a watch word is mentioned on live TV.

*"The Beeb"* is British slang for the BBC. Built at **Hack-A-Bot** — a hackathon hosted by Facebook at their London office (10 Brock Street, NW1) on 12 November 2016.

---

## What it does

1. **You tell Beebsbot what to listen for** — message the bot with keywords or phrases like `"Donald Trump"` or `"train crash"`.
2. **Beebsbot watches live TV** — it monitors the BBC News 24 DASH stream continuously.
3. **You get an instant alert** — the moment that phrase is spoken on air, you receive a Messenger notification with a screenshot of the broadcast and a one-tap link to watch it live on BBC iPlayer.

```
You:       "hello"
Beebsbot:  "I monitor live television for you. Give me a phrase to listen for."
You:       "interest rates"
Beebsbot:  "Got it. Send another phrase, or click Finished."
You:       [Finished]
Beebsbot:  "I'll sit here and watch TV (it's a hard life)."

… later …

Beebsbot:  "Hello! I've just heard someone mention interest rates on TV."
           [BBC News card with screenshot + Watch Live button]
```

---

## Architecture

```
Facebook Messenger
       │
       ▼
  index.php              — Messenger webhook (verifies hub challenge, routes events)
  Beebsbot.php           — Conversation logic: onboarding flow, keyword state machine
  database.php           — MySQL: stores users, keywords, notification queue
       │
       ▼
  alertMessenger.php     — Cron-triggered: polls notification queue, fires Messenger messages
       │
  screenshot-generator/
  └── index.js           — Node.js + ffmpeg: fetches DASH segments, extracts a still frame
                           as the notification image
```

### Stack

| Layer | Tech |
|---|---|
| Webhook / bot logic | PHP |
| Database | MySQL (PDO) |
| Screenshot extraction | Node.js, `fluent-ffmpeg`, DASH streaming |
| Messaging API | Facebook Messenger Platform (Graph API v2.6) |
| Live stream source | BBC News DASH stream |

---

## How the screenshot generator works

BBC News streams live TV over MPEG-DASH. When a keyword match is detected upstream, the segment number (derived from the timestamp) is used to fetch:

1. The **DASH init segment** (`.dash`) — codec and container metadata
2. The **matching media segment** (`.m4s`) — the video chunk containing that moment

These are concatenated in memory and piped through `ffmpeg` to extract a single frame as a PNG, which gets attached to the Messenger notification card.

---

## Setup

### Prerequisites

- PHP server with cURL and PDO/MySQL
- MySQL database
- Node.js (for the screenshot generator)
- A Facebook App with Messenger enabled and a Page Access Token
- An external process that feeds keyword matches + segment IDs into the `notifications` table

### Configuration

```php
// settings.php
$settings = array(
    "access_token" => "YOUR_PAGE_ACCESS_TOKEN",
    "verify_token" => "YOUR_WEBHOOK_VERIFY_TOKEN"
);
```

```php
// database.php — fill in your MySQL credentials
private $servername = "localhost";
private $username   = "your_db_user";
private $password   = "your_db_password";
private $database   = "your_db_name";
```

### Database schema (required tables)

```sql
CREATE TABLE users (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  BIGINT NOT NULL,
    keyword  TEXT,
    channel  TEXT
);

CREATE TABLE state (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  BIGINT NOT NULL,
    state    INT DEFAULT 0
);

CREATE TABLE notifications (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id  BIGINT NOT NULL,
    keyword  TEXT,
    context  TEXT,
    image    TEXT,
    sent     TINYINT DEFAULT 0
);
```

### Messenger webhook

Point your Facebook App's webhook at `https://yourdomain.com/index.php` with the verify token from `settings.php`.

### Screenshot generator

```bash
cd screenshot-generator
npm install
node index.js
# Listens on :3000
# GET /timestamp/:unix_timestamp → extracts frame, returns segment number
```

### Alert sender

Run `alertMessenger.php` on a cron (e.g. every 30 seconds) to flush the notification queue:

```
*/1 * * * * php /path/to/alertMessenger.php
```

---

## Context

Built at **Hack-A-Bot**, a hackathon run by Facebook at their London office (10 Brock Street, King's Cross, NW1) on 12 November 2016. The Messenger Platform had launched earlier that year, and the event challenged developers to build bots on it. Live TV monitoring felt like an obvious use case — and `ffmpeg` + DASH made grabbing a frame surprisingly tractable in a single day.

The name? *The Beeb* is what the British call the BBC. So: Beebsbot.
