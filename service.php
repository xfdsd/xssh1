<?php
require __DIR__ . '/includes/init.php';

$conn = db();

if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    $nickname = trim($_GET['nickname'] ?? '');
    $data = [];
    if ($nickname !== '') {
        $stmt = $conn->prepare('SELECT id, message, reply, status, created_at, replied_at FROM service_messages WHERE user_nickname = ? ORDER BY created_at ASC');
        $stmt->bind_param('s', $nickname);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
    echo json_encode(['messages' => $data]);
    exit;
}

$messageInfo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nickname = trim($_POST['nickname'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($nickname === '' || $message === '') {
        $messageInfo = ['type' => 'error', 'text' => '???????????'];
    } else {
        $_SESSION['service_user_nickname'] = $nickname;
        $stmt = $conn->prepare('INSERT INTO service_messages (user_nickname, message) VALUES (?, ?)');
        $stmt->bind_param('ss', $nickname, $message);
        if ($stmt->execute()) {
            $messageInfo = ['type' => 'success', 'text' => '??????????????'];
        } else {
            $messageInfo = ['type' => 'error', 'text' => '?????' . $stmt->error];
        }
        $stmt->close();
    }
}

$currentNickname = $_SESSION['service_user_nickname'] ?? '';

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>???? - H5 ??</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .chat-box {background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);min-height:400px;display:flex;flex-direction:column;}
        .chat-messages {flex:1;overflow-y:auto;padding-right:10px;margin-bottom:20px;}
        .chat-message {margin-bottom:15px;padding:10px;border-radius:6px;max-width:80%;}
        .chat-message.user {background:#e3f2fd;align-self:flex-end;text-align:right;}
        .chat-message.agent {background:#f0f0f0;align-self:flex-start;}
        .chat-meta {font-size:12px;color:#777;margin-top:4px;}
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>????</h1>
    </div>
</header>
<main class="container">
    <p><a class="btn" href="/index.php">????</a></p>
    <?php if ($messageInfo): ?>
        <div class="alert <?php echo $messageInfo['type'] === 'success' ? 'success' : 'error'; ?>"><?php echo h($messageInfo['text']); ?></div>
    <?php endif; ?>
    <div class="chat-box">
        <div class="chat-messages" id="chatMessages">
            <p style="color:#777;">????????????????????????</p>
        </div>
        <form method="post" id="chatForm">
            <label>??</label>
            <input type="text" name="nickname" id="nickname" value="<?php echo h($currentNickname); ?>" required>
            <label>??</label>
            <textarea name="message" id="message" rows="3" required></textarea>
            <button class="btn" type="submit" style="margin-top:10px;">??</button>
        </form>
    </div>
</main>

<script>
    const chatMessages = document.getElementById('chatMessages');
    const nicknameInput = document.getElementById('nickname');

    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function renderMessages(items) {
        if (!items.length) {
            chatMessages.innerHTML = '<p style="color:#777;">??????????</p>';
            return;
        }
        chatMessages.innerHTML = '';
        items.forEach(item => {
            const userDiv = document.createElement('div');
            userDiv.className = 'chat-message user';
            userDiv.innerHTML = `<div>${escapeHtml(item.message)}</div><div class="chat-meta">${item.created_at}</div>`;
            chatMessages.appendChild(userDiv);

            if (item.reply) {
                const agentDiv = document.createElement('div');
                agentDiv.className = 'chat-message agent';
                agentDiv.innerHTML = `<div>${escapeHtml(item.reply)}</div><div class="chat-meta">${item.replied_at ?? ''}</div>`;
                chatMessages.appendChild(agentDiv);
            }
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    async function fetchMessages() {
        const nickname = nicknameInput.value.trim();
        if (!nickname) {
            return;
        }
        try {
            const response = await fetch(`service.php?ajax=1&nickname=${encodeURIComponent(nickname)}`);
            if (!response.ok) return;
            const data = await response.json();
            renderMessages(data.messages || []);
        } catch (e) {
            console.error(e);
        }
    }

    setInterval(fetchMessages, 4000);
    window.addEventListener('load', fetchMessages);
</script>
</body>
</html>
