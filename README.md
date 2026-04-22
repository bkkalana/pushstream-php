# PushStream PHP SDK

Server-side PHP SDK for PushStream event publishing, batch publishing, channel auth payload generation, and webhook signature verification.

## Installation

```bash
composer require pushstream/pushstream-php
```

## Configuration

Pass `apiUrl` explicitly or set `PUSHSTREAM_API_URL`.

```php
use PushStream\PushStream;

$client = new PushStream(
    'APP_ID',
    'APP_KEY',
    'APP_SECRET',
    ['apiUrl' => 'https://api.pushstream.online']
);
```

## Publish

```php
$response = $client->publish('public-orders', 'order.created', ['id' => 1]);
```

The SDK signs requests with the current PushStream query contract:

- `auth_key`
- `auth_timestamp`
- `auth_version`
- `body_md5`
- `auth_signature`

## Batch Publish

```php
$client->publishBatch([
    ['name' => 'order.created', 'channel' => 'public-orders', 'data' => ['id' => 1]],
    ['name' => 'order.updated', 'channel' => 'public-orders', 'data' => ['id' => 1, 'status' => 'paid']],
]);
```

## Channel Auth

```php
$auth = $client->authorizeChannel(
    '123.456',
    'presence-org-1-chat',
    ['user_id' => '1001', 'user_info' => ['name' => 'Kamal']]
);
```

Returned payload shape:

- `auth` => `{app_key}:{signature}`
- `channel_data` => JSON string for presence channels

## Webhook Verification

```php
$valid = $client->verifyWebhook($signature, $rawBody);
```

## Security Notes

- Keep `APP_SECRET` server-side only.
- This SDK is for trusted server environments, not browsers or mobile clients.

## Testing

```bash
composer test
```
