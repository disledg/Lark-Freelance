<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isDeveloper()) {
    redirect('login.php');
}

$user_id = (int)$_SESSION['user_id'];

$result = query("
    SELECT d.*, u.full_name, u.email, u.created_at, u.last_login
    FROM developers d
    JOIN users u ON d.user_id = u.id
    WHERE u.id = $user_id
");
$developer = $result ? fetch($result) : null;
if (!$developer) {
    redirect('login.php');
}

$developer_id = (int)$developer['id'];

$stats = [];
$r = query("SELECT COUNT(*) as count FROM projects WHERE developer_id = $developer_id");
$stats['total'] = $r ? (int)fetch($r)['count'] : 0;
$r = query("SELECT COUNT(*) as count FROM projects WHERE developer_id = $developer_id AND status = 'new'");
$stats['new'] = $r ? (int)fetch($r)['count'] : 0;
$r = query("SELECT COUNT(*) as count FROM projects WHERE developer_id = $developer_id AND status = 'in_progress'");
$stats['in_progress'] = $r ? (int)fetch($r)['count'] : 0;
$r = query("SELECT COUNT(*) as count FROM projects WHERE developer_id = $developer_id AND status = 'completed'");
$stats['completed'] = $r ? (int)fetch($r)['count'] : 0;

$recent_projects = query("
    SELECT p.*, c.company_name
    FROM projects p
    JOIN clients c ON c.id = p.client_id
    WHERE p.developer_id = $developer_id
    ORDER BY p.created_at DESC
    LIMIT 3
");

$unread_messages = query("
    SELECT COUNT(*) as count
    FROM messages m
    JOIN projects p ON m.project_id = p.id
    WHERE p.developer_id = $developer_id AND m.is_read = 0 AND m.sender_id != $user_id
");
$unread = $unread_messages ? (int)fetch($unread_messages)['count'] : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Кабинет разработчика | Lark Freelance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <a href="dashboard.php" class="nav-link active">КАБИНЕТ</a>
                    <a href="profile.php" class="nav-link">ПРОФИЛЬ</a>
                    <a href="messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread > 0): ?>
                            <span style="margin-left: 0.35rem; color: var(--primary-gold);"><?= $unread ?></span>
                        <?php endif; ?>
                    </a>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 class="title-gold" style="font-size: 2rem;">
                        Здравствуйте, <?= htmlspecialchars($developer['full_name'] ?: 'Разработчик') ?>!
                    </h1>
                    <p style="color: var(--text-gray);">
                        <i class="fas fa-code" style="color: var(--primary-gold); margin-right: 0.5rem;"></i>
                        Уровень: <?= strtoupper(htmlspecialchars($developer['level'] ?: 'junior')) ?>
                    </p>
                </div>
                <?php if ($unread > 0): ?>
                <div style="background: rgba(255,215,0,0.1); padding: 0.5rem 1.5rem; border-radius: 30px; border: 1px solid var(--primary-gold);">
                    <i class="fas fa-envelope" style="color: var(--primary-gold); margin-right: 0.5rem;"></i>
                    <span style="color: var(--primary-gold); font-weight: bold;"><?= $unread ?></span>
                    <span style="color: var(--text-gray);"> новых сообщений</span>
                </div>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--cyber-blue); font-weight: bold;"><?= $stats['total'] ?></div>
                    <div style="color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Всего проектов</div>
                </div>
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--cyber-blue); font-weight: bold;"><?= $stats['new'] ?></div>
                    <div style="color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Новые</div>
                </div>
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--primary-gold); font-weight: bold;"><?= $stats['in_progress'] ?></div>
                    <div style="color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">В работе</div>
                </div>
                <div class="cyber-card" style="padding: 1.5rem; text-align: center;">
                    <div style="font-size: 2.5rem; color: var(--success); font-weight: bold;"><?= $stats['completed'] ?></div>
                    <div style="color: var(--text-gray); text-transform: uppercase; letter-spacing: 1px; font-size: 0.9rem;">Завершено</div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(1, 1fr); gap: 1.5rem; margin-bottom: 3rem;">
                <a href="messages/index.php" class="cyber-card" style="text-decoration: none; display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; transition: all 0.3s;">
                    <div style="width: 60px; height: 60px; border-radius: 50%; background: rgba(0,243,255,0.1); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-comments" style="font-size: 2rem; color: var(--cyber-blue);"></i>
                    </div>
                    <div>
                        <h3 style="color: var(--text-light); margin-bottom: 0.3rem; font-family: 'Orbitron';">Сообщения</h3>
                        <p style="color: var(--text-gray); font-size: 0.9rem;">Общение по текущим проектам</p>
                    </div>
                    <i class="fas fa-arrow-right" style="margin-left: auto; color: var(--cyber-blue);"></i>
                </a>
            </div>

            <?php if ($recent_projects && mysqli_num_rows($recent_projects) > 0): ?>
            <div class="cyber-card" style="margin-bottom: 3rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; font-size: 1.3rem;">
                        <i class="fas fa-history" style="margin-right: 0.5rem;"></i> Последние проекты
                    </h3>
                </div>

                <div style="display: grid; gap: 1rem;">
                    <?php while ($project = fetch($recent_projects)):
                        $status_color = $project['status'] === 'new' ? 'var(--cyber-blue)' : ($project['status'] === 'in_progress' ? 'var(--primary-gold)' : ($project['status'] === 'completed' ? 'var(--success)' : 'var(--text-gray)'));
                        $status_name = $project['status'] === 'new' ? 'Новый' : ($project['status'] === 'in_progress' ? 'В работе' : ($project['status'] === 'completed' ? 'Завершен' : $project['status']));
                    ?>
                    <a href="projects/view.php?id=<?= (int)$project['id'] ?>" style="text-decoration: none; display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(255,255,255,0.02); border-radius: 4px; transition: 0.3s; border: 1px solid transparent;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap;">
                                <h4 style="color: var(--text-light); font-size: 1.1rem;"><?= htmlspecialchars($project['title']) ?></h4>
                                <span style="background: <?= $status_color ?>20; color: <?= $status_color ?>; padding: 0.2rem 0.8rem; border-radius: 20px; font-size: 0.75rem;"><?= $status_name ?></span>
                            </div>
                            <p style="color: var(--text-gray); font-size: 0.9rem; margin-top: 0.3rem;"><?= htmlspecialchars($project['company_name']) ?></p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <span style="color: var(--text-gray); font-size: 0.85rem;"><i class="fas fa-calendar"></i> <?= date('d.m.Y', strtotime($project['created_at'])) ?></span>
                            <i class="fas fa-chevron-right" style="color: var(--primary-gold);"></i>
                        </div>
                    </a>
                    <?php endwhile; ?>
                </div>
            </div>
            <?php endif; ?>

            <div style="margin-top: 2rem; display: flex; justify-content: space-between; align-items: center; color: var(--text-gray); font-size: 0.85rem;">
                <div><i class="fas fa-user-clock"></i> Дата регистрации: <?= date('d.m.Y', strtotime($developer['created_at'])) ?></div>
                <div><i class="fas fa-history"></i> Последний вход: <?= $developer['last_login'] ? date('d.m.Y H:i', strtotime($developer['last_login'])) : 'Первый вход' ?></div>
                <a href="profile.php" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto;"><i class="fas fa-user"></i> Профиль</a>
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

