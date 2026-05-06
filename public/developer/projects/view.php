<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isDeveloper()) {
    redirect('/developer/login.php');
}

$user_id = (int)($_SESSION['user_id'] ?? 0);
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    redirect('/developer/dashboard.php');
}

$dev_result = query("SELECT id FROM developers WHERE user_id = $user_id LIMIT 1");
$dev = $dev_result ? fetch($dev_result) : null;
if (!$dev) {
    redirect('/developer/dashboard.php');
}
$developer_id = (int)$dev['id'];

$project_result = query("
    SELECT p.*, c.company_name, u.full_name as client_name
    FROM projects p
    JOIN clients c ON c.id = p.client_id
    JOIN users u ON u.id = c.user_id
    WHERE p.id = $project_id AND (p.developer_id = $developer_id OR p.developer_id IS NULL)
    LIMIT 1
");
$project = $project_result ? fetch($project_result) : null;
if (!$project) {
    redirect('/developer/dashboard.php');
}

// Handle project application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_project'])) {
    $cover_letter = escape($_POST['cover_letter'] ?? '');
    $proposed_budget = isset($_POST['proposed_budget']) && is_numeric($_POST['proposed_budget']) ? (float)$_POST['proposed_budget'] : null;
    $estimated_time = escape($_POST['estimated_time'] ?? '');

    // Create notification for the client
    $client_user_result = query("SELECT user_id FROM clients WHERE id = {$project['client_id']} LIMIT 1");
    $client_user = $client_user_result ? fetch($client_user_result) : null;

    if ($client_user) {
        $message = "Разработчик " . htmlspecialchars($dev['full_name'] ?? 'Неизвестный') . " подал заявку на проект '" . htmlspecialchars($project['title']) . "'.";
        if (!empty($cover_letter)) {
            $message .= "\n\nСопроводительное письмо: " . $cover_letter;
        }
        if ($proposed_budget) {
            $message .= "\nПредлагаемый бюджет: " . number_format($proposed_budget, 0, ',', ' ') . " ₽";
        }
        if (!empty($estimated_time)) {
            $message .= "\nОриентировочное время выполнения: " . $estimated_time;
        }

        addNotification($client_user['user_id'], 'Новая заявка на проект', $message, "/client/projects/view.php?id={$project_id}");
    }

    // Create notification for managers
    $managers_result = query("SELECT id FROM users WHERE role = 'manager' OR role = 'admin'");
    if ($managers_result) {
        while ($manager = fetch($managers_result)) {
            $message = "Разработчик подал заявку на проект #" . $project_id . " '" . htmlspecialchars($project['title']) . "'.";
            addNotification($manager['id'], 'Заявка на проект', $message, "/admin/projects/view.php?id={$project_id}");
        }
    }

    // Log the application
    addLog($user_id, 'project_application', "Applied for project #{$project_id}");

    // Redirect with success message
    redirect("/developer/projects/view.php?id={$project_id}&applied=1");
}

$unread_messages = query("
    SELECT COUNT(*) as count
    FROM messages m
    JOIN projects p ON m.project_id = p.id
    WHERE p.developer_id = $developer_id AND m.is_read = 0 AND m.sender_id != $user_id
");
$unread = $unread_messages ? fetch($unread_messages)['count'] : 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Проект #<?= (int)$project['id'] ?> | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                    <a href="../dashboard.php" class="nav-link">КАБИНЕТ</a>
                    <a href="available.php" class="nav-link">ДОСТУПНЫЕ</a>
                    <a href="../profile.php" class="nav-link">ПРОФИЛЬ</a>
                    <a href="../messages/index.php" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <?php if ($unread > 0): ?>
                            <span style="margin-left: 0.35rem; color: var(--primary-gold);"><?= (int)$unread ?></span>
                        <?php endif; ?>
                    </a>
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h1 class="title-gold" style="font-size: 2rem;">
                    <i class="fas fa-project-diagram" style="margin-right: 0.5rem;"></i> Проект #<?= (int)$project['id'] ?>
                </h1>
                <div style="display: flex; gap: 1rem;">
                    <a href="../dashboard.php" class="btn-cyber btn-neon" style="padding: 0.5rem 1rem; min-width: auto;">
                        <i class="fas fa-arrow-left" style="margin-right: 0.3rem;"></i> Назад
                    </a>
                    <?php if ($project['developer_id'] == $developer_id): ?>
                        <a href="../messages/index.php?project=<?= (int)$project['id'] ?>" class="btn-cyber btn-gold" style="padding: 0.5rem 1rem; min-width: auto;">
                            <i class="fas fa-envelope" style="margin-right: 0.3rem;"></i> Сообщения
                        </a>
                    <?php elseif ($project['developer_id'] === null && $project['status'] === 'new'): ?>
                        <button onclick="openApplyModal()" class="btn-cyber btn-gold" style="padding: 0.5rem 1rem; min-width: auto;">
                            <i class="fas fa-hand-paper" style="margin-right: 0.3rem;"></i> Подать заявку
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_GET['applied'])): ?>
            <div style="background: rgba(0,255,0,0.1); border: 1px solid var(--success); padding: 1rem; border-radius: 4px; margin-bottom: 2rem; color: var(--success);">
                <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i> Ваша заявка успешно отправлена! Клиент и менеджеры получат уведомление.
            </div>
            <?php endif; ?>

            <!-- Информация о проекте -->
            <div class="cyber-card" style="margin-bottom: 2rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; font-size: 1.3rem;">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i> Детали проекта
                    </h3>
                    <span style="background: <?= $project['status'] == 'new' ? 'rgba(0,243,255,0.1)' : ($project['status'] == 'in_progress' ? 'rgba(255,215,0,0.1)' : 'rgba(0,255,0,0.1)') ?>; 
                          color: <?= $project['status'] == 'new' ? 'var(--cyber-blue)' : ($project['status'] == 'in_progress' ? 'var(--primary-gold)' : 'var(--success)') ?>; 
                          padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; border: 1px solid <?= $project['status'] == 'new' ? 'var(--cyber-blue)' : ($project['status'] == 'in_progress' ? 'var(--primary-gold)' : 'var(--success)') ?>;">
                        <i class="fas fa-circle" style="margin-right: 0.3rem;"></i>
                        <?= $project['status'] == 'new' ? 'Новый' : ($project['status'] == 'in_progress' ? 'В работе' : ($project['status'] == 'completed' ? 'Завершен' : $project['status'])) ?>
                    </span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Название проекта</div>
                        <div style="color: var(--text-light); font-weight: 500; font-size: 1.1rem;"><?= htmlspecialchars($project['title']) ?></div>
                    </div>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Заказчик</div>
                        <div style="color: var(--text-light); font-weight: 500;">
                            <i class="fas fa-building" style="margin-right: 0.3rem; color: var(--primary-gold);"></i>
                            <?= htmlspecialchars($project['company_name']) ?>
                        </div>
                        <div style="color: var(--text-gray); font-size: 0.8rem; margin-top: 0.3rem;">
                            <i class="fas fa-user" style="margin-right: 0.3rem;"></i>
                            <?= htmlspecialchars($project['client_name']) ?>
                        </div>
                    </div>
                    <?php if ($project['budget']): ?>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Бюджет</div>
                        <div style="color: var(--cyber-blue); font-weight: 500; font-size: 1.1rem;">
                            <i class="fas fa-ruble-sign" style="margin-right: 0.3rem;"></i>
                            <?= number_format((float)$project['budget'], 0, ',', ' ') ?> ₽
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($project['deadline']): ?>
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 4px;">
                        <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.3rem;">Дедлайн</div>
                        <div style="color: var(--text-light); font-weight: 500;">
                            <i class="fas fa-calendar-alt" style="margin-right: 0.3rem; color: var(--primary-gold);"></i>
                            <?= date('d.m.Y', strtotime($project['deadline'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 4px; margin-bottom: 1rem;">
                    <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-align-left" style="margin-right: 0.3rem;"></i> Описание проекта
                    </div>
                    <div style="color: var(--text-light); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($project['description'])) ?>
                    </div>
                </div>

                <?php if (!empty($project['requirements'])): ?>
                <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 4px;">
                    <div style="color: var(--text-gray); font-size: 0.9rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-list-check" style="margin-right: 0.3rem;"></i> Требования
                    </div>
                    <div style="color: var(--text-light); line-height: 1.6;">
                        <?= nl2br(htmlspecialchars($project['requirements'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem; margin-top: 1.5rem; color: var(--text-gray); font-size: 0.85rem;">
                    <i class="fas fa-calendar" style="margin-right: 0.3rem;"></i> Создан: <?= date('d.m.Y H:i', strtotime($project['created_at'])) ?>
                    <?php if ($project['updated_at'] != $project['created_at']): ?>
                        | <i class="fas fa-edit" style="margin-right: 0.3rem;"></i> Обновлен: <?= date('d.m.Y H:i', strtotime($project['updated_at'])) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Модальное окно для подачи заявки -->
    <div id="applyModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); z-index: 1000; align-items: center; justify-content: center; padding: 2rem; box-sizing: border-box;">
        <div class="modal-content" style="background: rgba(20,20,30,0.98); backdrop-filter: blur(10px); border: 2px solid var(--primary-gold); border-radius: 12px; padding: 2.5rem; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 40px rgba(0,0,0,0.5);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; border-bottom: 1px solid rgba(255,215,0,0.3); padding-bottom: 1rem;">
                <h3 style="color: var(--primary-gold); font-family: 'Orbitron'; font-size: 1.4rem; margin: 0;">
                    <i class="fas fa-hand-paper" style="margin-right: 0.5rem;"></i> Подать заявку на проект
                </h3>
                <button onclick="closeApplyModal()" style="background: rgba(255,255,255,0.1); border: 1px solid var(--text-gray); border-radius: 50%; width: 40px; height: 40px; color: var(--text-gray); font-size: 1.2rem; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form method="POST" style="display: grid; gap: 2rem;">
                <div>
                    <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.75rem; font-size: 1rem;">
                        <i class="fas fa-envelope-open-text" style="margin-right: 0.5rem; color: var(--cyber-blue);"></i> Сопроводительное письмо
                    </label>
                    <textarea name="cover_letter" class="cyber-input" rows="5" placeholder="Расскажите почему вы подходите для этого проекта, ваш опыт и подход к решению..." style="resize: vertical; min-height: 120px;"></textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.75rem; font-size: 1rem;">
                            <i class="fas fa-ruble-sign" style="margin-right: 0.5rem; color: var(--success);"></i> Предлагаемый бюджет (₽)
                        </label>
                        <input name="proposed_budget" type="number" class="cyber-input" placeholder="Ваш бюджет" min="0" step="100">
                        <small style="color: var(--text-gray); font-size: 0.85rem; display: block; margin-top: 0.5rem;">Оставьте пустым, если согласны с бюджетом заказчика</small>
                    </div>

                    <div>
                        <label style="display: block; color: var(--text-light); font-weight: 500; margin-bottom: 0.75rem; font-size: 1rem;">
                            <i class="fas fa-clock" style="margin-right: 0.5rem; color: var(--primary-gold);"></i> Ориентировочное время
                        </label>
                        <input name="estimated_time" class="cyber-input" placeholder="Например: 2 недели">
                        <small style="color: var(--text-gray); font-size: 0.85rem; display: block; margin-top: 0.5rem;">Сроки выполнения</small>
                    </div>
                </div>

                <div style="background: rgba(255,215,0,0.1); padding: 1.5rem; border-radius: 8px; border: 1px solid var(--primary-gold);">
                    <div style="color: var(--primary-gold); font-size: 1rem; font-weight: 500; margin-bottom: 0.75rem;">
                        <i class="fas fa-info-circle" style="margin-right: 0.5rem;"></i> Что произойдет после отправки?
                    </div>
                    <ul style="color: var(--text-gray); font-size: 0.9rem; margin: 0; padding-left: 1.5rem; line-height: 1.6;">
                        <li>Заказчик получит уведомление о вашей заявке</li>
                        <li>Менеджеры рассмотрят вашу кандидатуру</li>
                        <li>Вы получите ответ в ближайшее время</li>
                    </ul>
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 1rem;">
                    <button type="button" onclick="closeApplyModal()" class="btn-cyber btn-neon" style="padding: 0.875rem 2rem; font-size: 1rem;">
                        <i class="fas fa-times" style="margin-right: 0.5rem;"></i> Отмена
                    </button>
                    <button type="submit" name="apply_project" class="btn-cyber btn-gold" style="padding: 0.875rem 2rem; font-size: 1rem;">
                        <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i> Отправить заявку
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Футер -->
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

        function openApplyModal() {
            document.getElementById('applyModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeApplyModal() {
            document.getElementById('applyModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        document.getElementById('applyModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeApplyModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('applyModal').style.display === 'flex') {
                closeApplyModal();
            }
        });
    </script>
</body>
</html>

