<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isClient()) {
    redirect('../login.php');
}

$user_id = $_SESSION['user_id'];

// Получаем информацию о клиенте
$result = query("
    SELECT c.*, u.full_name, u.email, u.created_at 
    FROM clients c 
    JOIN users u ON c.user_id = u.id 
    WHERE u.id = $user_id
");
$client = fetch($result);

if (!$client) {
    // Если профиля нет, перенаправляем на создание
    redirect('edit.php');
}

$client_id = $client['id'];

// Получаем непрочитанные сообщения
$unread_messages = query("
    SELECT COUNT(*) as count 
    FROM messages m
    JOIN projects p ON m.project_id = p.id
    WHERE p.client_id = $client_id AND m.is_read = 0 AND m.sender_id != $user_id
");
$unread = $unread_messages ? fetch($unread_messages)['count'] : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль | Lark Freelance</title>
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
                    <a href="../projects/index.php" class="nav-link">МОИ ПРОЕКТЫ</a>
                    <a href="index.php" class="nav-link active">ПРОФИЛЬ</a>
                    <a href="../messages.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread > 0): ?>
                            <span style="margin-left: 0.35rem; color: var(--primary-gold);"><?= (int)$unread ?></span>
                        <?php endif; ?>
                    </a>
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
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                    <h1 class="title-gold" style="font-size: 2rem;">ПРОФИЛЬ</h1>
                    <a href="edit.php" class="btn-cyber btn-gold" style="padding: 0.5rem 1rem; min-width: auto;">
                        <i class="fas fa-edit"></i> РЕДАКТИРОВАТЬ
                    </a>
                </div>

                <div class="cyber-card">
                    <div style="text-align: center; margin-bottom: 2rem;">
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(45deg, var(--primary-gold), var(--cyber-blue)); margin: 0 auto 1rem;"></div>
                        <h2 style="color: var(--text-light);"><?= htmlspecialchars($client['full_name']) ?></h2>
                        <p style="color: var(--primary-gold);"><?= htmlspecialchars($client['company_name']) ?></p>
                    </div>

                    <div style="border-top: 1px solid rgba(255,215,0,0.3); padding-top: 1.5rem;">
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-gray); font-size: 0.9rem;">Email</div>
                            <div style="color: var(--text-light);"><?= htmlspecialchars($client['email']) ?></div>
                        </div>

                        <?php if ($client['phone']): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-gray); font-size: 0.9rem;">Телефон</div>
                            <div style="color: var(--text-light);"><?= htmlspecialchars($client['phone']) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($client['telegram']): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-gray); font-size: 0.9rem;">Telegram</div>
                            <div style="color: var(--text-light);"><?= htmlspecialchars($client['telegram']) ?></div>
                        </div>
                        <?php endif; ?>

                        <?php if ($client['company_site']): ?>
                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-gray); font-size: 0.9rem;">Сайт компании</div>
                            <div style="color: var(--text-light);">
                                <a href="<?= htmlspecialchars($client['company_site']) ?>" target="_blank" style="color: var(--cyber-blue);">
                                    <?= htmlspecialchars($client['company_site']) ?>
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div style="margin-bottom: 1rem;">
                            <div style="color: var(--text-gray); font-size: 0.9rem;">На платформе с</div>
                            <div style="color: var(--text-light);"><?= date('d.m.Y', strtotime($client['created_at'])) ?></div>
                        </div>
                    </div>
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