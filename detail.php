<?php
require __DIR__ . '/includes/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('????');
}

$conn = db();
$stmt = $conn->prepare('SELECT id, name, price, image, stock, description, created_at FROM goods WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$goods = $result->fetch_assoc();
$stmt->close();

if (!$goods) {
    die('?????');
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title><?php echo h($goods['name']); ?> - H5 ??</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .goods-detail {background:#fff;border-radius:8px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}
        .goods-header {display:flex;flex-direction:column;gap:20px;}
        @media (min-width:768px){.goods-header{flex-direction:row;}}
        .goods-image {flex:1;min-height:260px;background-size:cover;background-position:center;border-radius:8px;}
        .goods-info {flex:1;}
        .goods-info h2 {margin-top:0;}
        .goods-desc {margin-top:20px;line-height:1.6;color:#555;white-space:pre-wrap;}
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>H5 ??</h1>
    </div>
</header>
<main class="container">
    <p><a class="btn" href="/index.php">????</a></p>
    <div class="goods-detail">
        <div class="goods-header">
            <div class="goods-image" style="background-image:url('<?php echo h($goods['image'] ?: '/assets/img/placeholder.png'); ?>');"></div>
            <div class="goods-info">
                <h2><?php echo h($goods['name']); ?></h2>
                <p>???<strong style="color:#e53935;">?<?php echo number_format((float)$goods['price'], 2); ?></strong></p>
                <p>???<?php echo (int)$goods['stock']; ?></p>
                <button class="btn" onclick="alert('?????????????');">????</button>
            </div>
        </div>
        <div class="goods-desc">
            <h3>????</h3>
            <p><?php echo nl2br(h($goods['description'] ?: '????')); ?></p>
        </div>
    </div>
</main>
</body>
</html>
