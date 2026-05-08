<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isDeveloper()) {
    redirect('/developer/login.php');
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    redirect('/developer/dashboard.php');
}

$dev_result = query("SELECT id FROM developers WHERE user_id = $user_id LIMIT 1");
$dev = $dev_result ? fetch($dev_result) : null;
if (!$dev) {
    redirect('/developer/dashboard.php');
}
$developer_id = (int)$dev['id'];

$project_result = query("
    SELECT p.*, c.company_name, u.full_name as client_name
    FROM projects p
    JOIN clients c ON c.id = p.client_id
    JOIN users u ON u.id = c.user_id
    WHERE p.id = $project_id AND p.developer_id = $developer_id
    LIMIT 1
");
$project = $project_result ? fetch($project_result) : null;
if (!$project) {
    redirect('/developer/dashboard.php');
}

$unread_messages = query("
    SELECT COUNT(*) as count
    FROM messages m
    JOIN projects p ON m.project_id = p.id
    WHERE p.developer_id = $developer_id AND m.is_read = 0 AND m.sender_id != $user_id
");
$unread = $unread_messages ? fetch($unread_messages)['count'] : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проект #<?= (int)$project['id'] ?> | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <!-- Анимированный фон -->
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

    <!-- Шапка -->
    <header class="cyber-header">
        <div class="container">
            <nav class="cyber-nav">
                <a href="/" class="cyber-logo">
                    <div class="logo-glow"></div>
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                </a>

                <div class="nav-hologram">
                    <a href="/" class="nav-link">ГЛАВНАЯ</a>
                    <a href="../dashboard.php" class="nav-link">КАБИНЕТ</a>
                    <a href="../profile.php" class="nav-link">ПРОФИЛЬ</a>
                    <a href="../messages/index.php" class="nav-link">
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

    <!-- Основной контент -->
    <section class="cyber-section" style="padding-top: 8rem;">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1 class="title-gold" style="font-size: 2rem;">
                    <i class="fas fa-project-diagram" style="margin-right: 0.5rem;"></i> Проект #<?= (int)$project['id'] ?>
                </h1>
                <div style="display: flex; gap: 1rem;">
                    <a href="../dashboard.php" class="btn-cyber btn-neon" style="padding: 0.5rem 1rem; min-width: auto;">
                        <i class="fas fa-arrow-left" style="margin-right: 0.3rem;"></i> Назад
                    </a>
                    <?php if ($project['developer_id'] == $developer_id): ?>
                        <a href="../messages/index.php?project=<?= (int)$project['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.5rem 1rem; min-width: auto;">
                            <i class="fas fa-envelope" style="margin-right: 0.3rem;"></i> Сообщения
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Информация о проекте -->
            <div class="cyber-card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; font-size: 1.3rem;">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i> Детали проекта
                    </h3>
                    <span style="background: <?= $project['status'] == 'new' ? 'rgba(0,243,255,0.1)' : ($project['status'] == 'in_progress' ? 'rgba(255,215,0,0.1)' : 'rgba(0,255,0,0.1)') ?>; 
                          color: <?= $project['status'] == 'new' ? 'var(--cyber-blue)' : ($project['status'] == 'in_progress' ? 'var(--primary-gold)' : 'var(--success)') ?>; 
                          padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; border: 1px solid <?= $project['status'] == 'new' ? 'var(--cyber-blue)' : ($project['status'] == 'in_progress' ? 'var(--primary-gold)' : 'var(--success)') ?>;">
                        <i class="fas fa-circle" style="margin-right: 0.3rem;"></i>
                        <?= $project['status'] == 'new' ? 'Новый' : ($project['status'] == 'in_progress' ? 'В работе' : ($project['status'] == 'completed' ? 'Завершен' : $project['status'])) ?>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Название проекта</div>
                        <div style="color: var(--text-light); font-weight: 500; font-size: 1.1rem;"><?= htmlspecialchars($project['title']) ?></div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Заказчик</div>
                        <div style="color: var(--text-light); font-weight: 500;">
                            <i class="fas fa-building" style="margin-right: 0.3rem; color: var(--primary-gold);"></i>
                            <?= htmlspecialchars($project['company_name']) ?>
                        </div>
                        <div style="color: var(--text-gray); font-size: 0.8rem; margin-top: 0.3rem;">
                            <i class="fas fa-user" style="margin-right: 0.3rem;"></i>
                            <?= htmlspecialchars($project['client_name']) ?>
                        </div>
                    </div>
                    <?php if ($project['budget']): ?>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Бюджет</div>
                        <div style="color: var(--cyber-blue); font-weight: 500; font-size: 1.1rem;">
                            <i class="fas fa-ruble-sign" style="margin-right: 0.3rem;"></i>
                            <?= number_format((float)$project['budget'], 0, ',', ' ') ?> ₽
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($project['deadline']): ?>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Дедлайн</div>
                        <div style="color: var(--text-light); font-weight: 500;">
                            <i class="fas fa-calendar-alt" style="margin-right: 0.3rem; color: var(--primary-gold);"></i>
                            <?= date('d.m.Y', strtotime($project['deadline'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 4px; margin-bottom: 1rem;">
                    <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-align-left" style="margin-right: 0.3rem;"></i> Описание проекта
                    </div>
                    <div style="color: var(--text-light); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </div>
                </div>

                <?php if (!empty($project['requirements'])): ?>
                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 4px;">
                    <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-list-check" style="margin-right: 0.3rem;"></i> Требования
                    </div>
                    <div style="color: var(--text-light); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($project['requirements'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; margin-top: 1.5rem; color: var(--text-gray); font-size: 0.85rem;">
                    <i class="fas fa-calendar" style="margin-right: 0.3rem;"></i> Создан: <?= date('d.m.Y H:i', strtotime($project['created_at'])) ?>
                    <?php if ($project['updated_at'] != $project['created_at']): ?>
                        | <i class="fas fa-edit" style="margin-right: 0.3rem;"></i> Обновлен: <?= date('d.m.Y H:i', strtotime($project['updated_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Модальное окно для подачи заявки -->
    <div id="applyModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; padding: 2rem; box-sizing: border-box;">
    <!-- Футер -->
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

