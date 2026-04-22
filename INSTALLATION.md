# PushStream PHP SDK Installation

## Install

```bash
composer require pushstream/pushstream-php
```

## Laravel Config Publish

```bash
php artisan vendor:publish --tag=pushstream-config
```

This writes `config/pushstream.php`.

## Environment

```env
PUSHSTREAM_APP_ID=your-app-id
PUSHSTREAM_APP_KEY=your-app-key
PUSHSTREAM_APP_SECRET=your-app-secret
PUSHSTREAM_API_URL=https://api.pushstream.online
```

## Usage

```php
use PushStream\Laravel\PushStreamFacade as PushStream;

PushStream::publish('public-orders', 'order.created', [
    'order_id' => 123,
    'amount' => 99.99,
]);
```

## Notes

- Laravel auto-discovery is enabled through `composer.json`.
- This package is for trusted server environments.
- Do not expose `PUSHSTREAM_APP_SECRET` to browsers or mobile apps.

See [README.md](README.md) for the current signing contract and API usage.
