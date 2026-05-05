<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Экранируем вручную
    global $connection;
    $email = mysqli_real_escape_string($connection, $email);
    
    $result = query("SELECT * FROM users WHERE email = '$email' AND role = 'client'");
    
    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        
        if (password_verify($password, $user['password'])) {
            if ($user['status'] === 'approved') {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = 'client';
                $_SESSION['user_name'] = $user['full_name'];
                
                query("UPDATE users SET last_login = NOW() WHERE id = {$user['id']}");
                addLog($user['id'], 'login', 'Client logged in');
                
                $redirect = isset($_SESSION['redirect_after_login']) ? $_SESSION['redirect_after_login'] : '/client/dashboard.php';
                unset($_SESSION['redirect_after_login']);
                redirect($redirect);
            } else {
                $error = 'Ваш аккаунт еще не одобрен менеджером';
            }
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
    <title>Вход для заказчиков | Lark Freelance</title>
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
            
            <h2 class="form-title" style="font-size: 1.5rem;">Вход для заказчиков</h2>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid var(--danger); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 1.5rem;">
                    <label class="cyber-label">
                        <span class="label-text">EMAIL</span>
                        <input type="email" name="email" required class="cyber-input" 
                               placeholder="client@test.ru">
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
                <a href="register.php" style="color: var(--cyber-blue);">Нет аккаунта? Зарегистрироваться</a>
            </div>
        </div>
    </div>
</body>
</html>