<?php

require_once __DIR__ . '/../src/PushStream.php';

use PushStream\PushStream;

function assertSameStrict($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL . 'Expected: ' . var_export($expected, true) . PHP_EOL . 'Actual: ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

function assertTrueStrict(bool $value, string $message): void
{
    if ($value !== true) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

$client = new PushStream('app-id', 'public-key', 'secret-key', [
    'apiUrl' => 'https://api.pushstream.online',
]);

$query = $client->buildSignedQuery('POST', '/api/apps/app-id/events', '{"hello":"world"}');
assertSameStrict('public-key', $query['auth_key'], 'auth_key should use the public app key');
assertSameStrict('v1', $query['auth_version'], 'auth_version should be v1');
assertSameStrict(md5('{"hello":"world"}'), $query['body_md5'], 'body_md5 should match raw body');

$canonical = [
    'auth_key' => $query['auth_key'],
    'auth_timestamp' => $query['auth_timestamp'],
    'auth_version' => $query['auth_version'],
    'body_md5' => $query['body_md5'],
];
ksort($canonical);
$expectedSignature = hash_hmac(
    'sha256',
    "POST\n/api/apps/app-id/events\n" . http_build_query($canonical, '', '&', PHP_QUERY_RFC3986),
    'secret-key'
);
assertSameStrict($expectedSignature, $query['auth_signature'], 'auth_signature should match backend contract');

$auth = $client->authorizeChannel('123.456', 'presence-org-1-chat', [
    'user_id' => '1001',
    'user_info' => ['name' => 'Kamal'],
]);
assertTrueStrict(str_starts_with($auth['auth'], 'public-key:'), 'channel auth should be prefixed with app key');
assertSameStrict(
    '{"user_id":"1001","user_info":{"name":"Kamal"}}',
    $auth['channel_data'],
    'presence channel_data should be encoded JSON'
);

assertTrueStrict($client->verifyWebhook(hash_hmac('sha256', 'payload', 'secret-key'), 'payload'), 'webhook verification should succeed for valid signature');

$published = new PushStream('app-id', 'public-key', 'secret-key', [
    'apiUrl' => 'https://api.pushstream.online',
    'httpClient' => function (string $method, string $url, array $options): array {
        assertSameStrict('POST', $method, 'publish should use POST');
        assertTrueStrict(str_contains($url, 'auth_signature='), 'publish URL should contain auth_signature');
        return [
            'status' => 200,
            'body' => '{"ok":true}',
        ];
    },
]);
assertSameStrict(['ok' => true], $published->publish('public-orders', 'order.created', ['id' => 1]), 'publish should decode JSON success response');

$failing = new PushStream('app-id', 'public-key', 'secret-key', [
    'apiUrl' => 'https://api.pushstream.online',
    'httpClient' => fn () => ['status' => 422, 'body' => '{"error":"bad request"}'],
]);

try {
    $failing->publish('public-orders', 'order.created', ['id' => 1]);
    fwrite(STDERR, 'publish should throw for non-2xx responses' . PHP_EOL);
    exit(1);
} catch (\RuntimeException $exception) {
    assertTrueStrict(str_contains($exception->getMessage(), 'HTTP 422'), 'publish failure should report HTTP status');
}

echo "PushStream PHP SDK tests passed" . PHP_EOL;
