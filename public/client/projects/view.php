<?php
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isClient()) {
    redirect('/client/login.php');
}

$user_id = $_SESSION['user_id'];
$project_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Получаем проект с проверкой прав
$result = query("
    SELECT p.*, 
           c.company_name,
           d.id as developer_id,
           u.full_name as developer_name,
           d.level as developer_level,
           d.rating as developer_rating,
           d.skills as developer_skills,
           u.email as developer_email
    FROM projects p
    JOIN clients c ON p.client_id = c.id
    LEFT JOIN developers d ON p.developer_id = d.id
    LEFT JOIN users u ON d.user_id = u.id
    WHERE p.id = $project_id AND c.user_id = $user_id
");

if (mysqli_num_rows($result) == 0) {
    redirect('index.php');
}

$project = fetch($result);

// Помечаем сообщения как прочитанные
query("UPDATE messages SET is_read = 1, read_at = NOW() 
       WHERE project_id = $project_id AND sender_id != $user_id");

// Получаем сообщения
$messages = query("
    SELECT m.*, u.full_name as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.project_id = $project_id
    ORDER BY m.created_at ASC
");

// Отправка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message']) && !empty($_POST['message'])) {
    $message = escape($_POST['message']);
    
    query("INSERT INTO messages (project_id, sender_id, message, created_at) 
           VALUES ($project_id, $user_id, '$message', NOW())");
    
    // Уведомление менеджеру
    if ($project['manager_id']) {
        addNotification(
            $project['manager_id'],
            'Новое сообщение',
            "Клиент отправил сообщение по проекту: {$project['title']}",
            "/admin/projects/view.php?id=$project_id"
        );
    }
    
    redirect("view.php?id=$project_id");
}

// Статусы
$status_options = [
    'new' => 'Новый',
    'in_progress' => 'В работе',
    'completed' => 'Завершен',
    'cancelled' => 'Отменен'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр проекта | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .chat-container {
            max-height: 400px;
            overflow-y: auto;
            padding: 1rem;
            background: rgba(0,0,0,0.2);
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .message {
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 8px;
            max-width: 80%;
        }
        
        .message-client {
            background: rgba(255,215,0,0.1);
            border-left: 3px solid var(--primary-gold);
            margin-left: auto;
        }
        
        .message-manager {
            background: rgba(0,243,255,0.1);
            border-left: 3px solid var(--cyber-blue);
        }
        
        .message-developer {
            background: rgba(157,78,221,0.1);
            border-left: 3px solid var(--neon-purple);
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
            color: var(--text-gray);
        }
        
        .message-content {
            word-wrap: break-word;
            color: var(--text-light);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .info-item {
            padding: 1rem;
            background: rgba(255,255,255,0.03);
            border-radius: 4px;
        }
        
        .info-label {
            color: var(--text-gray);
            font-size: 0.85rem;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 600;
        }
    </style>
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
                    <div class="logo-glow"></div>
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                </a>

                <div class="nav-hologram">
                    <a href="/" class="nav-link">ГЛАВНАЯ</a>
                    <a href="/client/dashboard.php" class="nav-link">КАБИНЕТ</a>
                    <a href="index.php" class="nav-link">МОИ ПРОЕКТЫ</a>
                    <a href="/client/logout.php" class="nav-link admin-portal">
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
            <!-- Навигация -->
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                <a href="index.php" class="btn-cyber btn-neon" style="padding: 0.5rem 1rem; min-width: auto;">
                    <i class="fas fa-arrow-left"></i> НАЗАД
                </a>
                <h1 class="title-gold" style="font-size: 2rem;"><?= htmlspecialchars($project['title']) ?></h1>
                <span style="background: <?= $status_options[$project['status']] ?>20; color: <?= $status_options[$project['status']] ?>; padding: 0.3rem 1rem; border-radius: 20px; font-size: 0.9rem; margin-left: auto;">
                    <?= $status_options[$project['status']] ?>
                </span>
            </div>

            <!-- Основная информация -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Левая колонка - информация о проекте -->
                <div>
                    <div class="cyber-card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;">ОПИСАНИЕ ПРОЕКТА</h3>
                        <p style="color: var(--text-gray); line-height: 1.8; white-space: pre-line;">
                            <?= nl2br(htmlspecialchars($project['description'])) ?>
                        </p>
                        
                        <?php if ($project['requirements']): ?>
                        <h3 style="color: var(--cyber-blue); margin: 2rem 0 1rem;">ТРЕБОВАНИЯ К РАЗРАБОТЧИКУ</h3>
                        <p style="color: var(--text-gray); line-height: 1.8; white-space: pre-line;">
                            <?= nl2br(htmlspecialchars($project['requirements'])) ?>
                        </p>
                        <?php endif; ?>
                    </div>

                    <!-- Чат -->
                    <div class="cyber-card">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;">
                            <i class="fas fa-comments"></i> ЧАТ С МЕНЕДЖЕРОМ
                        </h3>
                        
                        <div class="chat-container" id="chatContainer">
                            <?php if (mysqli_num_rows($messages) == 0): ?>
                            <div style="text-align: center; padding: 2rem; color: var(--text-gray);">
                                <i class="fas fa-comment-dots" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                <p>Нет сообщений. Напишите менеджеру, чтобы обсудить детали.</p>
                            </div>
                            <?php else: ?>
                                <?php while ($msg = fetch($messages)): 
                                    $msg_class = $msg['sender_id'] == $user_id ? 'message-client' : 
                                                ($msg['sender_role'] == 'manager' ? 'message-manager' : 'message-developer');
                                ?>
                                <div class="message <?= $msg_class ?>">
                                    <div class="message-header">
                                        <span><?= htmlspecialchars($msg['sender_name']) ?> (<?= $msg['sender_role'] ?>)</span>
                                        <span><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></span>
                                    </div>
                                    <div class="message-content">
                                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>

                        <form method="POST" class="cyber-form">
                            <div style="display: flex; gap: 1rem;">
                                <textarea name="message" rows="2" class="cyber-input" 
                                          placeholder="Введите сообщение..." required style="flex: 1;"></textarea>
                                <button type="submit" class="btn-cyber btn-gold" style="padding: 0 2rem; min-width: auto;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Правая колонка - детали и разработчик -->
                <div>
                    <!-- Детали проекта -->
                    <div class="cyber-card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;">ДЕТАЛИ ПРОЕКТА</h3>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">ID проекта</div>
                                <div class="info-value">#<?= $project['id'] ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Дата создания</div>
                                <div class="info-value"><?= date('d.m.Y', strtotime($project['created_at'])) ?></div>
                            </div>
                            
                            <?php if ($project['budget']): ?>
                            <div class="info-item">
                                <div class="info-label">Бюджет</div>
                                <div class="info-value"><?= number_format($project['budget'], 0, ',', ' ') ?> ₽</div>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($project['deadline']): ?>
                            <div class="info-item">
                                <div class="info-label">Дедлайн</div>
                                <div class="info-value"><?= date('d.m.Y', strtotime($project['deadline'])) ?></div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Информация о разработчике -->
                    <div class="cyber-card">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;">
                            <i class="fas fa-user"></i> РАЗРАБОТЧИК
                        </h3>
                        
                        <?php if ($project['developer_name']): ?>
                            <div style="text-align: center; margin-bottom: 1.5rem;">
                                <div style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(45deg, var(--primary-gold), var(--cyber-blue)); margin: 0 auto 1rem;"></div>
                                <h4 style="font-family: 'Orbitron'; margin-bottom: 0.3rem;"><?= htmlspecialchars($project['developer_name']) ?></h4>
                                <p style="color: var(--text-gray); margin-bottom: 1rem;">Уровень: <?= $project['developer_level'] ?></p>
                                
                                <?php if ($project['developer_skills']): 
                                    $skills = json_decode($project['developer_skills'], true) ?: explode(',', $project['developer_skills']);
                                ?>
                                <div style="display: flex; gap: 0.5rem; justify-content: center; flex-wrap: wrap; margin-bottom: 1rem;">
                                    <?php foreach (array_slice($skills, 0, 3) as $skill): ?>
                                    <span class="tag"><?= htmlspecialchars(trim($skill)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($project['developer_rating']): ?>
                                <div class="skill-meter" style="margin-bottom: 1rem;">
                                    <div class="meter-label">Рейтинг</div>
                                    <div class="meter-bar">
                                        <div class="meter-fill" style="width: <?= ($project['developer_rating'] / 5) * 100 ?>%"></div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 2rem; color: var(--text-gray);">
                                <i class="fas fa-user-clock" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                                <p>Разработчик еще не назначен</p>
                                <p style="font-size: 0.9rem; margin-top: 0.5rem;">Менеджер подберет специалиста после проверки проекта</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
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
        
        // Скролл чата вниз
        const chatContainer = document.getElementById('chatContainer');
        chatContainer.scrollTop = chatContainer.scrollHeight;
    </script>
</body>
</html>