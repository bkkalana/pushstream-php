# PushStream PHP SDK - Installation Guide

## ✅ Fixed Issue

The `php artisan vendor:publish --tag=pushstream-config` command now works correctly.

### What Was Fixed

Added Laravel package auto-discovery configuration to `composer.json`:

```json
"extra": {
    "laravel": {
        "providers": [
            "PushStream\\Laravel\\PushStreamServiceProvider"
        ],
        "aliases": {
            "PushStream": "PushStream\\Laravel\\PushStreamFacade"
        }
    }
}
```

---

## 📦 Installation Steps

### 1. Install Package

```bash
composer require pushstream/pushstream-php
```

### 2. Publish Configuration

```bash
php artisan vendor:publish --tag=pushstream-config
```

This creates `config/pushstream.php`

### 3. Configure Environment

Add to `.env`:

```env
PUSHSTREAM_APP_ID=your-app-id
PUSHSTREAM_APP_KEY=your-app-key
PUSHSTREAM_APP_SECRET=your-app-secret
PUSHSTREAM_API_URL=http://localhost:8000
```

### 4. Use in Your Code

```php
use PushStream\Laravel\PushStreamFacade as PushStream;

// Publish event
PushStream::publish('orders', 'order.created', [
    'order_id' => 123,
    'amount' => 99.99
]);
```

---

## 🔧 How It Works

### Auto-Discovery (Laravel 11+)

Laravel automatically discovers and registers:
- Service Provider: `PushStreamServiceProvider`
- Facade: `PushStream`

### Manual Registration (Laravel < 11)

Add to `config/app.php`:

```php
'providers' => [
    PushStream\Laravel\PushStreamServiceProvider::class,
],

'aliases' => [
    'PushStream' => PushStream\Laravel\PushStreamFacade::class,
],
```

---

## ✅ Verification

Test the installation:

```bash
# Check if config is published
ls config/pushstream.php

# Test in tinker
php artisan tinker
>>> config('pushstream.app_id')
```

---

## 📚 Next Steps

See [README.md](README.md) for full API documentation and examples.
