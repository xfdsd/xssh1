<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$conn = db();
$message = '';
$error = '';

if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    } catch (Exception $e) {
        $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(16));
    }
}
$token = $_SESSION['csrf_token'];

if (isset($_GET['delete'], $_GET['token'])) {
    if (hash_equals($token, $_GET['token'])) {
        $id = (int)$_GET['delete'];
        if ($id > 0) {
            $stmt = $conn->prepare('SELECT image FROM goods WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $goods = $result->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare('DELETE FROM goods WHERE id = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $message = '??????';
                if ($goods && !empty($goods['image'])) {
                    $imagePath = dirname(__DIR__) . $goods['image'];
                    if (is_file($imagePath)) {
                        @unlink($imagePath);
                    }
                }
            } else {
                $error = '?????' . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = '?????';
    }
}

$goodsList = [];
$result = $conn->query('SELECT id, name, price, stock, image, created_at FROM goods ORDER BY created_at DESC');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $goodsList[] = $row;
    }
    $result->free();
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>???? - ????</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        table img {max-width:80px;border-radius:6px;}
        nav a{margin-right:12px;}
    </style>
</head>
<body>
<header>
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
        <h1>????</h1>
        <nav>
            <a class="btn" href="/admin/goods_add.php">????</a>
            <a class="btn" href="/admin/logout.php">????</a>
        </nav>
    </div>
</header>
<main class="container">
    <h2>????</h2>
    <?php if ($message): ?>
        <div class="alert success"><?php echo h($message); ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
    <?php endif; ?>
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>??</th>
            <th>??</th>
            <th>??</th>
            <th>??</th>
            <th>????</th>
            <th>??</th>
        </tr>
        </thead>
        <tbody>
        <?php if (empty($goodsList)): ?>
            <tr><td colspan="7">????</td></tr>
        <?php else: ?>
            <?php foreach ($goodsList as $item): ?>
                <tr>
                    <td><?php echo (int)$item['id']; ?></td>
                    <td>
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo h($item['image']); ?>" alt="">
                        <?php else: ?>
                            ?
                        <?php endif; ?>
                    </td>
                    <td><?php echo h($item['name']); ?></td>
                    <td>?<?php echo number_format((float)$item['price'], 2); ?></td>
                    <td><?php echo (int)$item['stock']; ?></td>
                    <td><?php echo h($item['created_at']); ?></td>
                    <td>
                        <a class="btn" href="/admin/goods_edit.php?id=<?php echo (int)$item['id']; ?>">??</a>
                        <a class="btn" href="/admin/goods_list.php?delete=<?php echo (int)$item['id']; ?>&token=<?php echo $token; ?>" onclick="return confirm('????????');">??</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</main>
</body>
</html>
