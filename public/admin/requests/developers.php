<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

$modal = isset($_GET['modal']) ? $_GET['modal'] : '';
$selected_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$selected_app = null;

if ($selected_id > 0) {
    $selected_result = query("SELECT * FROM developer_applications WHERE id = $selected_id");
    $selected_app = $selected_result ? fetch($selected_result) : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        if ($_POST['action'] === 'approve_developer') {
            $comment = isset($_POST['comment']) ? escape($_POST['comment']) : '';
            $result = query("SELECT * FROM developer_applications WHERE id = $id");
            $app = $result ? fetch($result) : null;
            if ($app && $app['status'] === 'new') {
                $password = password_hash(substr(md5((string)time()), 0, 8), PASSWORD_DEFAULT);
                $username = strtolower(explode('@', $app['email'])[0]);
                $username = preg_replace('/[^a-z0-9_]/', '_', $username);
                $safe_username = escape($username);
                $safe_email = escape($app['email']);
                $safe_full_name = escape($app['full_name']);
                $safe_level = escape($app['level']);
                $safe_skills = escape($app['skills']);
                $safe_experience = escape($app['experience']);
                $safe_portfolio = escape((string)$app['portfolio']);
                $safe_telegram = escape((string)$app['telegram']);
                $safe_github = escape((string)$app['github']);

                $user_insert = query("INSERT INTO users (username, email, password, full_name, role, status) VALUES ('$safe_username', '$safe_email', '$password', '$safe_full_name', 'developer', 'approved')");
                if ($user_insert) {
                    $user_id = insert_id();
                    query("INSERT INTO developers (user_id, level, skills, experience, portfolio, telegram, github) VALUES ($user_id, '$safe_level', '$safe_skills', '$safe_experience', '$safe_portfolio', '$safe_telegram', '$safe_github')");
                    query("UPDATE developer_applications SET status = 'approved', manager_comment = '$comment' WHERE id = $id");
                    addNotification($user_id, 'Заявка одобрена', 'Ваша заявка одобрена. Войдите в систему.', '/developer/login.php');
                    addLog($_SESSION['user_id'] ?? null, 'approve_developer', "Approved developer application #$id");
                }
            }
            redirect('developers.php');
        }

        if ($_POST['action'] === 'reject_developer') {
            $comment = isset($_POST['comment']) ? escape($_POST['comment']) : '';
            query("UPDATE developer_applications SET status = 'rejected', manager_comment = '$comment' WHERE id = $id");
            addLog($_SESSION['user_id'] ?? null, 'reject_developer', "Rejected developer application #$id");
            redirect('developers.php');
        }
    }
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
    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            backdrop-filter: blur(8px);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal-card {
            width: min(860px, 95vw);
            max-height: 88vh;
            overflow: auto;
            padding: 1.25rem;
        }
    </style>
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
                                    <a href="developers.php?modal=view&id=<?= $app['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                        <i class="fas fa-eye"></i> Просмотр
                                    </a>
                                    
                                    <?php if ($app['status'] === 'new'): ?>
                                        <a href="developers.php?modal=approve&id=<?= $app['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                            <i class="fas fa-check"></i> Одобрить
                                        </a>
                                        <a href="developers.php?modal=reject&id=<?= $app['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem; background: rgba(255,51,102,0.1); color: var(--danger);">
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

    <?php if ($selected_app && in_array($modal, ['view', 'approve', 'reject'], true)): ?>
    <div class="modal-overlay" onclick="if (event.target === this) window.location.href='developers.php';">
        <div class="cyber-card modal-card">
            <div style="display:flex; justify-content: space-between; align-items:center; margin-bottom:1rem;">
                <h3 style="color: var(--primary-gold); margin: 0;">Заявка разработчика #<?= (int)$selected_app['id'] ?></h3>
                <a href="developers.php" class="btn-cyber btn-neon" style="padding:0.3rem 0.8rem; min-width:auto;">Закрыть</a>
            </div>

            <p><strong>ФИО:</strong> <?= htmlspecialchars($selected_app['full_name']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($selected_app['email']) ?></p>
            <p><strong>Уровень:</strong> <?= htmlspecialchars($selected_app['level']) ?></p>
            <p><strong>Навыки:</strong><br><?= nl2br(htmlspecialchars($selected_app['skills'])) ?></p>
            <p><strong>Опыт:</strong><br><?= nl2br(htmlspecialchars($selected_app['experience'])) ?></p>
            <p><strong>Портфолио:</strong> <?= htmlspecialchars((string)$selected_app['portfolio']) ?></p>
            <p><strong>Telegram:</strong> <?= htmlspecialchars((string)$selected_app['telegram']) ?></p>
            <p><strong>GitHub:</strong> <?= htmlspecialchars((string)$selected_app['github']) ?></p>
            <p><strong>Статус:</strong> <?= htmlspecialchars($selected_app['status']) ?></p>

            <?php if ($modal === 'approve' && $selected_app['status'] === 'new'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="approve_developer">
                    <input type="hidden" name="id" value="<?= (int)$selected_app['id'] ?>">
                    <textarea class="cyber-input" name="comment" rows="3" placeholder="Комментарий менеджера (необязательно)"></textarea>
                    <div style="display:flex; gap:0.8rem; margin-top:0.8rem;">
                        <button class="btn-cyber btn-gold" type="submit">Подтвердить одобрение</button>
                        <a href="developers.php" class="btn-cyber btn-neon">Отмена</a>
                    </div>
                </form>
            <?php elseif ($modal === 'reject' && $selected_app['status'] === 'new'): ?>
                <form method="POST">
                    <input type="hidden" name="action" value="reject_developer">
                    <input type="hidden" name="id" value="<?= (int)$selected_app['id'] ?>">
                    <textarea class="cyber-input" name="comment" rows="3" placeholder="Причина отклонения"></textarea>
                    <div style="display:flex; gap:0.8rem; margin-top:0.8rem;">
                        <button class="btn-cyber btn-gold" type="submit">Подтвердить отклонение</button>
                        <a href="developers.php" class="btn-cyber btn-neon">Отмена</a>
                    </div>
                </form>
            <?php elseif ($selected_app['status'] === 'new'): ?>
                <div style="display:flex; gap:0.8rem; margin-top:0.8rem;">
                    <a href="developers.php?modal=approve&id=<?= (int)$selected_app['id'] ?>" class="btn-cyber btn-gold">Одобрить</a>
                    <a href="developers.php?modal=reject&id=<?= (int)$selected_app['id'] ?>" class="btn-cyber btn-neon">Отклонить</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>