<?php
require __DIR__ . '/../includes/init.php';
require_admin();

$conn = db();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    die('????');
}

$stmt = $conn->prepare('SELECT id, name, price, stock, image, description FROM goods WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$goods = $result->fetch_assoc();
$stmt->close();

if (!$goods) {
    die('?????');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $priceInput = trim($_POST['price'] ?? '0');
    $stock = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $priceValue = (float)$priceInput;
    $imagePath = $goods['image'];

    if ($name === '' || $priceInput === '') {
        $error = '???????????';
    } else {
        if (!empty($_FILES['image']['name'])) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['image']['type'], $allowedTypes, true)) {
                $error = '??? JPG?PNG?GIF ?????';
            } elseif ($_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                $error = '???????????';
            } else {
                $uploadDir = dirname(__DIR__) . '/uploads/';
                if (!is_dir($uploadDir)) {
                    @mkdir($uploadDir, 0775, true);
                }
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                try {
                    $randomToken = bin2hex(random_bytes(4));
                } catch (Exception $e) {
                    $randomToken = bin2hex(openssl_random_pseudo_bytes(4));
                }
                $filename = 'goods_' . date('YmdHis') . '_' . $randomToken . '.' . $ext;
                $target = $uploadDir . $filename;
                if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                    $error = '???????????????';
                } else {
                    if (!empty($goods['image'])) {
                        $old = dirname(__DIR__) . $goods['image'];
                        if (is_file($old)) {
                            @unlink($old);
                        }
                    }
                    $imagePath = '/uploads/' . $filename;
                }
            }
        }

        if ($error === '') {
            $stmt = $conn->prepare('UPDATE goods SET name = ?, price = ?, stock = ?, image = ?, description = ? WHERE id = ?');
            $stmt->bind_param('sdissi', $name, $priceValue, $stock, $imagePath, $description, $id);
            if ($stmt->execute()) {
                $success = '????????';
                $goods['name'] = $name;
                $goods['price'] = $priceValue;
                $goods['stock'] = $stock;
                $goods['image'] = $imagePath;
                $goods['description'] = $description;
            } else {
                $error = '?????' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

?><!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>???? - ????</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        textarea{min-height:120px;}
        nav a{margin-right:12px;}
        .preview{margin:10px 0;}
        .preview img{max-width:160px;border-radius:8px;}
    </style>
</head>
<body>
<header>
    <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
        <h1>????</h1>
        <nav>
            <a class="btn" href="/admin/goods_list.php">????</a>
            <a class="btn" href="/admin/logout.php">????</a>
        </nav>
    </div>
</header>
<main class="container">
    <h2>????</h2>
    <?php if ($error): ?>
        <div class="alert error"><?php echo h($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert success"><?php echo h($success); ?></div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label>????</label>
        <input type="text" name="name" value="<?php echo h($goods['name']); ?>" required>
        <label>??</label>
        <input type="number" step="0.01" min="0" name="price" value="<?php echo h($goods['price']); ?>" required>
        <label>??</label>
        <input type="number" min="0" name="stock" value="<?php echo h($goods['stock']); ?>" required>
        <label>????</label>
        <div class="preview">
            <?php if (!empty($goods['image'])): ?>
                <img src="<?php echo h($goods['image']); ?>" alt="">
            <?php else: ?>
                ?
            <?php endif; ?>
        </div>
        <label>????</label>
        <input type="file" name="image" accept="image/*">
        <label>????</label>
        <textarea name="description"><?php echo h($goods['description']); ?></textarea>
        <button class="btn" type="submit" style="margin-top:20px;">??</button>
    </form>
</main>
</body>
</html>
