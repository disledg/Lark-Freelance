<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = escape($_POST['full_name']);
    $email = escape($_POST['email']);
    $company_name = escape($_POST['company_name']);
    $phone = escape($_POST['phone']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Проверяем, нет ли уже такого email
    $check = query("SELECT id FROM users WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
        $error = 'Пользователь с таким email уже существует';
    } else {
        // Создаем пользователя
        $username = strtolower(explode('@', $email)[0]);
        $sql = "INSERT INTO users (username, email, password, full_name, role, status) 
                VALUES ('$username', '$email', '$password', '$full_name', 'client', 'pending')";
        
        if (query($sql)) {
            $user_id = insert_id();
            
            // Создаем профиль клиента
            query("INSERT INTO clients (user_id, company_name, phone) VALUES ($user_id, '$company_name', '$phone')");
            
            // Уведомление менеджерам
            $managers = query("SELECT id FROM users WHERE role = 'manager'");
            while ($manager = fetch($managers)) {
                addNotification(
                    $manager['id'],
                    'Новый клиент',
                    "Зарегистрировался новый клиент: $company_name",
                    "/admin/clients/view.php?id=$user_id"
                );
            }
            
            $_SESSION['register_success'] = true;
            redirect('login.php?registered=1');
        } else {
            $error = 'Ошибка при регистрации';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация заказчика | Lark Freelance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes"></div>
    </div>

    <div class="container" style="max-width: 500px; margin-top: 100px;">
        <div class="cyber-card" style="padding: 2rem;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <a href="/" class="cyber-logo" style="display: inline-block;">
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                </a>
            </div>
            
            <h2 class="form-title" style="font-size: 1.5rem;">Регистрация заказчика</h2>
            
            <?php if (isset($error)): ?>
                <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid var(--danger); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <label class="cyber-label">
                        <span class="label-text">КОМПАНИЯ *</span>
                        <input type="text" name="company_name" required class="cyber-input">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="cyber-label">
                        <span class="label-text">КОНТАКТНОЕ ЛИЦО *</span>
                        <input type="text" name="full_name" required class="cyber-input">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="cyber-label">
                        <span class="label-text">EMAIL *</span>
                        <input type="email" name="email" required class="cyber-input">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label class="cyber-label">
                        <span class="label-text">ТЕЛЕФОН</span>
                        <input type="tel" name="phone" class="cyber-input">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label class="cyber-label">
                        <span class="label-text">ПАРОЛЬ *</span>
                        <input type="password" name="password" required class="cyber-input">
                        <div class="input-line"></div>
                    </label>
                </div>
                
                <button type="submit" class="btn-cyber btn-gold" style="width: 100%;">
                    <span class="btn-glow"></span>
                    <span class="btn-text">ЗАРЕГИСТРИРОВАТЬСЯ</span>
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--cyber-blue);">Уже есть аккаунт? Войти</a>
            </div>
        </div>
    </div>
</body>
</html>