<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

// Получаем все проекты
$projects = query("
    SELECT p.*, 
           c.company_name,
           c.id as client_id,
           d.id as developer_id,
           u.full_name as developer_name,
           (SELECT COUNT(*) FROM messages WHERE project_id = p.id AND is_read = 0) as new_messages
    FROM projects p 
    JOIN clients c ON p.client_id = c.id 
    LEFT JOIN developers d ON p.developer_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    ORDER BY 
        CASE p.status 
            WHEN 'new' THEN 1
            WHEN 'in_progress' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
            ELSE 5
        END,
        p.created_at DESC
");

// Статистика
$stats = [
    'total' => 0,
    'new' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'cancelled' => 0
];

if ($projects) {
    $stats['total'] = mysqli_num_rows($projects);
    mysqli_data_seek($projects, 0);
    while ($p = mysqli_fetch_assoc($projects)) {
        if (isset($stats[$p['status']])) {
            $stats[$p['status']]++;
        }
    }
    mysqli_data_seek($projects, 0);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление проектами | Lark Freelance</title>
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
                    <a href="../dashboard.php" class="nav-link">ДАШБОРД</a>
                    <a href="index.php" class="nav-link active">ПРОЕКТЫ</a>
                    <a href="../requests/developers.php" class="nav-link">ЗАЯВКИ</a>
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
            <h1 class="title-gold" style="font-size: 2rem; margin-bottom: 2rem;">УПРАВЛЕНИЕ ПРОЕКТАМИ</h1>
            
            <!-- Статистика -->
            <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--text-light);"><?= $stats['total'] ?></div>
                    <div style="color: var(--text-gray);">Всего</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--cyber-blue);"><?= $stats['new'] ?></div>
                    <div style="color: var(--text-gray);">Новые</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--primary-gold);"><?= $stats['in_progress'] ?></div>
                    <div style="color: var(--text-gray);">В работе</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--success);"><?= $stats['completed'] ?></div>
                    <div style="color: var(--text-gray);">Завершено</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--danger);"><?= $stats['cancelled'] ?></div>
                    <div style="color: var(--text-gray);">Отменено</div>
                </div>
            </div>

            <!-- Таблица проектов -->
            <div class="cyber-card" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--primary-gold);">
                            <th style="padding: 1rem; text-align: left;">ID</th>
                            <th style="padding: 1rem; text-align: left;">Проект</th>
                            <th style="padding: 1rem; text-align: left;">Клиент</th>
                            <th style="padding: 1rem; text-align: left;">Разработчик</th>
                            <th style="padding: 1rem; text-align: left;">Бюджет</th>
                            <th style="padding: 1rem; text-align: left;">Статус</th>
                            <th style="padding: 1rem; text-align: left;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                            <?php while ($project = mysqli_fetch_assoc($projects)): 
                                $status_colors = [
                                    'new' => 'var(--cyber-blue)',
                                    'in_progress' => 'var(--primary-gold)',
                                    'completed' => 'var(--success)',
                                    'cancelled' => 'var(--danger)'
                                ];
                                $status_color = $status_colors[$project['status']] ?? 'var(--text-gray)';
                            ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <td style="padding: 1rem;">#<?= $project['id'] ?></td>
                                <td style="padding: 1rem;">
                                    <strong style="color: var(--text-light);"><?= htmlspecialchars($project['title']) ?></strong>
                                    <?php if ($project['new_messages'] > 0): ?>
                                        <span style="background: var(--primary-gold); color: var(--dark-bg); padding: 0.2rem 0.5rem; border-radius: 10px; font-size: 0.7rem; margin-left: 0.5rem;">
                                            <?= $project['new_messages'] ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 1rem;"><?= htmlspecialchars($project['company_name'] ?: 'Не указан') ?></td>
                                <td style="padding: 1rem;">
                                    <?= $project['developer_name'] ? htmlspecialchars($project['developer_name']) : '<span style="color: var(--text-gray);">Не назначен</span>' ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <?= $project['budget'] ? number_format($project['budget'], 0, ',', ' ') . ' ₽' : '—' ?>
                                </td>
                                <td style="padding: 1rem;">
                                    <span style="background: <?= $status_color ?>20; color: <?= $status_color ?>; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.8rem;">
                                        <?= $project['status'] == 'new' ? 'Новый' : 
                                            ($project['status'] == 'in_progress' ? 'В работе' : 
                                            ($project['status'] == 'completed' ? 'Завершен' : 
                                            ($project['status'] == 'cancelled' ? 'Отменен' : $project['status']))) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;">
                                    <a href="view.php?id=<?= $project['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="assign.php?id=<?= $project['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                        <i class="fas fa-user-plus"></i>
                                    </a>
                                    <a href="status.php?id=<?= $project['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                        <i class="fas fa-tag"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-gray);">
                                    <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                    <p>Нет проектов</p>
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

    <script src="../../assets/js/main.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.nav-hologram').classList.toggle('active');
        });
    </script>
</body>
</html>