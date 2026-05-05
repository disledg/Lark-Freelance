<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('login.php');
}

// Получаем статистику
$new_dev_apps = 0;
$result = query("SELECT COUNT(*) as count FROM developer_applications WHERE status = 'new'");
if ($result) {
    $row = fetch($result);
    $new_dev_apps = $row['count'];
}

$new_client_apps = 0;
$result = query("SELECT COUNT(*) as count FROM client_applications WHERE status = 'new'");
if ($result) {
    $row = fetch($result);
    $new_client_apps = $row['count'];
}

$active_projects = 0;
$result = query("SELECT COUNT(*) as count FROM projects WHERE status = 'in_progress'");
if ($result) {
    $row = fetch($result);
    $active_projects = $row['count'];
}

$total_users = 0;
$result = query("SELECT COUNT(*) as count FROM users WHERE role IN ('developer', 'client')");
if ($result) {
    $row = fetch($result);
    $total_users = $row['count'];
}

// Последние заявки
$recent_apps = query("
    SELECT 'developer' as type, id, full_name as name, created_at, status 
    FROM developer_applications 
    UNION ALL 
    SELECT 'client' as type, id, company_name as name, created_at, status 
    FROM client_applications 
    ORDER BY created_at DESC LIMIT 10
");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель менеджера | Lark Freelance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <a href="dashboard.php" class="nav-link active">ДАШБОРД</a>
                    <a href="projects/index.php" class="nav-link">ПРОЕКТЫ</a>
                    <a href="requests/developers.php" class="nav-link">ЗАЯВКИ</a>
                    <a href="logout.php" class="nav-link admin-portal">
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
            <h1 class="title-gold" style="font-size: 2rem; margin-bottom: 2rem;">ПАНЕЛЬ УПРАВЛЕНИЯ</h1>
            
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--cyber-blue); font-weight: bold;"><?= $new_dev_apps ?></div>
                    <div style="color: var(--text-gray);">Новых разработчиков</div>
                    <a href="requests/developers.php" style="color: var(--primary-gold);">Просмотр →</a>
                </div>
                
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--cyber-blue); font-weight: bold;"><?= $new_client_apps ?></div>
                    <div style="color: var(--text-gray);">Новых клиентов</div>
                    <a href="requests/clients.php" style="color: var(--primary-gold);">Просмотр →</a>
                </div>
                
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary-gold); font-weight: bold;"><?= $active_projects ?></div>
                    <div style="color: var(--text-gray);">Активных проектов</div>
                    <a href="projects/index.php" style="color: var(--primary-gold);">Просмотр →</a>
                </div>
                
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--success); font-weight: bold;"><?= $total_users ?></div>
                    <div style="color: var(--text-gray);">Всего пользователей</div>
                </div>
            </div>

            <h2 style="color: var(--primary-gold); margin: 2rem 0 1rem;">Последние заявки</h2>
            
            <div class="cyber-card">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid rgba(255,215,0,0.3);">
                            <th style="padding: 1rem; text-align: left;">Тип</th>
                            <th style="padding: 1rem; text-align: left;">Название</th>
                            <th style="padding: 1rem; text-align: left;">Дата</th>
                            <th style="padding: 1rem; text-align: left;">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_apps && mysqli_num_rows($recent_apps) > 0): ?>
                            <?php while ($app = fetch($recent_apps)): ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <td style="padding: 1rem;">
                                    <?= $app['type'] === 'developer' ? '👨‍💻 Разработчик' : '🏢 Клиент' ?>
                                </td>
                                <td style="padding: 1rem;"><?= htmlspecialchars($app['name']) ?></td>
                                <td style="padding: 1rem;"><?= date('d.m.Y H:i', strtotime($app['created_at'])) ?></td>
                                <td style="padding: 1rem;">
                                    <span style="background: <?= $app['status'] == 'new' ? 'var(--cyber-blue)' : ($app['status'] == 'approved' ? 'var(--success)' : 'var(--danger)') ?>20; 
                                                       color: <?= $app['status'] == 'new' ? 'var(--cyber-blue)' : ($app['status'] == 'approved' ? 'var(--success)' : 'var(--danger)') ?>;
                                                       padding: 0.3rem 1rem; border-radius: 20px;">
                                        <?= $app['status'] ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="padding: 2rem; text-align: center; color: var(--text-gray);">
                                    Нет заявок
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    <script src="../assets/js/main.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.nav-hologram').classList.toggle('active');
        });
    </script>
</body>
</html>