<?php

namespace PushStream;

class PushStream
{
    private $appId;
    private $appKey;
    private $appSecret;
    private $apiUrl;

    public function __construct($appId, $appKey, $appSecret, $options = [])
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->apiUrl = $options['apiUrl'] ?? 'http://localhost:8000';
    }

    /**
     * Publish an event to a channel
     */
    public function publish($channel, $event, $data, $socketId = null)
    {
        $timestamp = time();
        
        // Ensure data is a string
        $dataString = is_string($data) ? $data : json_encode($data);
        
        $body = json_encode([
            'name' => $event,
            'channel' => $channel,
            'data' => $dataString,
            'socket_id' => $socketId
        ]);

        $path = "/api/apps/{$this->appId}/events";
        $queryString = "auth_timestamp={$timestamp}";
        $stringToSign = "POST\n{$path}\n{$queryString}\n{$body}";
        
        $signature = hash_hmac('sha256', $stringToSign, $this->appSecret);
        $authHeader = "{$this->appId}:{$signature}";

        $ch = curl_init("{$this->apiUrl}{$path}?{$queryString}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $authHeader,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Failed to publish event: {$response}");
        }

        return json_decode($response, true);
    }

    /**
     * Publish multiple events in batch
     */
    public function publishBatch($events)
    {
        $timestamp = time();
        $body = json_encode(['batch' => $events]);

        $path = "/api/apps/{$this->appId}/batch_events";
        $queryString = "auth_timestamp={$timestamp}";
        $stringToSign = "POST\n{$path}\n{$queryString}\n{$body}";
        
        $signature = hash_hmac('sha256', $stringToSign, $this->appSecret);
        $authHeader = "{$this->appId}:{$signature}";

        $ch = curl_init("{$this->apiUrl}{$path}?{$queryString}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: ' . $authHeader,
                'Content-Type: application/json'
            ]
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception("Failed to publish batch: {$response}");
        }

        return json_decode($response, true);
    }

    /**
     * Authenticate private/presence channel
     */
    public function authorizeChannel($socketId, $channel, $userData = null)
    {
        $stringToSign = "{$socketId}:{$channel}";
        
        if ($userData && strpos($channel, 'presence-') === 0) {
            $stringToSign .= ':' . json_encode($userData);
        }

        $signature = hash_hmac('sha256', $stringToSign, $this->appSecret);
        $auth = "{$this->appKey}:{$signature}";

        $response = ['auth' => $auth];
        
        if ($userData) {
            $response['channel_data'] = json_encode($userData);
        }

        return $response;
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook($signature, $body)
    {
        $expectedSignature = hash_hmac('sha256', $body, $this->appSecret);
        return hash_equals($expectedSignature, $signature);
    }
}
