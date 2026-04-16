<?php
$apiKey = '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';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://api.anthropic.com/v1/messages');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'x-api-key: ' . $apiKey,
    'anthropic-version: 2023-06-01',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'claude-haiku-4-5-20251001',
    'max_tokens' => 100,
    'messages' => [
        ['role' => 'user', 'content' => 'Dis bonjour']
    ]
]));

$response = curl_exec($ch);
curl_close($ch);
echo $response;