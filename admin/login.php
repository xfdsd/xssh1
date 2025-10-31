<?php
require __DIR__ . '/../includes/init.php';

if (is_admin_logged_in()) {
    redirect('/admin/goods_list.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = '?????????';
    } else {
        $conn = db();
        $stmt = $conn->prepare('SELECT id, password FROM admins WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $admin = $result->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = (int)$admin['id'];
            redirect('/admin/goods_list.php');
        } else {
            $error = '????????';
        }
    }
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>????? - H5 ??</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .login-box {max-width:420px;margin:60px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,0.08);} 
        .login-box h2 {margin-top:0;text-align:center;}
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>?????</h1>
    </div>
</header>
<main class="container">
    <div class="login-box">
        <h2>????</h2>
        <?php if ($error): ?>
            <div class="alert error"><?php echo h($error); ?></div>
        <?php endif; ?>
        <form method="post">
            <label>???</label>
            <input type="text" name="username" required>
            <label>??</label>
            <input type="password" name="password" required>
            <button class="btn" style="width:100%;margin-top:20px;" type="submit">??</button>
        </form>
    </div>
</main>
</body>
</html>
