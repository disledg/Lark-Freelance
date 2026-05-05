<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isClient()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];
$success = false;
$error = '';

// Получаем текущую информацию
$result = query("
    SELECT c.*, u.full_name, u.email 
    FROM clients c 
    JOIN users u ON c.user_id = u.id 
    WHERE u.id = $user_id
");
$client = fetch($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company_name = escape($_POST['company_name']);
    $full_name = escape($_POST['full_name']);
    $phone = escape($_POST['phone']);
    $telegram = escape($_POST['telegram']);
    $company_site = escape($_POST['company_site']);
    
    // Обновляем имя пользователя в таблице users
    query("UPDATE users SET full_name = '$full_name' WHERE id = $user_id");
    
    if ($client) {
        // Обновляем существующий профиль
        $sql = "UPDATE clients SET 
                company_name = '$company_name',
                phone = '$phone',
                telegram = '$telegram',
                company_site = '$company_site'
                WHERE user_id = $user_id";
    } else {
        // Создаем новый профиль
        $sql = "INSERT INTO clients (user_id, company_name, phone, telegram, company_site) 
                VALUES ($user_id, '$company_name', '$phone', '$telegram', '$company_site')";
    }
    
    if (query($sql)) {
        $success = true;
        // Обновляем данные для отображения
        $result = query("SELECT * FROM clients WHERE user_id = $user_id");
        $client = fetch($result);
    } else {
        $error = 'Ошибка при сохранении профиля';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Редактирование профиля | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes"></div>
    </div>

    <header class="cyber-header">
        <div class="container">
            <nav class="cyber-nav">
                <a href="/" class="cyber-logo">
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                </a>

                <div class="nav-hologram">
                    <a href="/" class="nav-link">ГЛАВНАЯ</a>
                    <a href="../dashboard.php" class="nav-link">КАБИНЕТ</a>
                    <a href="../projects/index.php" class="nav-link">ПРОЕКТЫ</a>
                    <a href="index.php" class="nav-link">ПРОФИЛЬ</a>
                    <a href="../logout.php" class="nav-link admin-portal">
                        <i class="fas fa-sign-out-alt"></i> ВЫЙТИ
                    </a>
                </div>

                <button class="cyber-menu-btn" id="menuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <section class="cyber-section" style="padding-top: 8rem;">
        <div class="container">
            <div style="max-width: 600px; margin: 0 auto;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <a href="index.php" class="btn-cyber btn-neon" style="padding: 0.5rem 1rem; min-width: auto;">
                        <i class="fas fa-arrow-left"></i> НАЗАД
                    </a>
                    <h1 class="title-gold" style="font-size: 2rem;">
                        <?= $client ? 'РЕДАКТИРОВАТЬ ПРОФИЛЬ' : 'СОЗДАТЬ ПРОФИЛЬ' ?>
                    </h1>
                </div>

                <div class="cyber-card">
                    <?php if ($success): ?>
                        <div style="background: rgba(0,255,0,0.1); border: 1px solid var(--success); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--success);">
                            <i class="fas fa-check-circle"></i> Профиль успешно сохранен!
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div style="background: rgba(255,51,102,0.1); border: 1px solid var(--danger); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--danger);">
                            <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="cyber-form">
                        <div class="input-group">
                            <label class="cyber-label">
                                <span class="label-text">НАЗВАНИЕ КОМПАНИИ *</span>
                                <input type="text" name="company_name" required class="cyber-input"
                                       value="<?= htmlspecialchars($client['company_name'] ?? '') ?>">
                                <div class="input-line"></div>
                            </label>
                        </div>

                        <div class="input-group">
                            <label class="cyber-label">
                                <span class="label-text">ВАШЕ ИМЯ *</span>
                                <input type="text" name="full_name" required class="cyber-input"
                                       value="<?= htmlspecialchars($_SESSION['user_name'] ?? '') ?>">
                                <div class="input-line"></div>
                            </label>
                        </div>

                        <div class="input-group">
                            <label class="cyber-label">
                                <span class="label-text">ТЕЛЕФОН</span>
                                <input type="text" name="phone" class="cyber-input"
                                       value="<?= htmlspecialchars($client['phone'] ?? '') ?>"
                                       placeholder="+7 (999) 123-45-67">
                                <div class="input-line"></div>
                            </label>
                        </div>

                        <div class="input-group">
                            <label class="cyber-label">
                                <span class="label-text">TELEGRAM</span>
                                <input type="text" name="telegram" class="cyber-input"
                                       value="<?= htmlspecialchars($client['telegram'] ?? '') ?>"
                                       placeholder="@username">
                                <div class="input-line"></div>
                            </label>
                        </div>

                        <div class="input-group">
                            <label class="cyber-label">
                                <span class="label-text">САЙТ КОМПАНИИ</span>
                                <input type="url" name="company_site" class="cyber-input"
                                       value="<?= htmlspecialchars($client['company_site'] ?? '') ?>"
                                       placeholder="https://example.com">
                                <div class="input-line"></div>
                            </label>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn-cyber btn-gold" style="min-width: 200px;">
                                <span class="btn-glow"></span>
                                <span class="btn-text">СОХРАНИТЬ</span>
                                <i class="fas fa-save btn-icon"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-bottom">
                <div class="copyright">© <?= date('Y') ?> LARK FREELANCE</div>
            </div>
        </div>
    </footer>

    <script src="../../assets/js/main.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.nav-hologram').classList.toggle('active');
        });
    </script>
</body>
</html>