<?php
require __DIR__ . '/includes/init.php';

$conn = db();
$goods = [];
$result = $conn->query('SELECT id, name, price, image, stock FROM goods ORDER BY created_at DESC LIMIT 20');
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $goods[] = $row;
    }
    $result->free();
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>H5 ????</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        .goods-grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;}
        .goods-item {background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05);transition:transform 0.3s ease;}
        .goods-item:hover {transform:translateY(-4px);}
        .goods-thumb {height:160px;background-size:cover;background-position:center;}
        .goods-body {padding:16px;}
        .goods-body h3 {margin:0 0 10px;font-size:18px;}
        .goods-meta {font-size:14px;color:#666;margin-top:8px;}
    </style>
</head>
<body>
<header>
    <div class="container">
        <h1>H5 ??</h1>
    </div>
</header>
<main class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <h2 style="margin:0;">????</h2>
        <div>
            <a class="btn" href="/service.php">????</a>
            <a class="btn" href="/admin/login.php">?????</a>
        </div>
    </div>

    <?php if (empty($goods)): ?>
        <p class="alert">??????????????</p>
    <?php else: ?>
        <div class="goods-grid">
            <?php foreach ($goods as $item): ?>
                <a class="goods-item" href="/detail.php?id=<?php echo (int)$item['id']; ?>">
                    <div class="goods-thumb" style="background-image:url('<?php echo h($item['image'] ?: '/assets/img/placeholder.png'); ?>');"></div>
                    <div class="goods-body">
                        <h3><?php echo h($item['name']); ?></h3>
                        <div class="goods-meta">????<?php echo number_format((float)$item['price'], 2); ?></div>
                        <div class="goods-meta">???<?php echo (int)$item['stock']; ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</body>
</html>
