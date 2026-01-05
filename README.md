# PushStream PHP SDK

Official PHP SDK for PushStream real-time messaging platform.

## Installation

```bash
composer require pushstream/pushstream-php
```

## Usage

### Basic Usage

```php
<?php

require 'vendor/autoload.php';

use PushStream\PushStream;

$pushstream = new PushStream(
    'your-app-id',
    'your-app-key',
    'your-app-secret'
);

// Publish event
$pushstream->publish('my-channel', 'order.created', [
    'order_id' => 123,
    'amount' => 99.99
]);
```

### Laravel Integration

#### 1. Install Package

```bash
composer require pushstream/pushstream-php
```

> **Note:** The service provider is auto-discovered in Laravel 11+

#### 2. Publish Config

```bash
php artisan vendor:publish --tag=pushstream-config
```

#### 3. Configure `.env`

```env
PUSHSTREAM_APP_ID=your-app-id
PUSHSTREAM_APP_KEY=your-app-key
PUSHSTREAM_APP_SECRET=your-app-secret
PUSHSTREAM_API_URL=https://api.pushstream.ceylonitsolutions.online
```

#### 4. Use Facade

> **Note:** Laravel 11+ auto-discovers the service provider. For Laravel < 11, manually register in `config/app.php`

```php
<?php

use PushStream\Laravel\PushStreamFacade as PushStream;

// Publish event
PushStream::publish('orders', 'order.created', [
    'order_id' => 123,
    'amount' => 99.99
]);

// Publish batch
PushStream::publishBatch([
    ['channel' => 'orders', 'name' => 'order.created', 'data' => ['id' => 1]],
    ['channel' => 'users', 'name' => 'user.updated', 'data' => ['id' => 2]],
]);
```

## API Reference

### Constructor

```php
new PushStream($appId, $appKey, $appSecret, $options = [])
```

**Options:**
- `apiUrl` - API endpoint (default: `https://api.pushstream.ceylonitsolutions.online`)

### Methods

#### publish()

Publish an event to a channel.

```php
$pushstream->publish($channel, $event, $data, $socketId = null)
```

**Parameters:**
- `$channel` (string) - Channel name
- `$event` (string) - Event name
- `$data` (array) - Event data
- `$socketId` (string|null) - Exclude this socket from receiving the event

**Returns:** `array` - API response

#### publishBatch()

Publish multiple events in a single request.

```php
$pushstream->publishBatch($events)
```

**Parameters:**
- `$events` (array) - Array of events

**Example:**
```php
$pushstream->publishBatch([
    ['channel' => 'orders', 'name' => 'order.created', 'data' => ['id' => 1]],
    ['channel' => 'users', 'name' => 'user.updated', 'data' => ['id' => 2]],
]);
```

#### authorizeChannel()

Authenticate private/presence channel.

```php
$pushstream->authorizeChannel($socketId, $channel, $userData = null)
```

**Parameters:**
- `$socketId` (string) - Socket ID from client
- `$channel` (string) - Channel name
- `$userData` (array|null) - User data for presence channels

**Returns:** `array` - Auth response

**Example:**
```php
// Private channel
$auth = $pushstream->authorizeChannel($socketId, 'private-user-123');

// Presence channel
$auth = $pushstream->authorizeChannel($socketId, 'presence-chat', [
    'user_id' => 123,
    'user_info' => ['name' => 'John Doe']
]);
```

#### verifyWebhook()

Verify webhook signature.

```php
$pushstream->verifyWebhook($signature, $body)
```

**Parameters:**
- `$signature` (string) - Signature from webhook header
- `$body` (string) - Raw request body

**Returns:** `bool` - True if valid

**Example:**
```php
$signature = $_SERVER['HTTP_X_PUSHSTREAM_SIGNATURE'];
$body = file_get_contents('php://input');

if ($pushstream->verifyWebhook($signature, $body)) {
    // Process webhook
}
```

## Laravel Examples

### Controller

```php
<?php

namespace App\Http\Controllers;

use PushStream\Laravel\PushStreamFacade as PushStream;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        $order = Order::create($request->all());
        
        // Publish event
        PushStream::publish('orders', 'order.created', [
            'order_id' => $order->id,
            'amount' => $order->amount,
            'created_at' => $order->created_at
        ]);
        
        return response()->json($order);
    }
}
```

### Channel Authorization

```php
<?php

// routes/web.php
use PushStream\Laravel\PushStreamFacade as PushStream;

Route::post('/pusher/auth', function (Request $request) {
    $socketId = $request->input('socket_id');
    $channel = $request->input('channel_name');
    
    // Authorize private channel
    if (str_starts_with($channel, 'private-')) {
        return PushStream::authorizeChannel($socketId, $channel);
    }
    
    // Authorize presence channel
    if (str_starts_with($channel, 'presence-')) {
        return PushStream::authorizeChannel($socketId, $channel, [
            'user_id' => auth()->id(),
            'user_info' => [
                'name' => auth()->user()->name
            ]
        ]);
    }
    
    return response()->json(['error' => 'Unauthorized'], 403);
});
```

### Event Broadcasting

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OrderCreated implements ShouldBroadcast
{
    public $order;
    
    public function __construct($order)
    {
        $this->order = $order;
    }
    
    public function broadcastOn()
    {
        return new Channel('orders');
    }
    
    public function broadcastAs()
    {
        return 'order.created';
    }
}
```

## Requirements

- PHP >= 7.4
- ext-curl
- ext-json

## License

MIT
