<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isDeveloper()) {
    redirect('/developer/login.php');
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$result = query("
    SELECT d.*, u.full_name, u.email, u.created_at, u.last_login
    FROM developers d
    JOIN users u ON u.id = d.user_id
    WHERE d.user_id = $user_id
    LIMIT 1
");
$developer = $result ? fetch($result) : null;
if (!$developer) {
    redirect('/developer/dashboard.php');
}

$developer_id = (int)$developer['id'];

$unread_messages = query("
    SELECT COUNT(*) as count
    FROM messages m
    JOIN projects p ON m.project_id = p.id
    WHERE p.developer_id = $developer_id AND m.is_read = 0 AND m.sender_id != $user_id
");
$unread = $unread_messages ? fetch($unread_messages)['count'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skills = escape($_POST['skills'] ?? '');
    $experience = escape($_POST['experience'] ?? '');
    $portfolio = escape($_POST['portfolio'] ?? '');
    $telegram = escape($_POST['telegram'] ?? '');
    $github = escape($_POST['github'] ?? '');
    query("UPDATE developers SET skills='$skills', experience='$experience', portfolio='$portfolio', telegram='$telegram', github='$github' WHERE user_id=$user_id");
    redirect('/developer/profile.php?saved=1');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль разработчика | Lark Freelance</title>
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
                    <a href="profile.php" class="nav-link active">ПРОФИЛЬ</a>
                    <a href="messages/index.php" class="nav-link">
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
                <i class="fas fa-user" style="margin-right: 0.5rem;"></i> Профиль разработчика
            </h1>

            <?php if (isset($_GET['saved'])): ?>
            <div style="background: rgba(0,255,0,0.1); border: 1px solid var(--success); padding: 1rem; border-radius: 4px; margin-bottom: 2rem; color: var(--success);">
                <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> Профиль успешно обновлен!
            </div>
            <?php endif; ?>

            <!-- Информация о профиле -->
            <div class="cyber-card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; font-size: 1.3rem;">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i> Основная информация
                    </h3>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">ФИО</div>
                        <div style="color: var(--text-light); font-weight: 500;"><?= htmlspecialchars($developer['full_name']) ?></div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Email</div>
                        <div style="color: var(--text-light); font-weight: 500;"><?= htmlspecialchars($developer['email']) ?></div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Уровень</div>
                        <div style="color: var(--cyber-blue); font-weight: 500;"><?= htmlspecialchars($developer['level']) ?></div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Дата регистрации</div>
                        <div style="color: var(--text-light); font-weight: 500;"><?= date('d.m.Y', strtotime($developer['created_at'])) ?></div>
                    </div>
                </div>
            </div>

            <!-- Форма редактирования -->
            <div class="cyber-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; font-size: 1.3rem;">
                        <i class="fas fa-edit" style="margin-right: 0.5rem;"></i> Редактировать профиль
                    </h3>
                </div>

                <form method="POST" style="display: grid; gap: 1.5rem;">
                    <div>
                        <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.5rem;">
                            <i class="fas fa-code" style="margin-right: 0.5rem; color: var(--cyber-blue);"></i> Навыки
                        </label>
                        <textarea name="skills" class="cyber-input" rows="4" placeholder="Опишите ваши навыки..."><?= htmlspecialchars($developer['skills'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.5rem;">
                            <i class="fas fa-briefcase" style="margin-right: 0.5rem; color: var(--primary-gold);"></i> Опыт работы
                        </label>
                        <textarea name="experience" class="cyber-input" rows="4" placeholder="Опишите ваш опыт..."><?= htmlspecialchars($developer['experience'] ?? '') ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                        <div>
                            <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.5rem;">
                                <i class="fas fa-globe" style="margin-right: 0.5rem; color: var(--success);"></i> Портфолио
                            </label>
                            <input name="portfolio" class="cyber-input" placeholder="Ссылка на портфолио" value="<?= htmlspecialchars($developer['portfolio'] ?? '') ?>">
                        </div>

                        <div>
                            <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.5rem;">
                                <i class="fab fa-telegram" style="margin-right: 0.5rem; color: #0088cc;"></i> Telegram
                            </label>
                            <input name="telegram" class="cyber-input" placeholder="@username" value="<?= htmlspecialchars($developer['telegram'] ?? '') ?>">
                        </div>

                        <div>
                            <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.5rem;">
                                <i class="fab fa-github" style="margin-right: 0.5rem; color: var(--text-light);"></i> GitHub
                            </label>
                            <input name="github" class="cyber-input" placeholder="username" value="<?= htmlspecialchars($developer['github'] ?? '') ?>">
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 1rem;">
                        <button type="submit" class="btn-cyber btn-gold" style="padding: 0.75rem 2rem; font-size: 1rem;">
                            <i class="fas fa-save" style="margin-right: 0.5rem;"></i> Сохранить изменения
                        </button>
                    </div>
                </form>
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

