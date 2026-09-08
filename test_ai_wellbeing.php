<?php
// Test the AI-powered wellbeing assistant
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🤖 AI-Powered Wellbeing Assistant Test</h1>";

require_once __DIR__ . '/lydo-system/shared/config.php';

echo "<h2>Configuration Check:</h2>";
$groqKey = getenv('GROQ_API_KEY');
if ($groqKey) {
    echo "<p style='color: green;'>✅ Groq API Key configured: " . substr($groqKey, 0, 10) . "...</p>";
} else {
    echo "<p style='color: red;'>❌ Groq API Key not found</p>";
}

echo "<h2>Test AI Function:</h2>";

// Mock user data
$user = ['first_name' => 'Test', 'full_name' => 'Test User'];

// Test the AI function directly
function testAI() {
    $apiKey = getenv('GROQ_API_KEY');
    if (!$apiKey) {
        return "No API key configured";
    }

    $data = [
        'model' => 'openai/gpt-oss-120b',
        'messages' => [
            [
                'role' => 'system', 
                'content' => 'You are a helpful AI assistant. Respond with "Hello! AI is working perfectly!" to confirm the connection.'
            ],
            [
                'role' => 'user', 
                'content' => 'Test connection'
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 100
    ];

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        $decoded = json_decode($response, true);
        if (isset($decoded['choices'][0]['message']['content'])) {
            return $decoded['choices'][0]['message']['content'];
        }
    }

    return "API call failed. HTTP Code: $httpCode, Response: $response";
}

$result = testAI();
echo "<div style='padding: 15px; background: #f0f8ff; border: 1px solid #0066cc; border-radius: 5px;'>";
echo "<strong>AI Response:</strong><br>";
echo htmlspecialchars($result);
echo "</div>";

echo "<h2>Access the Assistant:</h2>";
echo "<p><a href='lydo-system/shared/youth/wellbeing.php' target='_blank' style='background: #0066cc; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🤖 Launch AI Wellbeing Assistant</a></p>";

echo "<h2>Features Available:</h2>";
echo "<ul>";
echo "<li>✅ Crisis detection and admin alerts</li>";
echo "<li>🤖 AI-powered responses for ANY topic</li>";
echo "<li>💬 Natural conversation flow</li>";
echo "<li>📚 Support for mental health, academics, technology, relationships, etc.</li>";
echo "<li>🔄 Fallback responses if AI is unavailable</li>";
echo "<li>📱 Responsive mobile-friendly interface</li>";
echo "<li>🗂️ Chat history persistence</li>";
echo "</ul>";
?>