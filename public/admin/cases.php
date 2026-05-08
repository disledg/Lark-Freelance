<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/cases.php';

if (!isManager() && !isAdmin()) {
    redirect('/admin/login.php');
}

$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$case_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Обработка POST запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_case'])) {
        $data = [
            'project_id' => (int)$_POST['project_id'],
            'title' => escape($_POST['title']),
            'company_name' => escape($_POST['company_name']),
            'description' => escape($_POST['description']),
            'deadline' => escape($_POST['deadline']),
            'budget' => isset($_POST['budget']) && is_numeric($_POST['budget']) ? (float)$_POST['budget'] : null,
            'technologies' => escape($_POST['technologies']),
            'challenges' => escape($_POST['challenges']),
            'results' => escape($_POST['results']),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        if (createCase($data)) {
            addLog($_SESSION['user_id'], 'create_case', "Created case: {$data['title']}");
            redirect('cases.php?success=created');
        } else {
            $error = 'Ошибка создания кейса';
        }
    }

    if (isset($_POST['update_case'])) {
        $id = (int)$_POST['case_id'];
        $data = [
            'title' => escape($_POST['title']),
            'company_name' => escape($_POST['company_name']),
            'description' => escape($_POST['description']),
            'deadline' => escape($_POST['deadline']),
            'budget' => isset($_POST['budget']) && is_numeric($_POST['budget']) ? (float)$_POST['budget'] : null,
            'technologies' => escape($_POST['technologies']),
            'challenges' => escape($_POST['challenges']),
            'results' => escape($_POST['results']),
            'is_featured' => isset($_POST['is_featured']) ? 1 : 0
        ];

        if (updateCase($id, $data)) {
            addLog($_SESSION['user_id'], 'update_case', "Updated case ID: $id");
            redirect('cases.php?success=updated');
        } else {
            $error = 'Ошибка обновления кейса';
        }
    }

    if (isset($_POST['delete_case'])) {
        $id = (int)$_POST['case_id'];
        if (deleteCase($id)) {
            addLog($_SESSION['user_id'], 'delete_case', "Deleted case ID: $id");
            redirect('cases.php?success=deleted');
        } else {
            $error = 'Ошибка удаления кейса';
        }
    }
}

// Получаем данные в зависимости от действия
$cases = null;
$case = null;
$completed_projects = null;

if ($action === 'list') {
    $cases = getAllCases();
} elseif ($action === 'edit' && $case_id > 0) {
    $case = getCaseById($case_id);
} elseif ($action === 'create') {
    $completed_projects = getCompletedProjectsWithoutCases();
}

// Статистика
$stats = [
    'total' => 0,
    'featured' => 0
];

if ($cases) {
    $stats['total'] = mysqli_num_rows($cases);
    mysqli_data_seek($cases, 0);
    while ($c = mysqli_fetch_assoc($cases)) {
        if ($c['is_featured']) $stats['featured']++;
    }
    mysqli_data_seek($cases, 0);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление кейсами | Lark Freelance</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes"></div>
    </div>

    <?php $adminActivePage = 'cases'; ?>
    <?php require_once __DIR__ . '/includes/admin-header.php'; ?>

    <div class="container" style="padding-top: 8rem;">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php if ($_GET['success'] === 'created'): ?>
                    <i class="fas fa-check"></i> Кейс успешно создан!
                <?php elseif ($_GET['success'] === 'updated'): ?>
                    <i class="fas fa-check"></i> Кейс успешно обновлен!
                <?php elseif ($_GET['success'] === 'deleted'): ?>
                    <i class="fas fa-check"></i> Кейс успешно удален!
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="cyber-card" style="margin-bottom: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h1 class="form-title">
                    <i class="fas fa-trophy"></i> Управление успешными кейсами
                </h1>
                <div style="display: flex; gap: 1rem;">
                    <a href="?action=list" class="btn-cyber btn-gold" style="text-decoration: none;">
                        <span class="btn-glow"></span>
                        <span class="btn-text"><i class="fas fa-list"></i> Все кейсы</span>
                    </a>
                    <a href="?action=create" class="btn-cyber btn-gold" style="text-decoration: none;">
                        <span class="btn-glow"></span>
                        <span class="btn-text"><i class="fas fa-plus"></i> Создать кейс</span>
                    </a>
                </div>
            </div>

            <!-- Статистика -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total'] ?></div>
                    <div class="stat-label">Всего кейсов</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['featured'] ?></div>
                    <div class="stat-label">Избранных</div>
                </div>
            </div>
        </div>

        <?php if ($action === 'list' && $cases): ?>
            <div class="cyber-card">
                <div class="table-responsive">
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>Проект</th>
                                <th>Компания</th>
                                <th>Бюджет</th>
                                <th>Избранный</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($case = mysqli_fetch_assoc($cases)): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($case['title']) ?></strong>
                                        <br><small style="color: var(--text-gray);">
                                            <?= htmlspecialchars(substr($case['description'], 0, 100)) ?>...
                                        </small>
                                    </td>
                                    <td><?= htmlspecialchars($case['company_name']) ?></td>
                                    <td>
                                        <?php if ($case['budget']): ?>
                                            <span class="badge badge-gold">
                                                <?= number_format($case['budget'], 0, ',', ' ') ?> ₽
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($case['is_featured']): ?>
                                            <span class="badge badge-success">
                                                <i class="fas fa-star"></i> Да
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-gray">Нет</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="?action=edit&id=<?= $case['id'] ?>" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem;">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Удалить кейс?')">
                                                <input type="hidden" name="case_id" value="<?= $case['id'] ?>">
                                                <button type="submit" name="delete_case" class="btn-cyber btn-neon" style="padding: 0.3rem 1rem; min-width: auto; font-size: 0.8rem; background: rgba(255,51,102,0.1); color: var(--danger); border-color: rgba(255,51,102,0.4);">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($action === 'create'): ?>
            <div class="cyber-card">
                <h2 class="form-title">
                    <i class="fas fa-plus"></i> Создать новый кейс
                </h2>

                <?php if ($completed_projects && mysqli_num_rows($completed_projects) > 0): ?>
                    <form method="POST">
                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">ПРОЕКТ</span>
                                <select name="project_id" required class="cyber-input">
                                    <option value="">Выберите завершенный проект</option>
                                    <?php while ($project = mysqli_fetch_assoc($completed_projects)): ?>
                                        <option value="<?= $project['id'] ?>">
                                            <?= htmlspecialchars($project['title']) ?> - <?= htmlspecialchars($project['company_name'] ?? $project['client_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </label>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">ЗАГОЛОВОК КЕЙСА</span>
                                <input type="text" name="title" required class="cyber-input" placeholder="Например: Разработка интернет-магазина">
                            </label>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">КОМПАНИЯ</span>
                                <input type="text" name="company_name" required class="cyber-input" placeholder="Название компании">
                            </label>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">ОПИСАНИЕ ПРОЕКТА</span>
                                <textarea name="description" required class="cyber-input" rows="4" placeholder="Подробное описание проекта"></textarea>
                            </label>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">СРОКИ</span>
                                <input type="text" name="deadline" class="cyber-input" placeholder="Например: 45 дней">
                            </label>
                            <label class="cyber-label">
                                <span class="label-text">БЮДЖЕТ (₽)</span>
                                <input type="number" name="budget" class="cyber-input" placeholder="50000">
                            </label>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">ТЕХНОЛОГИИ</span>
                                <input type="text" name="technologies" class="cyber-input" placeholder="PHP, Laravel, MySQL, Redis">
                            </label>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">ВЫЗОВЫ</span>
                                <textarea name="challenges" class="cyber-input" rows="3" placeholder="Технические сложности и проблемы"></textarea>
                            </label>
                        </div>

                        <div style="margin-bottom: 1.5rem;">
                            <label class="cyber-label">
                                <span class="label-text">РЕЗУЛЬТАТЫ</span>
                                <textarea name="results" class="cyber-input" rows="3" placeholder="Достигнутые результаты и метрики"></textarea>
                            </label>
                        </div>

                        <div style="margin-bottom: 2rem;">
                            <label class="cyber-checkbox">
                                <input type="checkbox" name="is_featured" value="1">
                                <span class="checkmark"></span>
                                <span class="checkbox-text">Показывать на главной странице</span>
                            </label>
                        </div>

                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" name="create_case" class="btn-cyber btn-gold">
                                <span class="btn-glow"></span>
                                <span class="btn-text">СОЗДАТЬ КЕЙС</span>
                            </button>
                            <a href="?action=list" class="btn-cyber btn-secondary">ОТМЕНА</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Нет завершенных проектов без кейсов. Сначала завершите какие-нибудь проекты.
                    </div>
                    <a href="projects/index.php" class="btn-cyber btn-primary">ПЕРЕЙТИ К ПРОЕКТАМ</a>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'edit' && $case): ?>
            <div class="cyber-card">
                <h2 class="form-title">
                    <i class="fas fa-edit"></i> Редактировать кейс
                </h2>

                <form method="POST">
                    <input type="hidden" name="case_id" value="<?= $case['id'] ?>">

                    <div style="margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">ЗАГОЛОВОК КЕЙСА</span>
                            <input type="text" name="title" required class="cyber-input" value="<?= htmlspecialchars($case['title']) ?>">
                        </label>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">КОМПАНИЯ</span>
                            <input type="text" name="company_name" required class="cyber-input" value="<?= htmlspecialchars($case['company_name']) ?>">
                        </label>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">ОПИСАНИЕ ПРОЕКТА</span>
                            <textarea name="description" required class="cyber-input" rows="4"><?= htmlspecialchars($case['description']) ?></textarea>
                        </label>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">СРОКИ</span>
                            <input type="text" name="deadline" class="cyber-input" value="<?= htmlspecialchars($case['deadline'] ?? '') ?>">
                        </label>
                        <label class="cyber-label">
                            <span class="label-text">БЮДЖЕТ (₽)</span>
                            <input type="number" name="budget" class="cyber-input" value="<?= $case['budget'] ?? '' ?>">
                        </label>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">ТЕХНОЛОГИИ</span>
                            <input type="text" name="technologies" class="cyber-input" value="<?= htmlspecialchars($case['technologies'] ?? '') ?>">
                        </label>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">ВЫЗОВЫ</span>
                            <textarea name="challenges" class="cyber-input" rows="3"><?= htmlspecialchars($case['challenges'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div style="margin-bottom: 1.5rem;">
                        <label class="cyber-label">
                            <span class="label-text">РЕЗУЛЬТАТЫ</span>
                            <textarea name="results" class="cyber-input" rows="3"><?= htmlspecialchars($case['results'] ?? '') ?></textarea>
                        </label>
                    </div>

                    <div style="margin-bottom: 2rem;">
                        <label class="cyber-checkbox">
                            <input type="checkbox" name="is_featured" value="1" <?= $case['is_featured'] ? 'checked' : '' ?>>
                            <span class="checkmark"></span>
                            <span class="checkbox-text">Показывать на главной странице</span>
                        </label>
                    </div>

                    <div style="display: flex; gap: 1rem;">
                        <button type="submit" name="update_case" class="btn-cyber btn-gold">
                            <span class="btn-glow"></span>
                            <span class="btn-text">СОХРАНИТЬ</span>
                        </button>
                        <a href="?action=list" class="btn-cyber btn-secondary">ОТМЕНА</a>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .stat-card {
            background: var(--dark-card);
            border: 1px solid var(--dark-border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-gold);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--text-gray);
            font-size: 0.9rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }
        .alert-danger {
            background: rgba(255, 51, 102, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }
        .alert-warning {
            background: rgba(255, 170, 0, 0.1);
            border: 1px solid var(--warning);
            color: var(--warning);
        }
        .table-responsive {
            overflow-x: auto;
        }
        .cyber-table {
            width: 100%;
            border-collapse: collapse;
        }
        .cyber-table th,
        .cyber-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--dark-border);
            text-align: left;
        }
        .cyber-table th {
            background: var(--dark-card);
            color: var(--primary-gold);
            font-weight: 600;
        }
        .btn-sm {
            padding: 0.5rem;
            font-size: 0.8rem;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .badge-gold {
            background: rgba(255, 215, 0, 0.2);
            color: var(--primary-gold);
        }
        .badge-success {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success);
        }
        .badge-gray {
            background: rgba(160, 160, 192, 0.2);
            color: var(--text-gray);
        }
        /* Кнопки */
        .btn-cyber {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: 1px solid var(--primary-gold);
            background: transparent;
            color: var(--primary-gold);
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            border-radius: 2px;
            text-decoration: none;
        }
        .btn-cyber:hover {
            background: var(--primary-gold);
            color: var(--dark-bg);
            box-shadow: 0 0 20px var(--primary-gold);
            transform: translateY(-2px);
        }
        .btn-cyber .btn-glow {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-cyber:hover .btn-glow {
            left: 100%;
        }
        .btn-cyber .btn-text {
            position: relative;
            z-index: 1;
        }
        .btn-cyber.btn-secondary {
            border-color: var(--cyber-blue);
            color: var(--cyber-blue);
        }
        .btn-cyber.btn-secondary:hover {
            background: var(--cyber-blue);
            box-shadow: 0 0 20px var(--cyber-blue);
        }
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        .cyber-footer {
            margin-top: 4rem;
            padding-bottom: 5rem;
        }
    </style>

    <footer class="cyber-footer" style="margin-top: 4rem;">
        <div class="container">
            <div class="footer-bottom">
                <div class="copyright">© <?= date('Y') ?> LARK FREELANCE</div>
            </div>
        </div>
    </footer>
</body>
</html>