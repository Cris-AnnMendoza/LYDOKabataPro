<?php
session_start();
require_once __DIR__ . '/../config.php';

// Allow both youth users and organization presidents
$isYouth = !empty($_SESSION['user_id']);
$isPresident = !empty($_SESSION['org_president_id']);

if (!$isYouth && !$isPresident) {
    echo '<div style="padding:20px;text-align:center">Please <a href="../../login.php">login</a> to use the chatbot.</div>';
    exit;
}

$pdo = db();

// Get user info based on login type
if ($isPresident) {
    $president = $_SESSION['org_president'];
    $userName = $president['full_name'];
    $userType = 'president';
    $userId = $president['id'];
    $user = $president;
} else {
    $userId = (int)$_SESSION['user_id'];
    $uStmt = $pdo->prepare('SELECT * FROM youth_users WHERE id=? LIMIT 1');
    $uStmt->execute([$userId]);
    $user = $uStmt->fetch();
    $userName = $user['first_name'] . ' ' . $user['last_name'];
    $userType = 'youth';
}

// Ensure chat history table
$pdo->exec("CREATE TABLE IF NOT EXISTS wellbeing_chats (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    user_type  ENUM('youth','president') NOT NULL DEFAULT 'youth',
    role       ENUM('user','bot') NOT NULL,
    message    TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id, user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// AJAX: process message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_chat'])) {
    header('Content-Type: application/json');
    $msg = trim($_POST['message'] ?? '');
    if (!$msg || mb_strlen($msg) > 1000) {
        echo json_encode(['reply' => 'Please send a valid message (max 1000 characters).']);
        exit;
    }

    // Save user message
    $pdo->prepare('INSERT INTO wellbeing_chats (user_id,user_type,role,message) VALUES (?,?,?,?)')
        ->execute([$userId, $userType, 'user', $msg]);
    
    // Generate AI response
    $reply = generateWellbeingReply($msg, $user, $pdo, $userId, $userType);

    // Save bot reply
    $pdo->prepare('INSERT INTO wellbeing_chats (user_id,user_type,role,message) VALUES (?,?,?,?)')
        ->execute([$userId, $userType, 'bot', $reply]);

    echo json_encode(['reply' => $reply]);
    exit;
}

// Load recent chat history
$histStmt = $pdo->prepare('SELECT role, message, created_at FROM wellbeing_chats WHERE user_id=? AND user_type=? ORDER BY created_at ASC LIMIT 50');
$histStmt->execute([$userId, $userType]);
$history = $histStmt->fetchAll();

$firstName = $user['first_name'] ?? $user['full_name'] ?? 'Friend';

// AI Response Functions
function generateWellbeingReply(string $input, array $user, PDO $pdo, int $userId, string $userType = 'youth'): string {
    $text = mb_strtolower($input);
    $name = $user['first_name'] ?? $user['full_name'] ?? 'Friend';

    // Crisis detection
    $crisisWords = ['suicid','kill myself','end my life','want to die','no reason to live','self harm','hurt myself'];
    foreach ($crisisWords as $w) {
        if (str_contains($text, $w)) {
            return "💙 **{$name}, I'm really concerned about what you just shared.**\n\n" .
                   "Please know that **you are not alone**. Help is available right now:\n\n" .
                   "📞 **Hopeline PH:** 1553 (24/7, free)\n" .
                   "📞 **NCMH Crisis Line:** (02) 8989-8727\n" .
                   "📞 **Emergency:** 911\n\n" .
                   "Please reach out to someone you trust or call one of these numbers. Your life matters. 🙏";
        }
    }

    // Try AI
    $aiResponse = callGroqAPI($input, $name, $userType);
    if ($aiResponse) return $aiResponse;
    
    return getFallbackResponse($input, $name);
}

function callGroqAPI(string $input, string $name, string $userType): ?string {
    $apiKey = getenv('GROQ_API_KEY');
    if (!$apiKey || $apiKey === 'your_groq_api_key_here') {
        return null;
    }

    $systemPrompt = "You are LYDO's Well-being Assistant. Provide empathetic, practical support for Filipino youth. Address user as '{$name}'. Keep responses under 300 words. Use emojis appropriately. Help with any topic: mental health, academics, career, relationships, technology, etc.";
    
    $data = [
        'model' => 'openai/gpt-oss-120b',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $input]
        ],
        'temperature' => 0.7,
        'max_tokens' => 500
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
    return null;
}

function getFallbackResponse(string $input, string $name): string {
    $text = mb_strtolower($input);
    
    if (matchAny($text, ['hello','hi','hey','kumusta'])) {
        return "Hello, **{$name}**! 😊 I'm here to help with anything - mental health, school, career, or just to chat. What's on your mind?";
    }
    
    if (matchAny($text, ['thank','salamat'])) {
        return "You're welcome, **{$name}**! 💙 I'm always here when you need me.";
    }
    
    return "That's interesting, **{$name}**! 💙 Tell me more about that so I can help you better. I'm here to assist with any topic you'd like to discuss! 😊";
}

function matchAny(string $text, array $keywords): bool {
    foreach ($keywords as $kw) {
        if (str_contains($text, $kw)) return true;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Wellbeing Chat</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Inter', sans-serif;
    background: #f5f5f5;
    height: 100vh;
    overflow: hidden;
}

.chat-container {
    display: flex;
    flex-direction: column;
    height: 100vh;
    background: #fff;
}

.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    background: #f8f9fa;
}

.message {
    margin-bottom: 12px;
    display: flex;
    gap: 8px;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.message.user {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 0.8rem;
}

.message.user .message-avatar {
    background: #1565c0;
    color: #fff;
}

.message.bot .message-avatar {
    background: #e3f2fd;
    color: #1565c0;
}

.message-content {
    max-width: 75%;
    padding: 10px 14px;
    border-radius: 16px;
    line-height: 1.5;
    font-size: 0.85rem;
    word-wrap: break-word;
}

.message.user .message-content {
    background: #1565c0;
    color: #fff;
    border-bottom-right-radius: 4px;
}

.message.bot .message-content {
    background: #fff;
    color: #2d3748;
    border: 1px solid #e2e8f0;
    border-bottom-left-radius: 4px;
}

.message-time {
    font-size: 0.65rem;
    color: rgba(255,255,255,0.7);
    margin-top: 4px;
}

.message.bot .message-time {
    color: #94a3b8;
}

.typing-indicator {
    display: none;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    margin-bottom: 12px;
}

.typing-indicator.show {
    display: flex;
}

.typing-dots {
    display: flex;
    gap: 3px;
}

.typing-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: #94a3b8;
    animation: typing 1.4s infinite;
}

.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typing {
    0%, 60%, 100% { opacity: 0.3; transform: scale(0.8); }
    30% { opacity: 1; transform: scale(1); }
}

.chat-input-area {
    padding: 12px 16px;
    background: #fff;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    align-items: flex-end;
}

.chat-input-area textarea {
    flex: 1;
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 10px 16px;
    resize: none;
    font-family: inherit;
    font-size: 0.85rem;
    line-height: 1.4;
    max-height: 100px;
    outline: none;
}

.chat-input-area textarea:focus {
    border-color: #1565c0;
    box-shadow: 0 0 0 2px rgba(21,101,192,0.1);
}

.send-button {
    background: #1565c0;
    border: none;
    border-radius: 50%;
    width: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    cursor: pointer;
    transition: all 0.2s;
    flex-shrink: 0;
}

.send-button:hover {
    background: #0d47a1;
    transform: scale(1.05);
}

.send-button:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
    transform: none;
}

/* Scrollbar */
.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
</head>
<body>

<div class="chat-container">
    <div class="chat-messages" id="messagesContainer">
        <!-- Welcome Message -->
        <div class="message bot">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="message-content">
                Hey, how may I help you?
                <div class="message-time"><?= date('g:i A') ?></div>
            </div>
        </div>

        <!-- Load Chat History -->
        <?php foreach ($history as $msg): ?>
        <div class="message <?= $msg['role'] ?>">
            <div class="message-avatar">
                <i class="fas fa-<?= $msg['role'] === 'user' ? 'user' : 'robot' ?>"></i>
            </div>
            <div class="message-content">
                <?= nl2br(htmlspecialchars($msg['message'])) ?>
                <div class="message-time"><?= date('g:i A', strtotime($msg['created_at'])) ?></div>
            </div>
        </div>
        <?php endforeach ?>

        <!-- Typing Indicator -->
        <div class="typing-indicator" id="typingIndicator">
            <div class="message-avatar">
                <i class="fas fa-robot"></i>
            </div>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
        </div>
    </div>

    <div class="chat-input-area">
        <textarea 
            id="messageInput" 
            placeholder="Type your message..."
            rows="1"
            maxlength="1000"
        ></textarea>
        <button class="send-button" onclick="sendMessage()" id="sendBtn">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<script>
const messagesContainer = document.getElementById('messagesContainer');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const typingIndicator = document.getElementById('typingIndicator');

// Auto-resize textarea
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 100) + 'px';
});

// Send on Enter (Shift+Enter for new line)
messageInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

async function sendMessage() {
    const message = messageInput.value.trim();
    if (!message) return;
    
    addMessage('user', message);
    messageInput.value = '';
    messageInput.style.height = 'auto';
    
    showTyping(true);
    
    try {
        const formData = new FormData();
        formData.append('ajax_chat', '1');
        formData.append('message', message);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        showTyping(false);
        
        if (data.reply) {
            addMessage('bot', data.reply);
        }
    } catch (error) {
        console.error('Error:', error);
        showTyping(false);
        addMessage('bot', 'Sorry, I encountered an error. Please try again.');
    }
}

function addMessage(role, content) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${role}`;
    
    const time = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
    const icon = role === 'user' ? 'user' : 'robot';
    
    const formattedContent = content
        .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
        .replace(/\n/g, '<br>');
    
    messageDiv.innerHTML = `
        <div class="message-avatar">
            <i class="fas fa-${icon}"></i>
        </div>
        <div class="message-content">
            ${formattedContent}
            <div class="message-time">${time}</div>
        </div>
    `;
    
    messagesContainer.insertBefore(messageDiv, typingIndicator);
    scrollToBottom();
}

function showTyping(show) {
    typingIndicator.classList.toggle('show', show);
    sendBtn.disabled = show;
    if (show) scrollToBottom();
}

function scrollToBottom() {
    setTimeout(() => {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }, 100);
}

// Initial scroll
document.addEventListener('DOMContentLoaded', function() {
    scrollToBottom();
    messageInput.focus();
});
</script>

</body>
</html>