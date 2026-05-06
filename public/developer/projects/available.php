<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isDeveloper()) {
    redirect('/developer/login.php');
}

$user_id = (int)$_SESSION['user_id'];
$result = query("SELECT id FROM developers WHERE user_id = $user_id LIMIT 1");
$developer = $result ? fetch($result) : null;
if (!$developer) {
    redirect('/developer/dashboard.php');
}
$developer_id = (int)$developer['id'];

$projects = query("
    SELECT p.*, c.company_name
    FROM projects p
    JOIN clients c ON c.id = p.client_id
    WHERE p.developer_id IS NULL AND p.status = 'new'
    ORDER BY p.created_at DESC
");

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
    <title>Доступные проекты | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
    </div>

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
                    <a href="available.php" class="nav-link active">ДОСТУПНЫЕ</a>
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

    <section class="cyber-section" style="padding-top: 8rem;">
        <div class="container">
            <h1 class="title-gold" style="font-size: 2rem; margin-bottom: 2rem;">
                <i class="fas fa-list" style="margin-right: 0.5rem;"></i> Доступные проекты
            </h1>

            <div class="cyber-card" style="padding: 24px;">
                <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                    <div style="display: grid; gap: 1.5rem;">
                        <?php while ($p = fetch($projects)): ?>
                            <div class="cyber-card" style="padding: 1.5rem; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,215,0,0.2); transition: all 0.3s;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div style="flex: 1;">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                            <span style="color: var(--cyber-blue); font-weight: bold; font-size: 1.1rem;">#<?= (int)$p['id'] ?></span>
                                            <h3 style="color: var(--text-light); font-size: 1.2rem; margin: 0;"><?= htmlspecialchars($p['title']) ?></h3>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 1rem; color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.5rem;">
                                            <span><i class="fas fa-building" style="margin-right: 0.3rem;"></i> <?= htmlspecialchars($p['company_name']) ?></span>
                                            <?php if ($p['budget']): ?>
                                                <span><i class="fas fa-ruble-sign" style="margin-right: 0.3rem;"></i> <?= number_format((float)$p['budget'], 0, ',', ' ') ?> ₽</span>
                                            <?php endif; ?>
                                        </div>
                                        <p style="color: var(--text-gray); margin: 0; line-height: 1.4;">
                                            <?= htmlspecialchars(mb_substr($p['description'], 0, 200)) ?><?= strlen($p['description']) > 200 ? '...' : '' ?>
                                        </p>
                                    </div>
                                    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: flex-end;">
                                        <span style="background: rgba(0,243,255,0.1); color: var(--cyber-blue); padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; border: 1px solid var(--cyber-blue);">
                                            <i class="fas fa-clock" style="margin-right: 0.3rem;"></i> Новый
                                        </span>
                                        <a href="view.php?id=<?= (int)$p['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.5rem 1rem; min-width: auto; font-size: 0.9rem;">
                                            <i class="fas fa-eye" style="margin-right: 0.3rem;"></i> Открыть
                                        </a>
                                    </div>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 0.85rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 0.75rem;">
                                    <span><i class="fas fa-calendar" style="margin-right: 0.3rem;"></i> Создан: <?= date('d.m.Y', strtotime($p['created_at'])) ?></span>
                                    <?php if ($p['deadline']): ?>
                                        <span><i class="fas fa-hourglass-half" style="margin-right: 0.3rem;"></i> Дедлайн: <?= date('d.m.Y', strtotime($p['deadline'])) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; color: var(--text-gray);">
                        <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3 style="color: var(--text-light); margin-bottom: 0.5rem;">Нет доступных проектов</h3>
                        <p>Новые проекты появятся здесь, как только заказчики их разместят.</p>
                    </div>
                <?php endif; ?>
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

