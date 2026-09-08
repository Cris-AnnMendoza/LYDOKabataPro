<?php
require_once __DIR__ . '/../shared/config.php';
require_once __DIR__ . '/../shared/ai_config.php';
if (empty($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$pdo    = db();
$userId = (int)$_SESSION['user_id'];
$uStmt  = $pdo->prepare('SELECT * FROM youth_users WHERE id=? LIMIT 1');
$uStmt->execute([$userId]);
$user = $uStmt->fetch();
$nc   = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0');
$nc->execute([$userId]);
$notifCount = (int)$nc->fetchColumn();

// Handle AJAX chat
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    header('Content-Type: application/json');
    $userMsg = trim($_POST['message'] ?? '');
    if (!$userMsg) { echo json_encode(['reply' => '']); exit; }

    // Load recent history (last 10 exchanges)
    $histStmt = $pdo->prepare('SELECT role, message FROM ai_chat_history WHERE user_id=? ORDER BY created_at DESC LIMIT 20');
    $histStmt->execute([$userId]);
    $history = array_reverse($histStmt->fetchAll());

    $messages = [
        ['role' => 'system', 'content' =>
            "You are Kaya, a compassionate AI well-being assistant for the Local Youth Development Office (LYDO) of Sta. Cruz, Laguna, Philippines. " .
            "You help youth aged 15-30 with mental health support, career guidance, program information, and community engagement. " .
            "You are warm, encouraging, and speak in a friendly Filipino-English (Taglish) tone when appropriate. " .
            "LYDO services include: Accreditation for youth organizations, Assistance Program for youth-led activities, " .
            "Youth Volunteer Program (including Linggo ng Kabataan and Junior Officials Program), and Iskolar ng Bayan Scholarship. " .
            "For mental health crises, always refer to: National Crisis Hotline 1553, Hopeline 02-8804-4673, or the nearest health center. " .
            "The youth's name is " . htmlspecialchars($user['first_name']) . " from " . htmlspecialchars($user['barangay'] ?? 'Sta. Cruz') . ". " .
            "Keep responses concise, supportive, and actionable. Do not provide medical diagnoses."
        ]
    ];

    foreach ($history as $h) {
        $messages[] = ['role' => $h['role'], 'content' => $h['message']];
    }
    $messages[] = ['role' => 'user', 'content' => $userMsg];

    $reply = openai_chat($messages, 0.8, 500);

    // Save to history
    $pdo->prepare('INSERT INTO ai_chat_history (user_id,role,message) VALUES (?,?,?)')->execute([$userId,'user',$userMsg]);
    $pdo->prepare('INSERT INTO ai_chat_history (user_id,role,message) VALUES (?,?,?)')->execute([$userId,'assistant',$reply]);

    echo json_encode(['reply' => $reply]);
    exit;
}

// Handle clear history
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear'])) {
    $pdo->prepare('DELETE FROM ai_chat_history WHERE user_id=?')->execute([$userId]);
    header('Location: ai_assistant.php'); exit;
}

// Load chat history for display
$histStmt = $pdo->prepare('SELECT role, message, created_at FROM ai_chat_history WHERE user_id=? ORDER BY created_at ASC LIMIT 100');
$histStmt->execute([$userId]);
$chatHistory = $histStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/><meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>AI Well-being Assistant – LYDO</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link rel="stylesheet" href="youth.css"/>
<style>
.chat-wrap{display:flex;flex-direction:column;height:calc(100vh - 130px);max-width:800px;margin:0 auto}
.chat-header{background:linear-gradient(135deg,#0d3b6e,#1565c0);border-radius:16px 16px 0 0;padding:18px 22px;display:flex;align-items:center;gap:14px;color:#fff}
.kaya-avatar{width:48px;height:48px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0}
.chat-messages{flex:1;overflow-y:auto;padding:20px;background:#f8fafc;display:flex;flex-direction:column;gap:14px}
.msg{display:flex;gap:10px;max-width:80%}
.msg.user{align-self:flex-end;flex-direction:row-reverse}
.msg-bubble{padding:12px 16px;border-radius:16px;font-size:.9rem;line-height:1.6;white-space:pre-wrap}
.msg.assistant .msg-bubble{background:#fff;border:1px solid #e2e8f0;border-radius:4px 16px 16px 16px;color:#1e293b}
.msg.user .msg-bubble{background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border-radius:16px 4px 16px 16px}
.msg-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.85rem;flex-shrink:0}
.msg.assistant .msg-avatar{background:#e3f2fd;color:#1565c0}
.msg.user .msg-avatar{background:#1565c0;color:#fff}
.chat-input-wrap{background:#fff;border:1px solid #e2e8f0;border-radius:0 0 16px 16px;padding:14px 16px;display:flex;gap:10px;align-items:flex-end}
.chat-input{flex:1;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-family:inherit;font-size:.9rem;outline:none;resize:none;max-height:120px;transition:.2s}
.chat-input:focus{border-color:#1565c0;box-shadow:0 0 0 3px rgba(21,101,192,.1)}
.btn-send{width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,#0d3b6e,#1565c0);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1rem;transition:.2s;flex-shrink:0}
.btn-send:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(21,101,192,.3)}
.btn-send:disabled{opacity:.5;cursor:not-allowed;transform:none}
.typing{display:flex;gap:4px;align-items:center;padding:8px 12px}
.typing span{width:8px;height:8px;border-radius:50%;background:#94a3b8;animation:bounce .8s infinite}
.typing span:nth-child(2){animation-delay:.15s}
.typing span:nth-child(3){animation-delay:.3s}
@keyframes bounce{0%,80%,100%{transform:translateY(0)}40%{transform:translateY(-6px)}}
.quick-btns{display:flex;gap:8px;flex-wrap:wrap;padding:10px 20px;background:#f8fafc;border-top:1px solid #e2e8f0}
.quick-btn{padding:6px 14px;background:#fff;border:1.5px solid #e2e8f0;border-radius:50px;font-family:inherit;font-size:.78rem;font-weight:600;color:#475569;cursor:pointer;transition:.2s}
.quick-btn:hover{border-color:#1565c0;color:#1565c0;background:#e3f2fd}
.crisis-banner{background:#ffebee;border:1px solid #ef9a9a;border-radius:10px;padding:10px 14px;margin:0 20px 10px;font-size:.8rem;color:#c62828;display:flex;align-items:center;gap:8px}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>
<div class="y-main">
<?php include 'topbar.php'; ?>
<main class="y-content" style="padding:16px;display:flex;flex-direction:column">

<div class="chat-wrap">
  <div class="chat-header">
    <div class="kaya-avatar"><i class="fas fa-robot"></i></div>
    <div>
      <div style="font-size:1.1rem;font-weight:800">Kaya — AI Well-being Assistant</div>
      <div style="font-size:.8rem;opacity:.8">Your LYDO companion for guidance, support, and information</div>
    </div>
    <form method="POST" style="margin-left:auto">
      <input type="hidden" name="clear" value="1"/>
      <button type="submit" style="background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);color:#fff;padding:6px 12px;border-radius:8px;font-family:inherit;font-size:.78rem;cursor:pointer" onclick="return confirm('Clear chat history?')">
        <i class="fas fa-trash"></i> Clear
      </button>
    </form>
  </div>

  <div class="crisis-banner">
    <i class="fas fa-heart"></i>
    <span>If you're in crisis, call <strong>1553</strong> (National Crisis Hotline) or <strong>Hopeline: 02-8804-4673</strong></span>
  </div>

  <div class="chat-messages" id="chatMessages">
    <?php if (empty($chatHistory)): ?>
    <div class="msg assistant">
      <div class="msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="msg-bubble">
        Kumusta, <?=htmlspecialchars($user['first_name'])?>! 👋 I'm <strong>Kaya</strong>, your AI well-being assistant from LYDO Sta. Cruz, Laguna.

I'm here to help you with:
• 💬 Mental health support and guidance
• 🎓 Career and scholarship information
• 🏆 LYDO programs and how to apply
• 📝 Resume building tips
• 🤝 Community engagement opportunities

How can I help you today?
      </div>
    </div>
    <?php else: foreach ($chatHistory as $msg): ?>
    <div class="msg <?=$msg['role']?>">
      <div class="msg-avatar">
        <?php if ($msg['role']==='assistant'): ?><i class="fas fa-robot"></i>
        <?php else: ?><?=strtoupper(substr($user['first_name'],0,1))?><?php endif; ?>
      </div>
      <div class="msg-bubble"><?=nl2br(htmlspecialchars($msg['message']))?></div>
    </div>
    <?php endforeach; endif; ?>
    <div id="typingIndicator" style="display:none" class="msg assistant">
      <div class="msg-avatar"><i class="fas fa-robot"></i></div>
      <div class="msg-bubble"><div class="typing"><span></span><span></span><span></span></div></div>
    </div>
  </div>

  <div class="quick-btns">
    <button class="quick-btn" onclick="sendQuick('What LYDO programs can I join?')">📋 LYDO Programs</button>
    <button class="quick-btn" onclick="sendQuick('How do I apply for the Iskolar ng Bayan scholarship?')">🎓 Scholarship</button>
    <button class="quick-btn" onclick="sendQuick('I am feeling stressed and overwhelmed lately.')">💙 I need support</button>
    <button class="quick-btn" onclick="sendQuick('Help me with career guidance')">💼 Career Guidance</button>
    <button class="quick-btn" onclick="sendQuick('How do I volunteer for LYDO activities?')">🤝 Volunteer</button>
  </div>

  <div class="chat-input-wrap">
    <textarea id="chatInput" class="chat-input" placeholder="Type your message... (Enter to send, Shift+Enter for new line)" rows="1"></textarea>
    <button class="btn-send" id="sendBtn" onclick="sendMessage()"><i class="fas fa-paper-plane"></i></button>
  </div>
</div>

</main>
</div>

<script>
const chatMessages = document.getElementById('chatMessages');
const chatInput    = document.getElementById('chatInput');
const sendBtn      = document.getElementById('sendBtn');
const typing       = document.getElementById('typingIndicator');

// Auto-scroll to bottom
function scrollBottom() { chatMessages.scrollTop = chatMessages.scrollHeight; }
scrollBottom();

// Auto-resize textarea
chatInput.addEventListener('input', function() {
  this.style.height = 'auto';
  this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Enter to send
chatInput.addEventListener('keydown', function(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
});

function sendQuick(msg) { chatInput.value = msg; sendMessage(); }

function appendMsg(role, text) {
  const div = document.createElement('div');
  div.className = 'msg ' + role;
  const avatar = document.createElement('div');
  avatar.className = 'msg-avatar';
  avatar.innerHTML = role === 'assistant' ? '<i class="fas fa-robot"></i>' : '<?=strtoupper(substr($user['first_name'],0,1))?>';
  const bubble = document.createElement('div');
  bubble.className = 'msg-bubble';
  bubble.textContent = text;
  div.appendChild(avatar);
  div.appendChild(bubble);
  chatMessages.insertBefore(div, typing);
  scrollBottom();
}

async function sendMessage() {
  const msg = chatInput.value.trim();
  if (!msg || sendBtn.disabled) return;

  chatInput.value = '';
  chatInput.style.height = 'auto';
  sendBtn.disabled = true;

  appendMsg('user', msg);
  typing.style.display = 'flex';
  scrollBottom();

  try {
    const res = await fetch('ai_assistant.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'message=' + encodeURIComponent(msg)
    });
    const data = await res.json();
    typing.style.display = 'none';
    appendMsg('assistant', data.reply);
  } catch(e) {
    typing.style.display = 'none';
    appendMsg('assistant', 'Sorry, I encountered an error. Please try again.');
  }

  sendBtn.disabled = false;
  chatInput.focus();
}
</script>
</body>
</html>
