<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($user_id <= 0) {
    redirect('../dashboard.php');
}

$result = query("
    SELECT c.*, u.full_name, u.email, u.status
    FROM clients c
    JOIN users u ON c.user_id = u.id
    WHERE u.id = $user_id
    LIMIT 1
");
$client = $result ? fetch($result) : null;
if (!$client) {
    redirect('../requests/clients.php');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Клиент #<?= $user_id ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dark-theme">
    <div class="container" style="max-width: 900px; margin: 40px auto;">
        <div class="cyber-card" style="padding: 24px;">
            <h2>Профиль клиента</h2>
            <p><strong>Компания:</strong> <?= htmlspecialchars($client['company_name'] ?? '') ?></p>
            <p><strong>Контакт:</strong> <?= htmlspecialchars($client['full_name'] ?? '') ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($client['email'] ?? '') ?></p>
            <p><strong>Телефон:</strong> <?= htmlspecialchars($client['phone'] ?? '') ?></p>
            <p><strong>Telegram:</strong> <?= htmlspecialchars($client['telegram'] ?? '') ?></p>
            <p><strong>Сайт:</strong> <?= htmlspecialchars($client['company_site'] ?? '') ?></p>
            <p><strong>Статус:</strong> <?= htmlspecialchars($client['status'] ?? '') ?></p>
            <p><a class="btn-cyber btn-neon" href="../dashboard.php">Назад</a></p>
        </div>
    </div>
</body>
</html>

