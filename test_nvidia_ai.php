<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$apiKey = env('NVIDIA_API_KEY');
$apiUrl = env('NVIDIA_API_URL', 'https://integrate.api.nvidia.com/v1');

echo "Testing NVIDIA AI API...\n";
echo "API URL: $apiUrl\n";
echo "API Key: " . substr($apiKey, 0, 10) . "...\n\n";

$response = \Illuminate\Support\Facades\Http::withHeaders([
    'Authorization' => 'Bearer ' . $apiKey,
    'Content-Type'  => 'application/json',
])->timeout(30)->post($apiUrl . '/chat/completions', [
    'model' => 'meta/llama-3.3-70b-instruct',
    'messages' => [
        ['role' => 'system', 'content' => 'Output   ONLY raw PHP code. No markdown. No explanations. No use statements. Use FQN like \\App\\Models\\Film::count()'],
        ['role' => 'user', 'content' => 'Tampilkan total film di database'],
    ],
    'temperature' => 0.2,
    'max_tokens'  => 512,
    'stream'      => false,
]);

echo "Status: " . $response->status() . "\n";
if ($response->successful()) {
    $data = $response->json();
    $code = $data['choices'][0]['message']['content'] ?? '';
    echo "Tokens: " . ($data['usage']['total_tokens'] ?? 0) . "\n";
    echo "Model: " . ($data['model'] ?? 'unknown') . "\n\n";
    echo "=== GENERATED CODE ===\n";
    echo $code . "\n";
} else {
    echo "Error: " . $response->body() . "\n";
}
