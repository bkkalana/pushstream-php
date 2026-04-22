<?php

namespace PushStream;

class PushStream
{
    private string $appId;
    private string $appKey;
    private string $appSecret;
    private string $apiUrl;
    private int $timeout;
    private $httpClient;

    public function __construct(string $appId, string $appKey, string $appSecret, array $options = [])
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $apiUrl = $options['apiUrl'] ?? getenv('PUSHSTREAM_API_URL') ?? null;
        $this->apiUrl = $this->normalizeApiUrl($apiUrl);
        $this->timeout = (int) ($options['timeout'] ?? 10);
        $this->httpClient = $options['httpClient'] ?? null;
    }

    public function publish(string $channel, string $event, $data, ?string $socketId = null): array
    {
        $body = json_encode([
            'name' => $event,
            'channel' => $channel,
            'data' => is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_SLASHES),
            'socket_id' => $socketId,
        ], JSON_UNESCAPED_SLASHES);

        return $this->request('POST', "/api/apps/{$this->appId}/events", $body);
    }

    public function publishBatch(array $events): array
    {
        $batch = array_map(function (array $event): array {
            return [
                'name' => $event['name'],
                'channel' => $event['channel'],
                'data' => is_string($event['data']) ? $event['data'] : json_encode($event['data'], JSON_UNESCAPED_SLASHES),
                'socket_id' => $event['socket_id'] ?? null,
            ];
        }, $events);

        $body = json_encode(['batch' => $batch], JSON_UNESCAPED_SLASHES);

        return $this->request('POST', "/api/apps/{$this->appId}/batch_events", $body);
    }

    public function authorizeChannel(string $socketId, string $channel, ?array $userData = null): array
    {
        $channelData = null;
        $stringToSign = "{$socketId}:{$channel}";

        if ($userData && strpos($channel, 'presence-') === 0) {
            $channelData = json_encode($userData, JSON_UNESCAPED_SLASHES);
            $stringToSign .= ':' . $channelData;
        }

        $signature = hash_hmac('sha256', $stringToSign, $this->appSecret);
        $response = [
            'auth' => "{$this->appKey}:{$signature}",
        ];

        if ($channelData !== null) {
            $response['channel_data'] = $channelData;
        }

        return $response;
    }

    public function verifyWebhook(string $signature, string $body): bool
    {
        $expectedSignature = hash_hmac('sha256', $body, $this->appSecret);
        return hash_equals($expectedSignature, $signature);
    }

    public function buildSignedQuery(string $method, string $path, string $body = ''): array
    {
        $query = [
            'auth_key' => $this->appKey,
            'auth_timestamp' => (string) time(),
            'auth_version' => 'v1',
        ];

        if ($body !== '') {
            $query['body_md5'] = md5($body);
        }

        ksort($query);
        $canonical = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $query['auth_signature'] = hash_hmac('sha256', strtoupper($method) . "\n{$path}\n{$canonical}", $this->appSecret);

        return $query;
    }

    private function request(string $method, string $path, string $body): array
    {
        $query = $this->buildSignedQuery($method, $path, $body);
        $url = $this->apiUrl . $path . '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);

        if (is_callable($this->httpClient)) {
            $response = call_user_func($this->httpClient, strtoupper($method), $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body' => $body,
                'timeout' => $this->timeout,
            ]);

            $httpCode = $response['status'] ?? 500;
            $rawBody = $response['body'] ?? '';
            if ($httpCode < 200 || $httpCode >= 300) {
                throw new \RuntimeException("PushStream request failed with HTTP {$httpCode}: {$rawBody}");
            }

            $decoded = json_decode($rawBody, true);
            if (!is_array($decoded)) {
                throw new \RuntimeException('PushStream response was not valid JSON');
            }

            return $decoded;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException("PushStream request failed: {$error}");
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException("PushStream request failed with HTTP {$httpCode}: {$response}");
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('PushStream response was not valid JSON');
        }

        return $decoded;
    }

    private function normalizeApiUrl(?string $apiUrl): string
    {
        if (!$apiUrl) {
            throw new \InvalidArgumentException('apiUrl is required. Pass it explicitly or set PUSHSTREAM_API_URL.');
        }

        $scheme = parse_url($apiUrl, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new \InvalidArgumentException('apiUrl must use http or https.');
        }

        return rtrim($apiUrl, '/');
    }
}
