<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

// Получаем заявки от разработчиков
$applications = query("
    SELECT * FROM developer_applications 
    ORDER BY 
        CASE status 
            WHEN 'new' THEN 1
            WHEN 'reviewed' THEN 2
            WHEN 'approved' THEN 3
            WHEN 'rejected' THEN 4
            ELSE 5
        END,
        created_at DESC
");

// Статистика
$stats = [
    'new' => 0,
    'reviewed' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0
];

if ($applications) {
    $stats['total'] = mysqli_num_rows($applications);
    mysqli_data_seek($applications, 0);
    while ($app = mysqli_fetch_assoc($applications)) {
        if (isset($stats[$app['status']])) {
            $stats[$app['status']]++;
        }
    }
    mysqli_data_seek($applications, 0);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заявки разработчиков | Lark Freelance</title>
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
                    <a href="../projects/index.php" class="nav-link">ПРОЕКТЫ</a>
                    <a href="developers.php" class="nav-link active">ЗАЯВКИ</a>
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
            <h1 class="title-gold" style="font-size: 2rem; margin-bottom: 2rem;">ЗАЯВКИ РАЗРАБОТЧИКОВ</h1>
            
            <!-- Статистика -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--cyber-blue);"><?= $stats['new'] ?></div>
                    <div style="color: var(--text-gray);">Новые</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--primary-gold);"><?= $stats['reviewed'] ?></div>
                    <div style="color: var(--text-gray);">В работе</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--success);"><?= $stats['approved'] ?></div>
                    <div style="color: var(--text-gray);">Одобрено</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--danger);"><?= $stats['rejected'] ?></div>
                    <div style="color: var(--text-gray);">Отклонено</div>
                </div>
            </div>

            <!-- Таблица заявок -->
            <div class="cyber-card" style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; min-width: 1000px;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--primary-gold);">
                            <th style="padding: 1rem; text-align: left;">ID</th>
                            <th style="padding: 1rem; text-align: left;">ФИО</th>
                            <th style="padding: 1rem; text-align: left;">Email</th>
                            <th style="padding: 1rem; text-align: left;">Уровень</th>
                            <th style="padding: 1rem; text-align: left;">Дата</th>
                            <th style="padding: 1rem; text-align: left;">Статус</th>
                            <th style="padding: 1rem; text-align: left;">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($applications && mysqli_num_rows($applications) > 0): ?>
                            <?php while ($app = mysqli_fetch_assoc($applications)): 
                                $status_colors = [
                                    'new' => 'var(--cyber-blue)',
                                    'reviewed' => 'var(--primary-gold)',
                                    'approved' => 'var(--success)',
                                    'rejected' => 'var(--danger)'
                                ];
                                $status_color = $status_colors[$app['status']] ?? 'var(--text-gray)';
                                $status_text = [
                                    'new' => 'Новая',
                                    'reviewed' => 'Рассмотрена',
                                    'approved' => 'Одобрена',
                                    'rejected' => 'Отклонена'
                                ];
                            ?>
                            <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                <td style="padding: 1rem;">#<?= $app['id'] ?></td>
                                <td style="padding: 1rem;">
                                    <strong style="color: var(--text-light);"><?= htmlspecialchars($app['full_name']) ?></strong>
                                </td>
                                <td style="padding: 1rem;"><?= htmlspecialchars($app['email']) ?></td>
                                <td style="padding: 1rem;">
                                    <span style="color: <?= $app['level'] == 'junior' ? 'var(--cyber-blue)' : 'var(--primary-gold)' ?>;">
                                        <?= strtoupper($app['level']) ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;"><?= date('d.m.Y', strtotime($app['created_at'])) ?></td>
                                <td style="padding: 1rem;">
                                    <span style="background: <?= $status_color ?>20; color: <?= $status_color ?>; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.8rem;">
                                        <?= $status_text[$app['status']] ?? $app['status'] ?>
                                    </span>
                                </td>
                                <td style="padding: 1rem;">
                                    <a href="view_developer.php?id=<?= $app['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> Просмотр
                                    </a>
                                    
                                    <?php if ($app['status'] === 'new'): ?>
                                        <a href="approve_developer.php?id=<?= $app['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                            <i class="fas fa-check"></i> Одобрить
                                        </a>
                                        <a href="reject_developer.php?id=<?= $app['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem; background: rgba(255,51,102,0.1); color: var(--danger);">
                                            <i class="fas fa-times"></i> Отклонить
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="padding: 3rem; text-align: center; color: var(--text-gray);">
                                    <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                    <p>Нет заявок от разработчиков</p>
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