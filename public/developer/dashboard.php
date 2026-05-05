<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isDeveloper()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];

// Получаем данные разработчика
$result = query("SELECT d.*, u.full_name, u.email 
                 FROM developers d 
                 JOIN users u ON d.user_id = u.id 
                 WHERE u.id = $user_id");
$developer = fetch($result);

// Получаем проекты
$projects = query("SELECT p.*, c.company_name,
                        (SELECT COUNT(*) FROM messages WHERE project_id = p.id AND is_read = 0 AND sender_id != $user_id) as new_messages
                        FROM projects p 
                        JOIN clients c ON p.client_id = c.id 
                        WHERE p.developer_id = {$developer['id']}
                        ORDER BY p.created_at DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель разработчика</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 280px;
            background: rgba(17, 17, 31, 0.95);
            border-right: 1px solid rgba(255, 215, 0, 0.3);
            padding: 2rem 1rem;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .logo-gold {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            background: linear-gradient(45deg, #FFD700, #FFC400);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }
        
        .user-info {
            margin-top: 1rem;
        }
        
        .user-name {
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .user-level {
            color: #FFD700;
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }
        
        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .sidebar-nav a {
            color: #a0a0c0;
            text-decoration: none;
            padding: 0.8rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .sidebar-nav a:hover {
            background: rgba(255, 215, 0, 0.1);
            color: #FFD700;
        }
        
        .sidebar-nav a.active {
            background: rgba(255, 215, 0, 0.15);
            color: #FFD700;
            border-left: 3px solid #FFD700;
        }
        
        .content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
        }
        
        .content h1 {
            color: #FFD700;
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            margin-bottom: 2rem;
        }
        
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .project-card {
            background: rgba(17, 17, 31, 0.8);
            border: 1px solid rgba(255, 215, 0, 0.2);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .project-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255, 215, 0, 0.5);
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.1);
        }
        
        .project-card h3 {
            color: #fff;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        
        .project-card .company {
            color: #FFD700;
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }
        
        .project-card .description {
            color: #a0a0c0;
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .new-messages {
            background: rgba(255, 51, 102, 0.2);
            color: #ff3366;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .project-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .status-new {
            background: rgba(0, 243, 255, 0.2);
            color: #00f3ff;
        }
        
        .status-in_progress {
            background: rgba(255, 215, 0, 0.2);
            color: #FFD700;
        }
        
        .status-completed {
            background: rgba(0, 255, 136, 0.2);
            color: #00ff88;
        }
        
        .status-cancelled {
            background: rgba(255, 51, 102, 0.2);
            color: #ff3366;
        }
        
        .btn-small {
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            color: #FFD700;
            padding: 0.4rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s ease;
        }
        
        .btn-small:hover {
            background: rgba(255, 215, 0, 0.2);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="dark-theme">
    <div class="dashboard">
        <!-- Боковое меню -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo-gold">LARK</div>
                <div class="user-info">
                    <div class="user-name"><?= htmlspecialchars($developer['full_name'] ?? 'Разработчик') ?></div>
                    <div class="user-level"><?= strtoupper($developer['level'] ?? 'junior') ?></div>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="active">📊 Мои проекты</a>
                <a href="projects/available.php">🔍 Доступные проекты</a>
                <a href="messages/index.php">💬 Сообщения</a>
                <a href="profile.php">👤 Профиль</a>
                <a href="logout.php">🚪 Выход</a>
            </nav>
        </aside>
        
        <!-- Основной контент -->
        <main class="content">
            <h1>Мои проекты</h1>
            
            <?php if ($projects && mysqli_num_rows($projects) > 0): ?>
                <div class="projects-grid">
                    <?php while ($project = fetch($projects)): ?>
                    <div class="project-card">
                        <h3><?= htmlspecialchars($project['title']) ?></h3>
                        <p class="company"><?= htmlspecialchars($project['company_name']) ?></p>
                        <p class="description"><?= htmlspecialchars(mb_substr($project['description'], 0, 100)) ?>...</p>
                        
                        <?php if ($project['new_messages'] > 0): ?>
                            <div class="new-messages">
                                📨 Новых сообщений: <?= $project['new_messages'] ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="project-footer">
                            <span class="status status-<?= $project['status'] ?>">
                                <?= $project['status'] == 'new' ? 'Новый' : 
                                    ($project['status'] == 'in_progress' ? 'В работе' : 
                                    ($project['status'] == 'completed' ? 'Завершен' : 'Отменен')) ?>
                            </span>
                            <a href="projects/view.php?id=<?= $project['id'] ?>" class="btn-small">Подробнее →</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="cyber-card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--primary-gold); margin-bottom: 1rem;"></i>
                    <h3 style="margin-bottom: 1rem;">У вас пока нет проектов</h3>
                    <p style="color: var(--text-gray); margin-bottom: 2rem;">Посмотрите доступные проекты и начните работать!</p>
                    <a href="projects/available.php" class="btn-cyber btn-gold" style="display: inline-block;">
                        Найти проекты
                    </a>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>