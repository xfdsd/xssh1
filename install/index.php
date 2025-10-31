<?php
session_start();

$rootPath = dirname(__DIR__);
$lockFile = $rootPath . '/install/install.lock';

if (file_exists($lockFile)) {
    exit('???????????????? install/install.lock ???');
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$messages = [];

function checkEnvironment(): array
{
    global $rootPath;

    $checks = [];
    $phpVersion = PHP_VERSION;
    $checks[] = [
        'name' => 'PHP ?? >= 7.0',
        'status' => version_compare($phpVersion, '7.0.0', '>='),
        'detail' => '?????' . $phpVersion,
    ];

    $checks[] = [
        'name' => 'MySQLi ??',
        'status' => extension_loaded('mysqli'),
        'detail' => extension_loaded('mysqli') ? '???' : '???',
    ];

    $writableDirs = [
        $rootPath . '/config' => 'config ????',
        $rootPath . '/uploads' => 'uploads ????',
    ];

    foreach ($writableDirs as $path => $label) {
        if (!is_dir($path)) {
            @mkdir($path, 0775, true);
        }
        $isWritable = is_writable($path);
        $checks[] = [
            'name' => $label,
            'status' => $isWritable,
            'detail' => $isWritable ? '????' : '?????????',
        ];
    }

    return $checks;
}

function renderHeader(int $step): void
{
    echo '<!DOCTYPE html><html lang="zh-CN"><head><meta charset="UTF-8">';
    echo '<title>H5 ??????</title>';
    echo '<link rel="stylesheet" href="../assets/css/style.css">';
    echo '<style>.step-nav{display:flex;gap:10px;margin:20px 0;}';
    echo '.step-nav div{padding:10px 16px;border-radius:4px;background:#e0e0e0;}';
    echo '.step-nav .active{background:#1a73e8;color:#fff;}';
    echo 'form{background:#fff;padding:20px;border-radius:6px;box-shadow:0 2px 8px rgba(0,0,0,0.05);}';
    echo '</style></head><body><header><div class="container"><h1>H5 ??????</h1></div></header><div class="container">';
    echo '<div class="step-nav">';
    $titles = [1 => '????', 2 => '?????', 3 => '?????', 4 => '????'];
    foreach ($titles as $index => $label) {
        $class = $index === $step ? 'active' : '';
        echo '<div class="' . $class . '">' . $index . '. ' . $label . '</div>';
    }
    echo '</div>';
}

function renderFooter(): void
{
    echo '</div></body></html>';
}

function createDatabase(array $dbConfig, array &$errors, array &$messages): bool
{
    $conn = @new mysqli(
        $dbConfig['host'],
        $dbConfig['user'],
        $dbConfig['pass'],
        '',
        (int)$dbConfig['port']
    );

    if ($conn->connect_errno) {
        $errors[] = '????????' . $conn->connect_error;
        return false;
    }

    $dbName = $conn->real_escape_string($dbConfig['name']);
    if (!$conn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
        $errors[] = '????????' . $conn->error;
        $conn->close();
        return false;
    }

    if (!$conn->select_db($dbConfig['name'])) {
        $errors[] = '????????' . $conn->error;
        $conn->close();
        return false;
    }

    $sqlStatements = [
        'CREATE TABLE IF NOT EXISTS admins (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS service_agents (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            display_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS goods (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            image VARCHAR(255) DEFAULT NULL,
            stock INT NOT NULL DEFAULT 0,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        'CREATE TABLE IF NOT EXISTS service_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_nickname VARCHAR(100) NOT NULL,
            message TEXT NOT NULL,
            reply TEXT DEFAULT NULL,
            status TINYINT NOT NULL DEFAULT 0,
            agent_id INT UNSIGNED DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            replied_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_status (status),
            CONSTRAINT fk_agent FOREIGN KEY (agent_id) REFERENCES service_agents(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
    ];

    foreach ($sqlStatements as $statement) {
        if (!$conn->query($statement)) {
            $errors[] = '?? SQL ????' . $conn->error;
            $conn->close();
            return false;
        }
    }

    $messages[] = '????????????';
    $conn->close();
    return true;
}

function writeConfig(array $dbConfig, string $adminEmail): bool
{
    $configPath = __DIR__ . '/../config/config.php';
    $content = "<?php\nreturn [\n" .
        "    'db_host' => '" . addslashes($dbConfig['host']) . "',\n" .
        "    'db_port' => " . (int)$dbConfig['port'] . ",\n" .
        "    'db_user' => '" . addslashes($dbConfig['user']) . "',\n" .
        "    'db_pass' => '" . addslashes($dbConfig['pass']) . "',\n" .
        "    'db_name' => '" . addslashes($dbConfig['name']) . "',\n" .
        "    'admin_email' => '" . addslashes($adminEmail) . "',\n" .
        "];\n";

    return (bool)file_put_contents($configPath, $content);
}

if ($step === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConfig = [
        'host' => trim($_POST['db_host'] ?? ''),
        'port' => trim($_POST['db_port'] ?? '3306'),
        'user' => trim($_POST['db_user'] ?? ''),
        'pass' => trim($_POST['db_pass'] ?? ''),
        'name' => trim($_POST['db_name'] ?? ''),
    ];

    $_SESSION['install_db'] = $dbConfig;

    if (isset($_POST['action']) && $_POST['action'] === 'test') {
        $link = @new mysqli($dbConfig['host'], $dbConfig['user'], $dbConfig['pass'], '', (int)$dbConfig['port']);
        if ($link->connect_errno) {
            $errors[] = '???????' . $link->connect_error;
        } else {
            $messages[] = '????????';
            $link->close();
        }
    } else {
        if (in_array('', $dbConfig, true)) {
            $errors[] = '???????????';
        } else {
            header('Location: index.php?step=3');
            exit;
        }
    }
}

if ($step === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConfig = $_SESSION['install_db'] ?? null;
    if (!$dbConfig) {
        $errors[] = '????????????????';
    } else {
        if (createDatabase($dbConfig, $errors, $messages)) {
            header('Location: index.php?step=4');
            exit;
        }
    }
}

if ($step === 4 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbConfig = $_SESSION['install_db'] ?? null;
    if (!$dbConfig) {
        $errors[] = '????????????????';
    } else {
        $adminUser = trim($_POST['admin_user'] ?? '');
        $adminPass = trim($_POST['admin_pass'] ?? '');
        $adminEmail = trim($_POST['admin_email'] ?? '');
        $serviceUser = trim($_POST['service_user'] ?? '');
        $servicePass = trim($_POST['service_pass'] ?? '');
        $serviceName = trim($_POST['service_name'] ?? '??');

        if ($adminUser === '' || $adminPass === '' || $adminEmail === '' || $serviceUser === '' || $servicePass === '') {
            $errors[] = '????????????????';
        } else {
            $conn = @new mysqli(
                $dbConfig['host'],
                $dbConfig['user'],
                $dbConfig['pass'],
                $dbConfig['name'],
                (int)$dbConfig['port']
            );

            if ($conn->connect_errno) {
                $errors[] = '????????' . $conn->connect_error;
            } else {
                $conn->set_charset('utf8mb4');
                $adminPasswordHash = password_hash($adminPass, PASSWORD_DEFAULT);
                $servicePasswordHash = password_hash($servicePass, PASSWORD_DEFAULT);

                $stmt = $conn->prepare('INSERT INTO admins (username, password, email) VALUES (?, ?, ?)');
                $stmt->bind_param('sss', $adminUser, $adminPasswordHash, $adminEmail);
                if (!$stmt->execute()) {
                    $errors[] = '????????' . $stmt->error;
                }
                $stmt->close();

                if (empty($errors)) {
                    $stmt = $conn->prepare('INSERT INTO service_agents (username, password, display_name) VALUES (?, ?, ?)');
                    $stmt->bind_param('sss', $serviceUser, $servicePasswordHash, $serviceName);
                    if (!$stmt->execute()) {
                        $errors[] = '?????????' . $stmt->error;
                    }
                    $stmt->close();
                }

                if (empty($errors)) {
                    if (!writeConfig($dbConfig, $adminEmail)) {
                        $errors[] = '???????????? config ?????';
                    } else {
                        file_put_contents($lockFile, 'installed at ' . date('Y-m-d H:i:s'));
                        unset($_SESSION['install_db']);
                        $messages[] = '???????? install ????? install.lock ????????';
                    }
                }

                $conn->close();
            }
        }
    }
}

renderHeader($step);

foreach ($messages as $message) {
    echo '<div class="alert success">' . $message . '</div>';
}

foreach ($errors as $error) {
    echo '<div class="alert error">' . $error . '</div>';
}

if ($step === 1) {
    $checks = checkEnvironment();
    echo '<form><table><thead><tr><th>???</th><th>??</th><th>??</th></tr></thead><tbody>';
    $allPass = true;
    foreach ($checks as $check) {
        $statusText = $check['status'] ? '??' : '???';
        $class = $check['status'] ? 'success' : 'error';
        if (!$check['status']) {
            $allPass = false;
        }
        echo '<tr><td>' . $check['name'] . '</td><td><span class="alert ' . $class . '">' . $statusText . '</span></td><td>' . $check['detail'] . '</td></tr>';
    }
    echo '</tbody></table>';
    if ($allPass) {
        echo '<p style="margin-top:20px;"><a class="btn" href="?step=2">???</a></p>';
    } else {
        echo '<p class="alert error">?????????????????</p>';
    }
    echo '</form>';
}

if ($step === 2) {
    $dbConfig = $_SESSION['install_db'] ?? ['host' => '127.0.0.1', 'port' => '3306', 'user' => '', 'pass' => '', 'name' => 'h5_shop'];
    echo '<form method="post" action="?step=2">';
    echo '<label>?????</label><input type="text" name="db_host" value="' . htmlspecialchars($dbConfig['host']) . '" required>';
    echo '<label>?????</label><input type="text" name="db_port" value="' . htmlspecialchars($dbConfig['port']) . '" required>';
    echo '<label>??????</label><input type="text" name="db_user" value="' . htmlspecialchars($dbConfig['user']) . '" required>';
    echo '<label>?????</label><input type="password" name="db_pass" value="' . htmlspecialchars($dbConfig['pass']) . '">';
    echo '<label>?????</label><input type="text" name="db_name" value="' . htmlspecialchars($dbConfig['name']) . '" required>';
    echo '<div style="display:flex;gap:10px;margin-top:20px;">';
    echo '<button class="btn" type="submit" name="action" value="test">????</button>';
    echo '<button class="btn" type="submit" name="action" value="save">???</button>';
    echo '</div>';
    echo '</form>';
}

if ($step === 3) {
    echo '<form method="post" action="?step=3">';
    echo '<p>????????????????????????</p>';
    echo '<button class="btn" type="submit">????</button>';
    echo '</form>';
}

if ($step === 4) {
    $dbConfig = $_SESSION['install_db'] ?? null;
    if (!$dbConfig) {
        echo '<p class="alert error">???????????????</p>';
        echo '<p><a class="btn" href="?step=2">??</a></p>';
    } else {
        echo '<form method="post" action="?step=4">';
        echo '<h3>?????</h3>';
        echo '<label>??????</label><input type="text" name="admin_user" required>';
        echo '<label>?????</label><input type="password" name="admin_pass" required>';
        echo '<label>?????</label><input type="text" name="admin_email" required>';
        echo '<h3>????</h3>';
        echo '<label>?????</label><input type="text" name="service_user" required>';
        echo '<label>????</label><input type="password" name="service_pass" required>';
        echo '<label>??????</label><input type="text" name="service_name" value="??" required>';
        echo '<button class="btn" type="submit" style="margin-top:20px;">????</button>';
        echo '</form>';

        if (empty($errors) && !empty($messages)) {
            echo '<p style="margin-top:20px;"><a class="btn" href="/index.php">????</a> <a class="btn" href="/admin/login.php">????</a></p>';
        }
    }
}

renderFooter();
