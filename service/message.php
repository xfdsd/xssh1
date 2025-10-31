<?php
require __DIR__ . '/../includes/init.php';
require_service();

$conn = db();
$serviceId = (int)($_SESSION['service_id'] ?? 0);
$serviceName = $_SESSION['service_name'] ?? '??';

$message = '';
$error = '';

if (isset($_GET['success']) && $_GET['success'] === '1') {
    $message = '回复已发送给用户。';
}

$pendingMessages = [];
$result = $conn->query('SELECT id, user_nickname, message, created_at FROM service_messages WHERE status = 0 ORDER BY created_at ASC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pendingMessages[] = $row;
    }
    $result->free();
}

$selectedId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($selectedId === 0 && !empty($pendingMessages)) {
    $selectedId = (int)$pendingMessages[0]['id'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedId = (int)($_POST['message_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    if ($selectedId <= 0) {
        $error = '????????';
    } elseif ($reply === '') {
        $error = '????????';
    } else {
        $stmt = $conn->prepare('UPDATE service_messages SET reply = ?, status = 1, agent_id = ?, replied_at = NOW() WHERE id = ?');
        $stmt->bind_param('sii', $reply, $serviceId, $selectedId);
        if ($stmt->execute()) {
            header('Location: /service/message.php?id=' . $selectedId . '&success=1');
            exit;
        } else {
            $error = '保存失败：' . $stmt->error;
        }
        $stmt->close();
    }
}

$selectedMessage = null;
if ($selectedId > 0) {
    $stmt = $conn->prepare('SELECT m.*, a.display_name AS agent_name FROM service_messages m LEFT JOIN service_agents a ON m.agent_id = a.id WHERE m.id = ?');
    $stmt->bind_param('i', $selectedId);
    $stmt->execute();
    $result = $stmt->get_result();
    $selectedMessage = $result->fetch_assoc();
    $stmt->close();
}

$history = [];
if ($selectedMessage) {
    $stmt = $conn->prepare('SELECT id, message, reply, status, created_at, replied_at FROM service_messages WHERE user_nickname = ? ORDER BY created_at ASC');
    $stmt->bind_param('s', $selectedMessage['user_nickname']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    $stmt->close();
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>?????? - H5 ??</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .layout{display:flex;flex-direction:column;gap:20px;}
        @media(min-width:900px){.layout{flex-direction:row;align-items:flex-start;}}
        .list-panel{flex:1;background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);max-height:540px;overflow:auto;}
        .detail-panel{flex:2;background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
        .ticket{border-bottom:1px solid #eee;padding:12px 0;}
        .ticket:last-child{border-bottom:none;}
        .ticket a{text-decoration:none;color:#1a73e8;display:block;}
        .history-item{margin-bottom:15px;padding:12px;border-radius:6px;background:#f9f9f9;}
        .history-item .meta{font-size:12px;color:#777;margin-top:6px;}
        .empty{text-align:center;color:#777;margin:40px 0;}
        nav a{margin-right:12px;}
    </style>
</head>
<body>
<header>
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
        <h1>??????</h1>
        <nav>
            <span style="margin-right:12px;">?????<?php echo h($serviceName); ?></span>
            <a class="btn" href="/service/logout.php">????</a>
        </nav>
    </div>
</header>
<main class="container">
    <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
    <?php endif; ?>

    <div class="layout">
        <div class="list-panel">
            <h3>?????</h3>
            <?php if (empty($pendingMessages)): ?>
                <p class="empty">????????</p>
            <?php else: ?>
                <?php foreach ($pendingMessages as $item): ?>
                    <?php
                        $summary = $item['message'];
                        if (function_exists('mb_strimwidth')) {
                            $summary = mb_strimwidth($summary, 0, 60, '...', 'UTF-8');
                        } elseif (strlen($summary) > 30) {
                            $summary = substr($summary, 0, 30) . '...';
                        }
                    ?>
                    <div class="ticket">
                        <a href="/service/message.php?id=<?php echo (int)$item['id']; ?>">
                            <strong><?php echo h($item['user_nickname']); ?></strong>
                            <div style="margin-top:6px;color:#555;"><?php echo h($summary); ?></div>
                            <div class="meta" style="font-size:12px;color:#999;">???<?php echo h($item['created_at']); ?></div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="detail-panel">
            <?php if (!$selectedMessage): ?>
                <p class="empty">???????????????</p>
            <?php else: ?>
                <h3>???<?php echo h($selectedMessage['user_nickname']); ?></h3>
                <p><strong>?????</strong><?php echo h($selectedMessage['created_at']); ?></p>
                <p style="background:#f3f6fb;padding:12px;border-radius:6px;"><?php echo nl2br(h($selectedMessage['message'])); ?></p>

                <form method="post" style="margin-top:20px;">
                    <input type="hidden" name="message_id" value="<?php echo (int)$selectedMessage['id']; ?>">
                    <label>????</label>
                    <textarea name="reply" required><?php echo h($selectedMessage['reply'] ?? ''); ?></textarea>
                    <button class="btn" type="submit" style="margin-top:12px;">????</button>
                </form>

                <div style="margin-top:30px;">
                    <h4>????</h4>
                    <?php if (empty($history)): ?>
                        <p class="empty">???????</p>
                    <?php else: ?>
                        <?php foreach ($history as $row): ?>
                            <div class="history-item">
                                <div><strong>???</strong><?php echo nl2br(h($row['message'])); ?></div>
                                <div class="meta">???<?php echo h($row['created_at']); ?></div>
                                <?php if (!empty($row['reply'])): ?>
                                    <div style="margin-top:10px;"><strong>???</strong><?php echo nl2br(h($row['reply'])); ?></div>
                                    <div class="meta">?????<?php echo h($row['replied_at']); ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>
</body>
</html>
