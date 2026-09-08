<?php
/**
 * GET /api/youth/wellbeing - Get chat history
 * POST /api/youth/wellbeing - Send message, get AI response
 * Headers: Authorization: Bearer {token}
 */

require_once __DIR__ . '/bootstrap.php';

$user = requireAuth();
$pdo = db();
$userId = (int)$user['id'];

// Create wellbeing_chat table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS wellbeing_chat (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        message TEXT NOT NULL,
        response TEXT NOT NULL,
        sentiment VARCHAR(50) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {}

// GET - Chat history
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = (int)($_GET['limit'] ?? 50);
    $limit = min($limit, 100);
    
    $stmt = $pdo->prepare('
        SELECT id, message, response, sentiment, created_at
        FROM wellbeing_chat
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT ?
    ');
    $stmt->execute([$userId, $limit]);
    $history = $stmt->fetchAll();
    
    // Reverse to show oldest first
    $history = array_reverse($history);
    
    jsonResponse(['success' => true, 'history' => $history]);
}

// POST - Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = getJsonBody();
    $message = trim($body['message'] ?? '');
    
    if (!$message) {
        jsonResponse(['error' => 'Message is required'], 400);
    }
    
    // Simple keyword-based AI (same as web version)
    $msg = strtolower($message);
    $response = '';
    $sentiment = 'neutral';
    
    // Crisis detection
    if (preg_match('/\b(suicid|kill myself|end (my|it all)|want to die|cant go on)\b/i', $msg)) {
        $sentiment = 'crisis';
        $response = "I'm really concerned about what you're going through. Please reach out to a trusted adult, counselor, or call the National Crisis Hotline (988) immediately. Your life matters, and help is available. 💙";
        
        // Alert LYDO admins
        try {
            $pdo->prepare('INSERT INTO notifications(user_id, title, message, type) VALUES(1,?,?,"danger")')->execute(
                ['🚨 Crisis Alert', 'Youth user ' . $user['first_name'] . ' ' . $user['last_name'] . ' expressed crisis keywords in wellbeing chat. Please reach out immediately.']
            );
        } catch (PDOException $e) {}
        
    } elseif (preg_match('/\b(stress|pressure|overwhelm|anxious|anxiety|worried|worry)\b/', $msg)) {
        $sentiment = 'stress';
        $response = "It sounds like you're feeling stressed. Remember to take deep breaths and break tasks into smaller steps. Have you tried talking to someone you trust about this? I'm here to listen. 🌸";
        
    } elseif (preg_match('/\b(sad|depress|lonely|alone|empty|hopeless)\b/', $msg)) {
        $sentiment = 'sadness';
        $response = "I'm sorry you're feeling this way. It's okay to feel sad sometimes, but you don't have to go through it alone. Consider reaching out to a friend, family member, or counselor. You matter. 💙";
        
    } elseif (preg_match('/\b(school|exam|test|study|grade|homework|class)\b/', $msg)) {
        $sentiment = 'academic';
        $response = "School can be tough! Try breaking your study time into 25-minute focused sessions with 5-minute breaks. Don't hesitate to ask teachers or classmates for help. You've got this! 📚";
        
    } elseif (preg_match('/\b(job|career|work|future|college|course)\b/', $msg)) {
        $sentiment = 'career';
        $response = "Thinking about your future is great! Explore different interests, talk to people in fields you're curious about, and remember that it's okay not to have everything figured out yet. Take it one step at a time. 🚀";
        
    } elseif (preg_match('/\b(relationship|friend|family|parent|sibling|boyfriend|girlfriend|break up)\b/', $msg)) {
        $sentiment = 'relationship';
        $response = "Relationships can be complicated. Communication and honesty are key. If someone is hurting you, it's okay to set boundaries. Surround yourself with people who respect and care about you. 💛";
        
    } elseif (preg_match('/\b(sleep|tired|exhausted|insomnia|cant sleep)\b/', $msg)) {
        $sentiment = 'sleep';
        $response = "Good sleep is so important! Try to keep a consistent bedtime, avoid screens an hour before bed, and create a relaxing routine. If sleep problems persist, consider talking to a healthcare provider. 😴";
        
    } elseif (preg_match('/\b(confident|confidence|self esteem|insecure|not good enough)\b/', $msg)) {
        $sentiment = 'confidence';
        $response = "Building confidence takes time. Focus on your strengths, set small achievable goals, and celebrate your wins. Remember, you are worthy exactly as you are. 🌟";
        
    } elseif (preg_match('/\b(angry|anger|mad|frustrated|annoyed)\b/', $msg)) {
        $sentiment = 'anger';
        $response = "It's normal to feel angry sometimes. Try to identify what's triggering it, and find healthy outlets like exercise, journaling, or talking to someone. Take a few deep breaths. You'll get through this. 🧘";
        
    } elseif (preg_match('/\b(motivat|inspired|goal|dream|ambition)\b/', $msg)) {
        $sentiment = 'motivation';
        $response = "I love your energy! Set clear, realistic goals and break them down into daily actions. Celebrate small wins along the way. Remember, progress is progress, no matter how small. Keep going! 💪";
        
    } elseif (preg_match('/\b(money|financial|budget|expenses|afford)\b/', $msg)) {
        $sentiment = 'financial';
        $response = "Financial stress is real. Start by tracking your expenses, making a simple budget, and looking for scholarship or assistance programs. LYDO also offers financial assistance — check the portal! 💰";
        
    } elseif (preg_match('/\b(health|sick|pain|doctor|medical)\b/', $msg)) {
        $sentiment = 'health';
        $response = "Your health is important! If you're experiencing physical symptoms, please consult a healthcare provider. Don't ignore signs your body is giving you. Take care of yourself. 🏥";
        
    } elseif (preg_match('/\b(thank|thanks|helpful|appreciate)\b/', $msg)) {
        $sentiment = 'positive';
        $response = "You're very welcome! I'm glad I could help. Remember, LYDO is here to support you anytime. Keep taking care of yourself! 😊";
        
    } elseif (preg_match('/\b(hello|hi|hey|good morning|good afternoon)\b/', $msg)) {
        $sentiment = 'greeting';
        $response = "Hello! I'm here to support your mental and emotional well-being. Feel free to share what's on your mind, and I'll do my best to help. How are you feeling today? 🌈";
        
    } else {
        $response = "I hear you. Sometimes it helps just to express what you're feeling. If you'd like more specific guidance, try telling me more about what you're experiencing. I'm here to listen. 💙";
    }
    
    // Save to database
    $stmt = $pdo->prepare('INSERT INTO wellbeing_chat (user_id, message, response, sentiment) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $message, $response, $sentiment]);
    
    jsonResponse([
        'success' => true,
        'response' => $response,
        'sentiment' => $sentiment
    ]);
}

jsonResponse(['error' => 'Method not allowed'], 405);
