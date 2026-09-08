<?php
// OpenAI Configuration
define('OPENAI_API_KEY', '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');
define('OPENAI_MODEL', 'gpt-4o-mini');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

function openai_chat(array $messages, float $temperature = 0.7, int $max_tokens = 1000): string {
    $payload = json_encode([
        'model'       => OPENAI_MODEL,
        'messages'    => $messages,
        'temperature' => $temperature,
        'max_tokens'  => $max_tokens,
    ]);

    $ch = curl_init(OPENAI_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        $errCode = $data['error']['code'] ?? '';
        if ($errCode === 'insufficient_quota') return 'The AI service is temporarily unavailable due to billing limits. Please contact the LYDO office.';
        return 'AI service error: ' . ($data['error']['message'] ?? 'Unknown error');
    }
    return $data['choices'][0]['message']['content'] ?? 'Sorry, I could not process that request.';
}
