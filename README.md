# PushStream PHP SDK (`sdks` Edition)

PHP SDK for PushStream event publishing, batch publishing, and channel auth signature generation.

## Source

- File: `sdks/pushstream-php/src/PushStream.php`
- Class: `PushStream\PushStream`

## Compatibility

This SDK matches current PushStream protocol:

- Event endpoint: `POST /api/apps/{app_id}/events`
- Batch endpoint: `POST /api/apps/{app_id}/batch_events`
- Publish auth header: `Authorization: {app_id}:{signature}`
- Batch `data` normalized as JSON string
- Channel auth result format: `{app_id}:{signature}`

## Installation

```bash
composer require pushstream/pushstream-php
```

## Quick Start

```php
<?php

require 'vendor/autoload.php';

use PushStream\PushStream;

$client = new PushStream('APP_ID', 'APP_KEY', 'APP_SECRET');

$response = $client->publish('orders', 'order.created', ['id' => 1]);
print_r($response);
```

## Constructor

```php
new PushStream($appId, $appKey, $appSecret, $options = [])
```

Options:

- `apiUrl` (default `https://api.pushstream.ceylonitsolutions.online`)

## API

### `publish($channel, $event, $data, $socketId = null)`

Publish a single event.

### `publishBatch($events)`

Publish multiple events in one request.

Example:

```php
$client->publishBatch([
  ['name' => 'order.created', 'channel' => 'orders', 'data' => ['id' => 1]],
  ['name' => 'order.updated', 'channel' => 'orders', 'data' => ['id' => 1, 'status' => 'paid']],
]);
```

### `authorizeChannel($socketId, $channel, $userData = null)`

Generate auth payload for private/presence channels.

Example:

```php
$auth = $client->authorizeChannel(
  '123.456',
  'presence-org-1-chat',
  ['user_id' => 1001, 'user_info' => ['name' => 'Kamal']]
);
```

### `verifyWebhook($signature, $body)`

Verify webhook signature using app secret.

## Security Guidance

- Keep `APP_SECRET` server-side only.
- Never expose secret in browser/mobile client code.

## Migration Recommendation

For actively maintained package and cleaner API, use canonical SDK:

- `sdk/pushstream-php/README.md`
