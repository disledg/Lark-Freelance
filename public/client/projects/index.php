<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// Проверяем авторизацию
if (!isClient()) {
    redirect('/client/login.php');
}

$user_id = $_SESSION['user_id'];

// Получаем ID клиента
$result = query("SELECT id FROM clients WHERE user_id = $user_id");
$client = fetch($result);

if (!$client) {
    redirect('create.php');
}

$client_id = $client['id'];

// Получаем информацию о клиенте для отображения в профиле
$client_info = query("
    SELECT c.*, u.full_name, u.email 
    FROM clients c 
    JOIN users u ON c.user_id = u.id 
    WHERE u.id = $user_id
");
$client_data = fetch($client_info);

// Получаем проекты клиента
$projects = query("
    SELECT p.*, 
           d.id as developer_id,
           u.full_name as developer_name,
           u.email as developer_email,
           (SELECT COUNT(*) FROM messages WHERE project_id = p.id AND is_read = 0 AND sender_id != $user_id) as new_messages
    FROM projects p
    LEFT JOIN developers d ON p.developer_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE p.client_id = $client_id
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

$status_names = [
    'new' => 'Новый',
    'in_progress' => 'В работе',
    'completed' => 'Завершен',
    'cancelled' => 'Отменен'
];

$status_colors = [
    'new' => 'var(--cyber-blue)',
    'in_progress' => 'var(--primary-gold)',
    'completed' => 'var(--success)',
    'cancelled' => 'var(--danger)'
];

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
    <title>Мои проекты | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
                    <a href="index.php" class="nav-link active">МОИ ПРОЕКТЫ</a>
                    <a href="../profile/index.php" class="nav-link">ПРОФИЛЬ</a>
                    
                    <?php if ($unread > 0): ?>
                    <a href="../messages.php" class="nav-link" style="position: relative;">
                        <i class="fas fa-envelope"></i>
                        <span style="position: absolute; top: 0; right: 0; background: var(--danger); color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">
                            <?= $unread ?>
                        </span>
                    </a>
                    <?php endif; ?>
                    
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
            <!-- Заголовок и статистика -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <h1 class="title-gold" style="font-size: 2rem;">МОИ ПРОЕКТЫ</h1>
                    <p style="color: var(--text-gray);">
                        <i class="fas fa-building" style="color: var(--primary-gold); margin-right: 0.5rem;"></i>
                        <?= htmlspecialchars($client_data['company_name'] ?? 'Компания не указана') ?>
                    </p>
                </div>
                <a href="create.php" class="btn-cyber btn-gold">
                    <i class="fas fa-plus"></i> НОВЫЙ ПРОЕКТ
                </a>
            </div>

            <!-- Статистика проектов -->
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem;">
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--text-light); font-weight: bold;"><?= $stats['total'] ?></div>
                    <div style="color: var(--text-gray);">Всего</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--cyber-blue); font-weight: bold;"><?= $stats['new'] ?></div>
                    <div style="color: var(--text-gray);">Новые</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--primary-gold); font-weight: bold;"><?= $stats['in_progress'] ?></div>
                    <div style="color: var(--text-gray);">В работе</div>
                </div>
                <div class="cyber-card" style="padding: 1rem; text-align: center;">
                    <div style="font-size: 2rem; color: var(--success); font-weight: bold;"><?= $stats['completed'] ?></div>
                    <div style="color: var(--text-gray);">Завершено</div>
                </div>
            </div>

            <?php if ($stats['total'] == 0): ?>
            <!-- Пустое состояние -->
            <div class="cyber-card" style="text-align: center; padding: 4rem;">
                <i class="fas fa-folder-open" style="font-size: 4rem; color: var(--primary-gold); margin-bottom: 1rem;"></i>
                <h3 style="margin-bottom: 1rem;">У вас пока нет проектов</h3>
                <p style="color: var(--text-gray); margin-bottom: 2rem;">Создайте первый проект и менеджер поможет найти разработчика</p>
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="create.php" class="btn-cyber btn-gold">СОЗДАТЬ ПРОЕКТ</a>
                    <a href="../profile/index.php" class="btn-cyber btn-neon">НАСТРОИТЬ ПРОФИЛЬ</a>
                </div>
            </div>
            <?php else: ?>

            <!-- Список проектов -->
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 1.5rem;">
                <?php while ($project = mysqli_fetch_assoc($projects)): 
                    $status_color = $status_colors[$project['status']] ?? 'var(--text-gray)';
                    $status_name = $status_names[$project['status']] ?? $project['status'];
                ?>
                <div class="cyber-card" style="display: flex; flex-direction: column; height: 100%;">
                    <!-- Статус и ID -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <span style="background: <?= $status_color ?>20; color: <?= $status_color ?>; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.8rem;">
                            <?= $status_name ?>
                        </span>
                        <span style="color: var(--text-gray); font-size: 0.8rem;">
                            #<?= $project['id'] ?>
                        </span>
                    </div>

                    <!-- Название проекта -->
                    <h3 style="font-family: 'Orbitron'; font-size: 1.2rem; margin-bottom: 0.8rem;">
                        <?= htmlspecialchars($project['title']) ?>
                    </h3>

                    <!-- Описание -->
                    <p style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 1rem; flex-grow: 1;">
                        <?= htmlspecialchars(mb_substr($project['description'], 0, 120)) ?>...
                    </p>

                    <!-- Информация о разработчике -->
                    <?php if ($project['developer_name']): ?>
                    <div style="margin-bottom: 1rem; padding: 0.5rem; background: rgba(255,215,0,0.05); border-radius: 4px;">
                        <i class="fas fa-user" style="color: var(--primary-gold); margin-right: 0.5rem;"></i>
                        <span style="color: var(--text-gray); font-size: 0.9rem;">
                            Разработчик: <?= htmlspecialchars($project['developer_name']) ?>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Бюджет и даты -->
                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem; color: var(--text-gray); font-size: 0.85rem; flex-wrap: wrap;">
                        <?php if ($project['budget']): ?>
                        <span><i class="fas fa-ruble-sign" style="color: var(--primary-gold);"></i> <?= number_format($project['budget'], 0, ',', ' ') ?> ₽</span>
                        <?php endif; ?>
                        
                        <?php if ($project['deadline']): ?>
                        <span><i class="fas fa-calendar" style="color: var(--cyber-blue);"></i> <?= date('d.m.Y', strtotime($project['deadline'])) ?></span>
                        <?php endif; ?>
                        
                        <span><i class="fas fa-clock" style="color: var(--neon-purple);"></i> <?= date('d.m.Y', strtotime($project['created_at'])) ?></span>
                    </div>

                    <!-- Кнопки действий -->
                    <div style="display: flex; gap: 0.5rem; margin-top: 1rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                        <a href="view.php?id=<?= $project['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.5rem; min-width: auto; flex: 1;">
                            <i class="fas fa-eye"></i> Просмотр
                        </a>
                        
                        <?php if ($project['new_messages'] > 0): ?>
                        <a href="../messages.php?project=<?= $project['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.5rem; min-width: auto; position: relative;">
                            <i class="fas fa-envelope"></i>
                            <span style="position: absolute; top: -5px; right: -5px; background: var(--danger); color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 0.7rem; display: flex; align-items: center; justify-content: center;">
                                <?= $project['new_messages'] ?>
                            </span>
                        </a>
                        <?php else: ?>
                        <a href="../messages.php?project=<?= $project['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.5rem; min-width: auto;">
                            <i class="fas fa-comment"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Футер -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-bottom">
                <div class="copyright">© <?= date('Y') ?> LARK FREELANCE</div>
                <div style="color: var(--text-gray); font-size: 0.8rem; margin-top: 0.5rem;">
                    <a href="../profile/index.php" style="color: var(--primary-gold);">Профиль</a> • 
                    <a href="../dashboard.php" style="color: var(--primary-gold);">Кабинет</a> • 
                    <a href="/" style="color: var(--primary-gold);">Главная</a>
                </div>
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