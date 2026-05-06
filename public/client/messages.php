<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isClient()) {
    redirect('/client/login.php');
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$c_result = query("SELECT id FROM clients WHERE user_id = $user_id LIMIT 1");
$client = $c_result ? fetch($c_result) : null;
if (!$client) {
    redirect('/client/dashboard.php');
}
$client_id = (int)$client['id'];

$project_id = isset($_GET['project']) ? (int)$_GET['project'] : 0;
if ($project_id <= 0) {
    $p = query("SELECT id FROM projects WHERE client_id = $client_id ORDER BY updated_at DESC LIMIT 1");
    $row = $p ? fetch($p) : null;
    $project_id = $row ? (int)$row['id'] : 0;
}

if ($project_id > 0 && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $message = escape($_POST['message']);
    query("INSERT INTO messages (project_id, sender_id, message, is_read, created_at) VALUES ($project_id, $user_id, '$message', 0, NOW())");
    redirect("/client/messages.php?project=$project_id");
}

$projects = query("SELECT id, title FROM projects WHERE client_id = $client_id ORDER BY updated_at DESC");
$messages = $project_id > 0
    ? query("SELECT m.*, u.full_name FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.project_id = $project_id ORDER BY m.created_at ASC")
    : null;
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
    <title>Сообщения клиента | Lark Freelance</title>
    <link rel="stylesheet" href="../assets/css/style.css">
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
                    <a href="dashboard.php" class="nav-link">КАБИНЕТ</a>
                    <a href="projects/index.php" class="nav-link">МОИ ПРОЕКТЫ</a>
                    <a href="profile/index.php" class="nav-link">ПРОФИЛЬ</a>
                    <a href="messages.php" class="nav-link active">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread > 0): ?>
                            <span style="margin-left: 0.35rem; color: var(--primary-gold);"><?= (int)$unread ?></span>
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

    <!-- Основной контент -->
    <section class="cyber-section" style="padding-top: 8rem;">
        <div class="container">
            <h1 class="title-gold" style="font-size: 2rem; margin-bottom: 2rem;">
                <i class="fas fa-envelope" style="margin-right: 0.5rem;"></i> Сообщения
            </h1>

            <div class="cyber-card" style="padding:24px;">

        <div style="display:grid; grid-template-columns: 260px 1fr; gap: 16px;">
            <div>
                <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; margin-bottom: 1rem;">Проекты</h3>
                <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <?php while ($p = fetch($projects)): ?>
                            <a href="?project=<?= (int)$p['id'] ?>" style="text-decoration: none; padding: 0.75rem; background: rgba(255,255,255,0.02); border-radius: 4px; border: 1px solid transparent; transition: all 0.3s; color: var(--text-light); display: block; <?php if ($project_id == $p['id']) echo 'background: rgba(255,215,0,0.1); border-color: var(--primary-gold);'; ?>">
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <span style="color: var(--cyber-blue); font-weight: bold;">#<?= (int)$p['id'] ?></span>
                                    <span style="flex: 1;"><?= htmlspecialchars($p['title']) ?></span>
                                    <?php if ($project_id == $p['id']): ?>
                                        <i class="fas fa-check" style="color: var(--primary-gold);"></i>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-gray); font-style: italic;">Нет проектов</p>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($project_id > 0): ?>
                    <div style="max-height: 420px; overflow:auto; border:1px solid #333; padding:10px;">
                        <?php if ($messages && mysqli_num_rows($messages) > 0): ?>
                            <?php while ($m = fetch($messages)): ?>
                                <p><strong><?= htmlspecialchars($m['full_name']) ?>:</strong> <?= nl2br(htmlspecialchars($m['message'])) ?></p>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>Нет сообщений</p>
                        <?php endif; ?>
                    </div>
                    <form method="POST" style="margin-top: 12px;">
                        <textarea name="message" class="cyber-input" rows="3" placeholder="Введите сообщение..." required></textarea>
                        <button class="btn-cyber btn-gold" type="submit" style="margin-top:8px;">Отправить</button>
                    </form>
                <?php else: ?>
                    <p>Выберите проект</p>
                <?php endif; ?>
            </div>
            </div>
        </div>
    </section>

    <!-- Футер -->
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

