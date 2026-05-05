<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php'; // Этой строки не хватало!

// Проверяем авторизацию
if (!isClient()) {
    $_SESSION['redirect_after_login'] = '/client/projects/create.php';
    redirect('/client/login.php');
}

$user_id = $_SESSION['user_id'];

// Получаем ID клиента
$result = query("SELECT id FROM clients WHERE user_id = $user_id");
$client = fetch($result);

if (!$client) {
    // Если у пользователя нет профиля клиента, создаем
    $result = query("SELECT full_name, email FROM users WHERE id = $user_id");
    $user = fetch($result);
    
    query("INSERT INTO clients (user_id, company_name) VALUES ($user_id, '{$user['full_name']}')");
    $client_id = insert_id();
} else {
    $client_id = $client['id'];
}

// Обработка формы
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = escape($_POST['title']);
    $description = escape($_POST['description']);
    $requirements = escape($_POST['requirements']);
    $budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : 'NULL';
    $deadline = !empty($_POST['deadline']) ? "'" . escape($_POST['deadline']) . "'" : 'NULL';
    
    if (empty($title) || empty($description)) {
        $error = 'Заполните обязательные поля';
    } else {
        $sql = "INSERT INTO projects (title, description, requirements, client_id, budget, deadline, status, created_at) 
                VALUES ('$title', '$description', '$requirements', $client_id, $budget, $deadline, 'new', NOW())";
        
        if (query($sql)) {
            $project_id = insert_id();
            addLog($user_id, 'create_project', "Создан проект #$project_id");
            
            // Уведомление менеджерам
            $managers = query("SELECT id FROM users WHERE role = 'manager' AND status = 'approved'");
            while ($manager = fetch($managers)) {
                addNotification(
                    $manager['id'],
                    'Новый проект',
                    "Клиент создал новый проект: $title",
                    "/admin/projects/view.php?id=$project_id"
                );
            }
            
            $success = true;
        } else {
            $error = 'Ошибка при создании проекта: ' . mysqli_error($connection);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создать проект | Lark Freelance</title>
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
                    <a href="index.php" class="nav-link">МОИ ПРОЕКТЫ</a>
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
            <div style="max-width: 800px; margin: 0 auto;">
                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
                    <a href="index.php" class="btn-cyber btn-neon" style="padding: 0.5rem 1rem; min-width: auto;">
                        <i class="fas fa-arrow-left"></i> НАЗАД
                    </a>
                    <h1 class="title-gold" style="font-size: 2rem;">НОВЫЙ ПРОЕКТ</h1>
                </div>

                <div class="cyber-card">
                    <?php if ($success): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success); margin-bottom: 1rem;"></i>
                        <h3 style="color: var(--success); margin-bottom: 1rem;">Проект успешно создан!</h3>
                        <p style="color: var(--text-gray); margin-bottom: 2rem;">
                            Менеджер рассмотрит ваш проект и свяжется с вами в ближайшее время
                        </p>
                        <div style="display: flex; gap: 1rem; justify-content: center;">
                            <a href="index.php" class="btn-cyber btn-neon">
                                <span class="btn-glow"></span>
                                <span class="btn-text">К ПРОЕКТАМ</span>
                            </a>
                            <a href="create.php" class="btn-cyber btn-gold">
                                <span class="btn-glow"></span>
                                <span class="btn-text">СОЗДАТЬ ЕЩЁ</span>
                            </a>
                        </div>
                    </div>
                    <?php else: ?>

                    <?php if ($error): ?>
                    <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid var(--danger); border-radius: 4px; padding: 1rem; margin-bottom: 2rem; color: var(--danger);">
                        <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="cyber-form">
                        <div class="form-grid">
                            <div class="input-group full-width">
                                <label class="cyber-label">
                                    <span class="label-text">НАЗВАНИЕ ПРОЕКТА *</span>
                                    <input type="text" name="title" required class="cyber-input" 
                                           placeholder="Например: Интернет-магазин одежды"
                                           value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                                    <div class="input-line"></div>
                                </label>
                            </div>

                            <div class="input-group full-width">
                                <label class="cyber-label">
                                    <span class="label-text">ОПИСАНИЕ ПРОЕКТА *</span>
                                    <textarea name="description" required class="cyber-input" 
                                              rows="6" placeholder="Подробно опишите, что нужно сделать..."><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                    <div class="input-line"></div>
                                </label>
                                <div style="color: var(--text-gray); font-size: 0.8rem; margin-top: 0.3rem;">
                                    Опишите цели, функционал, дизайн и другие важные детали
                                </div>
                            </div>

                            <div class="input-group full-width">
                                <label class="cyber-label">
                                    <span class="label-text">ТРЕБОВАНИЯ К РАЗРАБОТЧИКУ</span>
                                    <textarea name="requirements" class="cyber-input" 
                                              rows="4" placeholder="Какие технологии, опыт, навыки нужны..."><?= isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : '' ?></textarea>
                                    <div class="input-line"></div>
                                </label>
                                <div style="color: var(--text-gray); font-size: 0.8rem; margin-top: 0.3rem;">
                                    Например: PHP, Laravel, опыт работы с API и т.д.
                                </div>
                            </div>

                            <div class="input-group">
                                <label class="cyber-label">
                                    <span class="label-text">БЮДЖЕТ (₽)</span>
                                    <input type="number" name="budget" class="cyber-input" 
                                           placeholder="Например: 100000"
                                           value="<?= isset($_POST['budget']) ? htmlspecialchars($_POST['budget']) : '' ?>">
                                    <div class="input-line"></div>
                                </label>
                            </div>

                            <div class="input-group">
                                <label class="cyber-label">
                                    <span class="label-text">ДЕДЛАЙН</span>
                                    <input type="date" name="deadline" class="cyber-input"
                                           value="<?= isset($_POST['deadline']) ? htmlspecialchars($_POST['deadline']) : '' ?>">
                                    <div class="input-line"></div>
                                </label>
                            </div>
                        </div>

                        <div style="background: rgba(255,215,0,0.05); border: 1px solid rgba(255,215,0,0.2); border-radius: 4px; padding: 1rem; margin: 1rem 0;">
                            <p style="color: var(--text-gray); font-size: 0.9rem;">
                                <i class="fas fa-info-circle" style="color: var(--cyber-blue); margin-right: 0.5rem;"></i>
                                После создания проекта менеджер проверит его и свяжется с вами для уточнения деталей. Затем будет назначен подходящий разработчик.
                            </p>
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="btn-cyber btn-gold" style="min-width: 250px;">
                                <span class="btn-glow"></span>
                                <span class="btn-text">СОЗДАТЬ ПРОЕКТ</span>
                                <i class="fas fa-rocket btn-icon"></i>
                            </button>
                        </div>
                    </form>
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

    <script src="../../assets/js/main.js"></script>
    <script>
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.querySelector('.nav-hologram').classList.toggle('active');
        });
    </script>
</body>
</html>