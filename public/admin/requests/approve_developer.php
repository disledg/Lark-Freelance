<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

$id = (int)$_GET['id'];

// Получаем данные заявки
$result = $db->query("SELECT * FROM developer_applications WHERE id = $id");
$app = $result->fetch_assoc();

if (!$app) {
    redirect('developers.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = $db->escape($_POST['comment']);
    
    // Создаем пользователя
    $password = password_hash(substr(md5(time()), 0, 8), PASSWORD_DEFAULT);
    $username = strtolower(explode('@', $app['email'])[0]);
    
    $sql = "INSERT INTO users (username, email, password, full_name, role, status) 
            VALUES ('$username', '{$app['email']}', '$password', '{$app['full_name']}', 'developer', 'approved')";
    $db->query($sql);
    $user_id = $db->insert_id();
    
    // Создаем профиль разработчика
    $sql = "INSERT INTO developers (user_id, level, skills, experience, portfolio, telegram, github) 
            VALUES ($user_id, '{$app['level']}', '{$app['skills']}', '{$app['experience']}', '{$app['portfolio']}', '{$app['telegram']}', '{$app['github']}')";
    $db->query($sql);
    
    // Обновляем статус заявки
    $db->query("UPDATE developer_applications SET status = 'approved', manager_comment = '$comment' WHERE id = $id");
    
    // Отправляем уведомление
    addNotification($user_id, 'Заявка одобрена', 'Ваша заявка одобрена. Войдите в систему с временным паролем: ' . $password, '/developer/login.php');
    
    addLog($_SESSION['user_id'], 'approve_developer', "Approved developer application #$id");
    
    redirect('developers.php?success=1');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Одобрение заявки</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="dark-theme">
    <div class="container" style="max-width: 600px; margin-top: 100px;">
        <div class="cyber-card">
            <h2>Одобрение заявки #<?= $id ?></h2>
            
            <div style="margin: 2rem 0; padding: 1rem; background: rgba(255,215,0,0.1);">
                <p><strong><?= htmlspecialchars($app['full_name']) ?></strong></p>
                <p>Email: <?= htmlspecialchars($app['email']) ?></p>
                <p>Уровень: <?= $app['level'] ?></p>
            </div>
            
            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <textarea name="comment" placeholder="Комментарий менеджера" 
                              style="width: 100%; padding: 0.8rem; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,215,0,0.3); color: white;" rows="3"></textarea>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn-cyber btn-gold">Подтвердить</button>
                    <a href="developers.php" class="btn-cyber btn-neon">Отмена</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>