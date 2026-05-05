<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Экранируем вручную, так как функции escape нет
    global $connection;
    $username = mysqli_real_escape_string($connection, $username);
    
    $result = query("SELECT * FROM users WHERE (username = '$username' OR email = '$username') AND role IN ('admin', 'manager')");
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['full_name'];
            
            //query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
            addLog($user['id'], 'login', 'Manager logged in');
            
            redirect('dashboard.php');
        } else {
            $error = 'Неверный пароль';
        }
    } else {
        $error = 'Пользователь не найден';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход для менеджеров | Lark Freelance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes"></div>
    </div>

    <div class="container" style="max-width: 400px; margin-top: 150px;">
        <div class="cyber-card" style="padding: 2rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="/" class="cyber-logo" style="display: inline-block;">
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                </a>
            </div>
            
            <h2 class="form-title" style="font-size: 1.5rem;">Вход для менеджеров</h2>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid var(--danger); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label class="cyber-label">
                        <span class="label-text">ЛОГИН ИЛИ EMAIL</span>
                        <input type="text" name="username" required class="cyber-input" 
                               placeholder="admin@lark.ru">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <label class="cyber-label">
                        <span class="label-text">ПАРОЛЬ</span>
                        <input type="password" name="password" required class="cyber-input">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <button type="submit" class="btn-cyber btn-gold" style="width: 100%;">
                    <span class="btn-glow"></span>
                    <span class="btn-text">ВОЙТИ</span>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="/" class="nav-link">← На главную</a>
            </div>
        </div>
    </div>
</body>
</html>