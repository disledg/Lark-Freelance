<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/functions.php';

// --- Authentication & Authorization ---
if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

// --- Get Project ID ---
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    redirect('index.php');
}

// --- Fetch Project Data with All Related Information ---
$sql = "SELECT
            p.*,
            c.id as client_id,
            c.company_name,
            c.phone as client_phone,
            c.telegram as client_telegram,
            uc.full_name as client_contact_name,
            uc.email as client_email,
            d.id as developer_id,
            d.level as developer_level,
            d.skills as developer_skills,
            d.rating as developer_rating,
            ud.full_name as developer_name,
            ud.email as developer_email,
            d.telegram as developer_telegram,
            m.id as manager_id,
            um.full_name as manager_name
        FROM projects p
        JOIN clients c ON p.client_id = c.id
        JOIN users uc ON c.user_id = uc.id
        LEFT JOIN developers d ON p.developer_id = d.id
        LEFT JOIN users ud ON d.user_id = ud.id
        LEFT JOIN users m ON p.manager_id = m.id
        LEFT JOIN users um ON m.id = um.id
        WHERE p.id = $project_id";

$result = query($sql);
if (!$result || mysqli_num_rows($result) == 0) {
    // Project not found
    $_SESSION['error_message'] = "Проект #$project_id не найден.";
    redirect('index.php');
}
$project = fetch($result);

// --- Fetch all available developers for the assignment dropdown ---
$available_developers = query("
    SELECT d.id, u.full_name, d.level, d.skills, d.rating
    FROM developers d
    JOIN users u ON d.user_id = u.id
    WHERE u.status = 'approved' AND d.is_available = 1
    ORDER BY d.rating DESC, u.full_name ASC
");

// --- Handle Actions (Status Change, Developer Assignment) ---
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Handle Status Change ---
    if (isset($_POST['action']) && $_POST['action'] === 'change_status') {
        $new_status = escape($_POST['new_status']);
        $old_status = $project['status'];

        if ($new_status !== $old_status) {
            $update_sql = "UPDATE projects SET status = '$new_status'";
            if ($new_status === 'completed') {
                $update_sql .= ", completed_at = NOW()";
            }
            $update_sql .= " WHERE id = $project_id";

            if (query($update_sql)) {
                // Log the status change
                addLog($_SESSION['user_id'], 'change_project_status', "Changed project #$project_id status from '$old_status' to '$new_status'");

                // Add to status history
                $history_sql = "INSERT INTO project_status_history (project_id, old_status, new_status, changed_by, comment)
                                VALUES ($project_id, '$old_status', '$new_status', {$_SESSION['user_id']}, 'Status changed by manager')";
                query($history_sql);

                $success_message = "Статус проекта успешно изменен на '" . ucfirst(str_replace('_', ' ', $new_status)) . "'.";

                // If project is completed, update developer stats and availability
                if ($new_status === 'completed' && $project['developer_id']) {
                    query("UPDATE developers SET completed_projects = completed_projects + 1, is_available = 1 WHERE id = {$project['developer_id']}");
                    addNotification($project['developer_id'], 'Проект завершен', "Проект \"{$project['title']}\" был завершен. Отличная работа!");
                } elseif ($new_status === 'in_progress' && $project['developer_id']) {
                    query("UPDATE developers SET is_available = 0 WHERE id = {$project['developer_id']}");
                } elseif (($new_status === 'cancelled' || $new_status === 'new') && $project['developer_id']) {
                    query("UPDATE developers SET is_available = 1 WHERE id = {$project['developer_id']}");
                }

                // Refresh project data
                $result = query($sql);
                $project = fetch($result);
            } else {
                $error_message = "Ошибка при изменении статуса проекта: " . mysqli_error($connection);
            }
        } else {
            $error_message = "Новый статус совпадает с текущим.";
        }
    }

    // --- Handle Developer Assignment ---
    if (isset($_POST['action']) && $_POST['action'] === 'assign_developer') {
        $new_developer_id = isset($_POST['developer_id']) ? (int)$_POST['developer_id'] : 0;
        $old_developer_id = $project['developer_id'];

        if ($new_developer_id > 0 && $new_developer_id != $old_developer_id) {
            // Check if the new developer exists and is available
            $dev_check = query("SELECT id FROM developers WHERE id = $new_developer_id AND is_available = 1");
            if (mysqli_num_rows($dev_check) > 0) {
                // Update the project
                $update_sql = "UPDATE projects SET developer_id = $new_developer_id";
                // If the project is 'new', change status to 'in_progress' automatically
                if ($project['status'] === 'new') {
                    $update_sql .= ", status = 'in_progress'";
                    $new_status = 'in_progress';
                }
                $update_sql .= " WHERE id = $project_id";

                if (query($update_sql)) {
                    // Log the assignment
                    addLog($_SESSION['user_id'], 'assign_developer', "Assigned developer #$new_developer_id to project #$project_id");

                    // Update old developer's availability (if any)
                    if ($old_developer_id) {
                        query("UPDATE developers SET is_available = 1 WHERE id = $old_developer_id");
                    }
                    // Update new developer's availability
                    query("UPDATE developers SET is_available = 0 WHERE id = $new_developer_id");

                    // Add notification for the developer
                    $dev_user_id = fetch(query("SELECT user_id FROM developers WHERE id = $new_developer_id"))['user_id'];
                    addNotification($dev_user_id, 'Новый проект', "Вам назначен новый проект: \"{$project['title']}\".", "/developer/projects/view.php?id=$project_id");

                    $success_message = "Разработчик успешно назначен на проект." . (isset($new_status) ? " Статус автоматически изменен на 'В работе'." : "");

                    // Refresh project data
                    $result = query($sql);
                    $project = fetch($result);
                } else {
                    $error_message = "Ошибка при назначении разработчика: " . mysqli_error($connection);
                }
            } else {
                $error_message = "Выбранный разработчик не найден или в данный момент занят.";
            }
        } elseif ($new_developer_id == 0) {
            $error_message = "Пожалуйста, выберите разработчика из списка.";
        } else {
            $error_message = "Этот разработчик уже назначен на проект.";
        }
    }

    // --- Handle New Message ---
    if (isset($_POST['action']) && $_POST['action'] === 'send_message') {
        $message_text = trim($_POST['message_text']);
        if (!empty($message_text)) {
            $escaped_message = escape($message_text);
            $sender_id = $_SESSION['user_id'];
            $insert_sql = "INSERT INTO messages (project_id, sender_id, message, created_at)
                           VALUES ($project_id, $sender_id, '$escaped_message', NOW())";
            if (query($insert_sql)) {
                addLog($_SESSION['user_id'], 'send_message', "Sent message in project #$project_id");

                // Notify the client and developer
                $client_user_id = fetch(query("SELECT user_id FROM clients WHERE id = {$project['client_id']}"))['user_id'];
                addNotification($client_user_id, 'Новое сообщение в проекте', "Новое сообщение от менеджера по проекту \"{$project['title']}\".", "/client/projects/view.php?id=$project_id");
                if ($project['developer_id']) {
                    $dev_user_id = fetch(query("SELECT user_id FROM developers WHERE id = {$project['developer_id']}"))['user_id'];
                    addNotification($dev_user_id, 'Новое сообщение в проекте', "Новое сообщение от менеджера по проекту \"{$project['title']}\".", "/developer/projects/view.php?id=$project_id");
                }

                $success_message = "Сообщение отправлено.";
                // Refresh page to see the new message
                header("Location: view.php?id=$project_id");
                exit();
            } else {
                $error_message = "Ошибка при отправке сообщения: " . mysqli_error($connection);
            }
        } else {
            $error_message = "Сообщение не может быть пустым.";
        }
    }

    // --- Handle Project Deletion ---
    if (isset($_POST['action']) && $_POST['action'] === 'delete_project') {
        $developer_id_to_free = (int)($project['developer_id'] ?? 0);
        $project_title = $project['title'] ?? '';

        // Delete project (with cascading removal of related messages/history)
        if (query("DELETE FROM projects WHERE id = $project_id")) {
            // Free developer availability (if any)
            if ($developer_id_to_free > 0) {
                query("UPDATE developers SET is_available = 1 WHERE id = $developer_id_to_free");
            }

            // Notify client/developer (best-effort)
            $client_user_id = fetch(query("SELECT user_id FROM clients WHERE id = {$project['client_id']}"))['user_id'] ?? null;
            if ($client_user_id) {
                addNotification(
                    $client_user_id,
                    'Проект удален',
                    "Менеджер удалил ваш проект: \"{$project_title}\"."
                );
            }

            if ($developer_id_to_free > 0) {
                $dev_user_id = fetch(query("SELECT user_id FROM developers WHERE id = $developer_id_to_free"))['user_id'] ?? null;
                if ($dev_user_id) {
                    addNotification(
                        $dev_user_id,
                        'Проект удален',
                        "Менеджер удалил проект: \"{$project_title}\"."
                    );
                }
            }

            addLog($_SESSION['user_id'], 'delete_project', "Deleted project #$project_id");
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Ошибка при удалении проекта: " . mysqli_error($connection);
        }
    }
}

// --- Fetch Messages for this Project ---
$messages = query("
    SELECT m.*, u.full_name as sender_name, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.project_id = $project_id
    ORDER BY m.created_at ASC
");

// Mark messages from others as read
query("UPDATE messages SET is_read = 1, read_at = NOW()
       WHERE project_id = $project_id AND sender_id != {$_SESSION['user_id']}");

// --- Prepare Status Options for Dropdown ---
$statuses = [
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

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Просмотр проекта #<?= $project_id ?> | Lark Freelance</title>
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
            background: rgba(0,243,255,0.1);
            border-left: 3px solid var(--cyber-blue);
        }
        .message-developer {
            background: rgba(157,78,221,0.1);
            border-left: 3px solid var(--neon-purple);
        }
        .message-manager {
            background: rgba(255,215,0,0.1);
            border-left: 3px solid var(--primary-gold);
            margin-left: auto;
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
            font-size: 1rem;
            font-weight: 600;
        }
        .info-value a {
            color: var(--cyber-blue);
            text-decoration: none;
        }
        .info-value a:hover {
            color: var(--primary-gold);
        }
        .developer-card, .client-card {
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        .status-badge {
            background: <?= $status_colors[$project['status']] ?>20;
            color: <?= $status_colors[$project['status']] ?>;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
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
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                </a>
                <div class="nav-hologram">
                    <a href="/" class="nav-link">ГЛАВНАЯ</a>
                    <a href="../dashboard.php" class="nav-link">ДАШБОРД</a>
                    <a href="index.php" class="nav-link active">ПРОЕКТЫ</a>
                    <a href="../requests/developers.php" class="nav-link">ЗАЯВКИ</a>
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
            <!-- Notifications -->
            <?php if ($success_message): ?>
                <div style="background: rgba(0,255,0,0.1); border: 1px solid var(--success); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--success);">
                    <i class="fas fa-check-circle"></i> <?= $success_message ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div style="background: rgba(255, 51, 102, 0.1); border: 1px solid var(--danger); border-radius: 4px; padding: 1rem; margin-bottom: 1.5rem; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle"></i> <?= $error_message ?>
                </div>
            <?php endif; ?>

            <!-- Header with Title and Status -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                <div>
                    <a href="index.php" class="btn-cyber btn-neon" style="padding: 0.5rem 1rem; min-width: auto; margin-right: 1rem;">
                        <i class="fas fa-arrow-left"></i> НАЗАД
                    </a>
                    <h1 class="title-gold" style="display: inline-block; font-size: 2rem;"><?= htmlspecialchars($project['title']) ?></h1>
                </div>
                <div class="status-badge">
                    <i class="fas fa-tag"></i> <?= $statuses[$project['status']] ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem;">
                <!-- Left Column: Project Details & Chat -->
                <div>
                    <!-- Project Description Card -->
                    <div class="cyber-card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> ОПИСАНИЕ ПРОЕКТА</h3>
                        <p style="color: var(--text-gray); line-height: 1.8; white-space: pre-line;">
                            <?= nl2br(htmlspecialchars($project['description'])) ?>
                        </p>
                        <?php if ($project['requirements']): ?>
                            <h3 style="color: var(--cyber-blue); margin: 2rem 0 1rem;"><i class="fas fa-list-ul"></i> ТРЕБОВАНИЯ К РАЗРАБОТЧИКУ</h3>
                            <p style="color: var(--text-gray); line-height: 1.8; white-space: pre-line;">
                                <?= nl2br(htmlspecialchars($project['requirements'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Card -->
                    <div class="cyber-card">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;">
                            <i class="fas fa-comments"></i> КОММУНИКАЦИЯ
                        </h3>
                        <div class="chat-container" id="chatContainer">
                            <?php if (mysqli_num_rows($messages) == 0): ?>
                                <div style="text-align: center; padding: 2rem; color: var(--text-gray);">
                                    <i class="fas fa-comment-dots" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                    <p>Нет сообщений. Напишите клиенту или разработчику.</p>
                                </div>
                            <?php else: ?>
                                <?php while ($msg = fetch($messages)):
                                    $msg_class = '';
                                    if ($msg['sender_role'] == 'manager') $msg_class = 'message-manager';
                                    elseif ($msg['sender_role'] == 'client') $msg_class = 'message-client';
                                    elseif ($msg['sender_role'] == 'developer') $msg_class = 'message-developer';
                                ?>
                                    <div class="message <?= $msg_class ?>">
                                        <div class="message-header">
                                            <span><i class="fas fa-user"></i> <?= htmlspecialchars($msg['sender_name']) ?> (<?= $msg['sender_role'] ?>)</span>
                                            <span><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></span>
                                        </div>
                                        <div class="message-content">
                                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Send Message Form -->
                        <form method="POST" class="cyber-form">
                            <input type="hidden" name="action" value="send_message">
                            <div style="display: flex; gap: 1rem;">
                                <textarea name="message_text" rows="2" class="cyber-input" 
                                          placeholder="Введите сообщение для клиента или разработчика..." required style="flex: 1;"></textarea>
                                <button type="submit" class="btn-cyber btn-gold" style="padding: 0 2rem; min-width: auto;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Actions and Details -->
                <div>
                    <!-- Status Change Card -->
                    <div class="cyber-card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;"><i class="fas fa-tasks"></i> УПРАВЛЕНИЕ СТАТУСОМ</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_status">
                            <div class="input-group" style="margin-bottom: 1rem;">
                                <label class="cyber-label">
                                    <span class="label-text">ТЕКУЩИЙ СТАТУС</span>
                                    <select name="new_status" class="cyber-select">
                                        <?php foreach ($statuses as $key => $name): ?>
                                            <option value="<?= $key ?>" <?= $project['status'] == $key ? 'selected' : '' ?>>
                                                <?= $name ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <button type="submit" class="btn-cyber btn-neon" style="width: 100%;">
                                <i class="fas fa-save"></i> ИЗМЕНИТЬ СТАТУС
                            </button>
                        </form>
                    </div>

                    <!-- Developer Assignment Card -->
                    <div class="cyber-card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;"><i class="fas fa-user-code"></i> НАЗНАЧЕНИЕ РАЗРАБОТЧИКА</h3>
                        <?php if ($project['developer_id']): ?>
                            <div class="developer-card">
                                <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-user-circle" style="font-size: 2rem; color: var(--primary-gold);"></i>
                                    <div>
                                        <h4 style="margin: 0;"><?= htmlspecialchars($project['developer_name']) ?></h4>
                                        <p style="margin: 0; color: var(--text-gray); font-size: 0.85rem;"><?= strtoupper($project['developer_level']) ?></p>
                                    </div>
                                </div>
                                <div class="info-item" style="margin: 0.5rem 0;">
                                    <div class="info-label">Рейтинг</div>
                                    <div class="info-value"><?= $project['developer_rating'] ?> / 5</div>
                                </div>
                                <div class="info-item" style="margin: 0.5rem 0;">
                                    <div class="info-label">Навыки</div>
                                    <div class="info-value"><?= htmlspecialchars($project['developer_skills']) ?></div>
                                </div>
                                <div class="info-item" style="margin: 0.5rem 0;">
                                    <div class="info-label">Email</div>
                                    <div class="info-value"><a href="mailto:<?= $project['developer_email'] ?>"><?= $project['developer_email'] ?></a></div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" style="margin-top: 1rem;">
                            <input type="hidden" name="action" value="assign_developer">
                            <div class="input-group" style="margin-bottom: 1rem;">
                                <label class="cyber-label">
                                    <span class="label-text">НАЗНАЧИТЬ РАЗРАБОТЧИКА</span>
                                    <select name="developer_id" class="cyber-select">
                                        <option value="0">-- Выберите разработчика --</option>
                                        <?php while ($dev = fetch($available_developers)): ?>
                                            <option value="<?= $dev['id'] ?>" <?= $project['developer_id'] == $dev['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dev['full_name']) ?> (<?= strtoupper($dev['level']) ?>, рейтинг: <?= $dev['rating'] ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </label>
                            </div>
                            <button type="submit" class="btn-cyber btn-gold" style="width: 100%;" <?= $project['status'] == 'completed' || $project['status'] == 'cancelled' ? 'disabled' : '' ?>>
                                <i class="fas fa-user-plus"></i> НАЗНАЧИТЬ
                            </button>
                            <?php if ($project['status'] == 'completed' || $project['status'] == 'cancelled'): ?>
                                <p style="color: var(--text-gray); font-size: 0.8rem; margin-top: 0.5rem;">Назначение разработчика недоступно для завершенных или отмененных проектов.</p>
                            <?php endif; ?>
                        </form>
                    </div>

                    <!-- Delete Project Card -->
                    <div class="cyber-card" style="margin-bottom: 2rem; border: 1px solid rgba(255,51,102,0.3);">
                        <h3 style="color: var(--danger); margin-bottom: 1rem;"><i class="fas fa-trash-alt"></i> УДАЛИТЬ ПРОЕКТ</h3>
                        <p style="color: var(--text-gray); font-size: 0.85rem; margin-bottom: 1rem;">
                            Удаление необратимо: будут удалены сообщения и история статусов.
                        </p>
                        <form method="POST" onsubmit="return confirm('Удалить проект #<?= (int)$project_id ?>?');">
                            <input type="hidden" name="action" value="delete_project">
                            <button type="submit" class="btn-cyber" style="width: 100%; background: rgba(255,51,102,0.1); border: 1px solid rgba(255,51,102,0.4); color: var(--danger);">
                                <i class="fas fa-trash-alt"></i> УДАЛИТЬ
                            </button>
                        </form>
                    </div>

                    <!-- Project Details Card -->
                    <div class="cyber-card" style="margin-bottom: 2rem;">
                        <h3 style="color: var(--primary-gold); margin-bottom: 1rem;"><i class="fas fa-chart-line"></i> ДЕТАЛИ ПРОЕКТА</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">ID проекта</div>
                                <div class="info-value">#<?= $project['id'] ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Дата создания</div>
                                <div class="info-value"><?= date('d.m.Y H:i', strtotime($project['created_at'])) ?></div>
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
                            <?php if ($project['completed_at']): ?>
                                <div class="info-item">
                                    <div class="info-label">Дата завершения</div>
                                    <div class="info-value"><?= date('d.m.Y H:i', strtotime($project['completed_at'])) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($project['manager_name']): ?>
                                <div class="info-item">
                                    <div class="info-label">Менеджер</div>
                                    <div class="info-value"><?= htmlspecialchars($project['manager_name']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Client Details Card -->
                    <div class="cyber-card">
                        <h3 style="color: var(--cyber-blue); margin-bottom: 1rem;"><i class="fas fa-building"></i> ИНФОРМАЦИЯ О КЛИЕНТЕ</h3>
                        <div class="client-card">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                <i class="fas fa-user-tie" style="font-size: 2rem; color: var(--cyber-blue);"></i>
                                <div>
                                    <h4 style="margin: 0;"><?= htmlspecialchars($project['company_name']) ?></h4>
                                    <p style="margin: 0; color: var(--text-gray); font-size: 0.85rem;">Контакт: <?= htmlspecialchars($project['client_contact_name']) ?></p>
                                </div>
                            </div>
                            <div class="info-item" style="margin: 0.5rem 0;">
                                <div class="info-label">Email</div>
                                <div class="info-value"><a href="mailto:<?= $project['client_email'] ?>"><?= $project['client_email'] ?></a></div>
                            </div>
                            <?php if ($project['client_phone']): ?>
                                <div class="info-item" style="margin: 0.5rem 0;">
                                    <div class="info-label">Телефон</div>
                                    <div class="info-value"><?= htmlspecialchars($project['client_phone']) ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if ($project['client_telegram']): ?>
                                <div class="info-item" style="margin: 0.5rem 0;">
                                    <div class="info-label">Telegram</div>
                                    <div class="info-value"><?= htmlspecialchars($project['client_telegram']) ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
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
        // Scroll chat to bottom on page load
        const chatContainer = document.getElementById('chatContainer');
        if (chatContainer) {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    </script>
</body>
</html>