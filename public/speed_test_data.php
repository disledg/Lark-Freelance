<?php
/**
 * Скрипт для заполнения базы данных тестовыми данными
 * Запустите: http://localhost:8080/seed_test_data.php
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Заполнение тестовыми данными</title>
    <style>
        body {
            font-family: monospace;
            background: #0a0a0f;
            color: #fff;
            padding: 20px;
        }
        .success { color: #00ff88; }
        .error { color: #ff3366; }
        .info { color: #00f3ff; }
        hr { border-color: rgba(255,215,0,0.3); margin: 20px 0; }
        pre {
            background: rgba(0,0,0,0.5);
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1 style='color: #ffd700;'>🌱 Заполнение тестовыми данными</h1>
    <hr>";

echo "<h2>📊 Начинаем заполнение базы данных...</h2>";

// Проверяем подключение
if (!$connection) {
    die("<div class='error'>❌ Ошибка подключения к базе данных!</div>");
}

// Очищаем существующие данные (опционально)
echo "<h3>🗑️ Очистка существующих данных...</h3>";
query("SET FOREIGN_KEY_CHECKS = 0");
query("TRUNCATE TABLE messages");
query("TRUNCATE TABLE project_status_history");
query("TRUNCATE TABLE notifications");
query("TRUNCATE TABLE logs");
query("TRUNCATE TABLE projects");
query("TRUNCATE TABLE developers");
query("TRUNCATE TABLE clients");
query("TRUNCATE TABLE users");
query("TRUNCATE TABLE developer_applications");
query("TRUNCATE TABLE client_applications");
query("SET FOREIGN_KEY_CHECKS = 1");
echo "<div class='success'>✓ Все таблицы очищены</div>";

// ==================== 1. СОЗДАНИЕ ПОЛЬЗОВАТЕЛЕЙ ====================
echo "<h3>👥 Создание пользователей...</h3>";

$users_data = [
    // Администраторы и менеджеры
    [
        'username' => 'admin',
        'email' => 'admin@lark.ru',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'full_name' => 'Администратор Системы',
        'role' => 'admin',
        'status' => 'approved'
    ],
    [
        'username' => 'manager1',
        'email' => 'manager1@lark.ru',
        'password' => password_hash('manager123', PASSWORD_DEFAULT),
        'full_name' => 'Иван Менеджеров',
        'role' => 'manager',
        'status' => 'approved'
    ],
    [
        'username' => 'manager2',
        'email' => 'manager2@lark.ru',
        'password' => password_hash('manager123', PASSWORD_DEFAULT),
        'full_name' => 'Анна Смирнова',
        'role' => 'manager',
        'status' => 'approved'
    ],
    
    // Клиенты
    [
        'username' => 'techpro',
        'email' => 'info@techpro.ru',
        'password' => password_hash('client123', PASSWORD_DEFAULT),
        'full_name' => 'Смирнов Алексей',
        'role' => 'client',
        'status' => 'approved'
    ],
    [
        'username' => 'romashka',
        'email' => 'info@romashka.ru',
        'password' => password_hash('client123', PASSWORD_DEFAULT),
        'full_name' => 'Петрова Елена',
        'role' => 'client',
        'status' => 'approved'
    ],
    [
        'username' => 'stroyinvest',
        'email' => 'info@stroyinvest.ru',
        'password' => password_hash('client123', PASSWORD_DEFAULT),
        'full_name' => 'Кузнецов Дмитрий',
        'role' => 'client',
        'status' => 'approved'
    ],
    [
        'username' => 'itsolutions',
        'email' => 'contact@itsolutions.ru',
        'password' => password_hash('client123', PASSWORD_DEFAULT),
        'full_name' => 'Васильева Мария',
        'role' => 'client',
        'status' => 'pending'
    ],
    
    // Разработчики
    [
        'username' => 'ivan_dev',
        'email' => 'ivan.petrov@dev.ru',
        'password' => password_hash('dev123', PASSWORD_DEFAULT),
        'full_name' => 'Иван Петров',
        'role' => 'developer',
        'status' => 'approved'
    ],
    [
        'username' => 'anna_dev',
        'email' => 'anna.smirnova@dev.ru',
        'password' => password_hash('dev123', PASSWORD_DEFAULT),
        'full_name' => 'Анна Смирнова',
        'role' => 'developer',
        'status' => 'approved'
    ],
    [
        'username' => 'dmitry_dev',
        'email' => 'dmitry.ivanov@dev.ru',
        'password' => password_hash('dev123', PASSWORD_DEFAULT),
        'full_name' => 'Дмитрий Иванов',
        'role' => 'developer',
        'status' => 'approved'
    ],
    [
        'username' => 'elena_dev',
        'email' => 'elena.kozlov@dev.ru',
        'password' => password_hash('dev123', PASSWORD_DEFAULT),
        'full_name' => 'Елена Козлова',
        'role' => 'developer',
        'status' => 'approved'
    ],
    [
        'username' => 'maxim_dev',
        'email' => 'maxim.sokolov@dev.ru',
        'password' => password_hash('dev123', PASSWORD_DEFAULT),
        'full_name' => 'Максим Соколов',
        'role' => 'developer',
        'status' => 'pending'
    ],
];

$user_ids = [];
$client_ids = [];
$developer_ids = [];

foreach ($users_data as $user) {
    $sql = "INSERT INTO users (username, email, password, full_name, role, status, created_at, last_login) 
            VALUES (
                '{$user['username']}', 
                '{$user['email']}', 
                '{$user['password']}', 
                '{$user['full_name']}', 
                '{$user['role']}', 
                '{$user['status']}',
                NOW(),
                " . ($user['status'] == 'approved' ? "NOW()" : "NULL") . "
            )";
    
    if (query($sql)) {
        $user_id = insert_id();
        $user_ids[$user['email']] = $user_id;
        echo "<div class='success'>✓ Создан пользователь: {$user['full_name']} ({$user['role']})</div>";
    } else {
        echo "<div class='error'>✗ Ошибка создания пользователя: " . mysqli_error($connection) . "</div>";
    }
}

// ==================== 2. СОЗДАНИЕ ПРОФИЛЕЙ КЛИЕНТОВ ====================
echo "<h3>🏢 Создание профилей клиентов...</h3>";

$clients_data = [
    [
        'email' => 'info@techpro.ru',
        'company_name' => 'ООО ТехноПроект',
        'phone' => '+7 (495) 123-45-67',
        'telegram' => '@techpro_company',
        'company_site' => 'https://techpro.ru'
    ],
    [
        'email' => 'info@romashka.ru',
        'company_name' => 'ООО Ромашка',
        'phone' => '+7 (812) 234-56-78',
        'telegram' => '@romashka_company',
        'company_site' => 'https://romashka.ru'
    ],
    [
        'email' => 'info@stroyinvest.ru',
        'company_name' => 'ООО СтройИнвест',
        'phone' => '+7 (343) 345-67-89',
        'telegram' => '@stroyinvest',
        'company_site' => 'https://stroyinvest.ru'
    ],
    [
        'email' => 'contact@itsolutions.ru',
        'company_name' => 'IT Solutions',
        'phone' => '+7 (499) 456-78-90',
        'telegram' => '@itsolutions',
        'company_site' => 'https://itsolutions.ru'
    ],
];

foreach ($clients_data as $client) {
    if (isset($user_ids[$client['email']])) {
        $user_id = $user_ids[$client['email']];
        $sql = "INSERT INTO clients (user_id, company_name, phone, telegram, company_site) 
                VALUES ($user_id, '{$client['company_name']}', '{$client['phone']}', '{$client['telegram']}', '{$client['company_site']}')";
        
        if (query($sql)) {
            $client_id = insert_id();
            $client_ids[$client['company_name']] = $client_id;
            echo "<div class='success'>✓ Создан клиент: {$client['company_name']}</div>";
        } else {
            echo "<div class='error'>✗ Ошибка создания клиента: " . mysqli_error($connection) . "</div>";
        }
    }
}

// ==================== 3. СОЗДАНИЕ ПРОФИЛЕЙ РАЗРАБОТЧИКОВ ====================
echo "<h3>👨‍💻 Создание профилей разработчиков...</h3>";

$developers_data = [
    [
        'email' => 'ivan.petrov@dev.ru',
        'level' => 'junior',
        'skills' => json_encode(['PHP', 'MySQL', 'JavaScript', 'HTML', 'CSS']),
        'experience' => '2 года фриланса. Разрабатывал лендинги, интернет-магазины на OpenCart, небольшие CRM системы.',
        'portfolio' => 'https://github.com/ivanpetrov',
        'telegram' => '@ivan_dev',
        'github' => 'https://github.com/ivanpetrov',
        'rating' => 3.5,
        'completed_projects' => 3,
        'is_available' => 1
    ],
    [
        'email' => 'anna.smirnova@dev.ru',
        'level' => 'middle',
        'skills' => json_encode(['PHP', 'Laravel', 'MySQL', 'PostgreSQL', 'Redis', 'Docker']),
        'experience' => '5 лет коммерческой разработки. Участвовала в разработке высоконагруженных API, работала с микросервисной архитектурой.',
        'portfolio' => 'https://github.com/annasmirnova',
        'telegram' => '@anna_dev',
        'github' => 'https://github.com/annasmirnova',
        'rating' => 4.2,
        'completed_projects' => 12,
        'is_available' => 0
    ],
    [
        'email' => 'dmitry.ivanov@dev.ru',
        'level' => 'junior',
        'skills' => json_encode(['Python', 'Django', 'Flask', 'PostgreSQL', 'Docker']),
        'experience' => '1.5 года разработки на Python. Создавал телеграм-ботов, парсеры, REST API.',
        'portfolio' => 'https://github.com/dmitryivanov',
        'telegram' => '@dmitry_dev',
        'github' => 'https://github.com/dmitryivanov',
        'rating' => 3.8,
        'completed_projects' => 4,
        'is_available' => 1
    ],
    [
        'email' => 'elena.kozlov@dev.ru',
        'level' => 'middle',
        'skills' => json_encode(['UI/UX', 'Figma', 'Adobe XD', 'Photoshop', 'Illustrator', 'HTML/CSS']),
        'experience' => '4 года в веб-дизайне. Разрабатывала дизайн для 20+ проектов, включая мобильные приложения и веб-сервисы.',
        'portfolio' => 'https://behance.net/elenakozlova',
        'telegram' => '@elena_design',
        'github' => '',
        'rating' => 4.5,
        'completed_projects' => 18,
        'is_available' => 1
    ],
    [
        'email' => 'maxim.sokolov@dev.ru',
        'level' => 'junior',
        'skills' => json_encode(['React', 'Vue.js', 'Node.js', 'MongoDB', 'Express']),
        'experience' => '1 год разработки на React. Делал несколько pet-проектов: todo-лист, погодное приложение.',
        'portfolio' => 'https://github.com/maximsokolov',
        'telegram' => '@maxim_dev',
        'github' => 'https://github.com/maximsokolov',
        'rating' => 0,
        'completed_projects' => 0,
        'is_available' => 1
    ],
];

foreach ($developers_data as $dev) {
    if (isset($user_ids[$dev['email']])) {
        $user_id = $user_ids[$dev['email']];
        $sql = "INSERT INTO developers (user_id, level, skills, experience, portfolio, telegram, github, rating, completed_projects, is_available) 
                VALUES (
                    $user_id, 
                    '{$dev['level']}', 
                    '{$dev['skills']}', 
                    '{$dev['experience']}', 
                    '{$dev['portfolio']}', 
                    '{$dev['telegram']}', 
                    '{$dev['github']}', 
                    {$dev['rating']}, 
                    {$dev['completed_projects']}, 
                    {$dev['is_available']}
                )";
        
        if (query($sql)) {
            $developer_id = insert_id();
            $developer_ids[$dev['email']] = $developer_id;
            echo "<div class='success'>✓ Создан разработчик: {$dev['email']} ({$dev['level']})</div>";
        } else {
            echo "<div class='error'>✗ Ошибка создания разработчика: " . mysqli_error($connection) . "</div>";
        }
    }
}

// ==================== 4. СОЗДАНИЕ ПРОЕКТОВ ====================
echo "<h3>📁 Создание проектов...</h3>";

$projects_data = [
    [
        'title' => 'Интернет-магазин для ООО ТехноПроект',
        'description' => 'Разработка полнофункционального интернет-магазина с каталогом товаров, корзиной, личным кабинетом и интеграцией с платежными системами.',
        'requirements' => 'Опыт работы с PHP 8.x, Laravel, MySQL, JavaScript, Vue.js. Знание REST API, опыт интеграции с платежными системами.',
        'client' => 'ООО ТехноПроект',
        'developer' => 'anna.smirnova@dev.ru',
        'budget' => 150000,
        'deadline_days' => 45,
        'status' => 'in_progress',
        'created_days_ago' => 10
    ],
    [
        'title' => 'CRM система для СтройИнвест',
        'description' => 'Разработка CRM-системы для управления клиентами, проектами и задачами. Необходим функционал отчетности и аналитики.',
        'requirements' => 'Python, Django, PostgreSQL, опыт работы с API, знание React или Vue.js.',
        'client' => 'ООО СтройИнвест',
        'developer' => 'dmitry.ivanov@dev.ru',
        'budget' => 200000,
        'deadline_days' => 60,
        'status' => 'new',
        'created_days_ago' => 2
    ],
    [
        'title' => 'Мобильное приложение для фитнес-центра',
        'description' => 'Разработка кроссплатформенного мобильного приложения для записи на тренировки, отслеживания прогресса и общения с тренерами.',
        'requirements' => 'React Native или Flutter, опыт работы с Firebase, умение работать с дизайнерами.',
        'client' => 'ООО Ромашка',
        'developer' => 'elena.kozlov@dev.ru',
        'budget' => 180000,
        'deadline_days' => 50,
        'status' => 'in_progress',
        'created_days_ago' => 15
    ],
    [
        'title' => 'Корпоративный портал',
        'description' => 'Разработка корпоративного портала с новостями, документацией, календарем событий и системой управления задачами.',
        'requirements' => 'PHP, Laravel, MySQL, опыт работы с API, знание JavaScript.',
        'client' => 'IT Solutions',
        'developer' => null,
        'budget' => 250000,
        'deadline_days' => 75,
        'status' => 'new',
        'created_days_ago' => 1
    ],
    [
        'title' => 'Лендинг для IT-конференции',
        'description' => 'Разработка современного лендинга для IT-конференции с возможностью регистрации участников и программой мероприятия.',
        'requirements' => 'HTML, CSS, JavaScript, знание Bootstrap или Tailwind, опыт работы с формами.',
        'client' => 'IT Solutions',
        'developer' => 'ivan.petrov@dev.ru',
        'budget' => 35000,
        'deadline_days' => 20,
        'status' => 'completed',
        'completed_days_ago' => 5,
        'created_days_ago' => 25
    ],
    [
        'title' => 'Админ-панель для управления контентом',
        'description' => 'Разработка админ-панели для управления контентом сайта: новости, статьи, галерея, пользователи.',
        'requirements' => 'React, Redux, Node.js, MongoDB, опыт работы с REST API.',
        'client' => 'ООО ТехноПроект',
        'developer' => 'maxim.sokolov@dev.ru',
        'budget' => 95000,
        'deadline_days' => 30,
        'status' => 'cancelled',
        'created_days_ago' => 8
    ],
    [
        'title' => 'Telegram-бот для доставки еды',
        'description' => 'Разработка Telegram-бота для заказа еды с возможностью выбора блюд, корзины и оплаты.',
        'requirements' => 'Python, aiogram, Django, PostgreSQL, опыт работы с платежными системами.',
        'client' => 'ООО Ромашка',
        'developer' => null,
        'budget' => 75000,
        'deadline_days' => 40,
        'status' => 'new',
        'created_days_ago' => 3
    ],
];

foreach ($projects_data as $project) {
    if (isset($client_ids[$project['client']])) {
        $client_id = $client_ids[$project['client']];
        $developer_id = $project['developer'] && isset($developer_ids[$project['developer']]) ? $developer_ids[$project['developer']] : 'NULL';
        
        $budget = $project['budget'] ? $project['budget'] : 'NULL';
        $deadline = $project['deadline_days'] ? "DATE_ADD(NOW(), INTERVAL {$project['deadline_days']} DAY)" : 'NULL';
        $created_at = "DATE_SUB(NOW(), INTERVAL {$project['created_days_ago']} DAY)";
        
        $sql = "INSERT INTO projects (title, description, requirements, client_id, developer_id, budget, deadline, status, created_at) 
                VALUES (
                    '{$project['title']}',
                    '{$project['description']}',
                    '{$project['requirements']}',
                    $client_id,
                    $developer_id,
                    $budget,
                    $deadline,
                    '{$project['status']}',
                    $created_at
                )";
        
        if (query($sql)) {
            $project_id = insert_id();
            echo "<div class='success'>✓ Создан проект: {$project['title']} (статус: {$project['status']})</div>";
            
            // Обновляем completed_at для завершенных проектов
            if ($project['status'] == 'completed' && isset($project['completed_days_ago'])) {
                $completed_at = "DATE_SUB(NOW(), INTERVAL {$project['completed_days_ago']} DAY)";
                query("UPDATE projects SET completed_at = $completed_at WHERE id = $project_id");
            }
            
            // Добавляем историю статусов для проектов в работе
            if ($project['status'] == 'in_progress') {
                $manager_id = 2; // ID менеджера
                $status_date = "DATE_SUB(NOW(), INTERVAL {$project['created_days_ago']} DAY)";
                query("INSERT INTO project_status_history (project_id, old_status, new_status, changed_by, created_at) 
                       VALUES ($project_id, 'new', 'in_progress', $manager_id, DATE_ADD($status_date, INTERVAL 2 DAY))");
            }
            
            if ($project['status'] == 'completed' && isset($project['completed_days_ago'])) {
                $manager_id = 2;
                $created_date = "DATE_SUB(NOW(), INTERVAL {$project['created_days_ago']} DAY)";
                $in_progress_date = "DATE_ADD($created_date, INTERVAL 2 DAY)";
                $completed_date = "DATE_SUB(NOW(), INTERVAL {$project['completed_days_ago']} DAY)";
                
                query("INSERT INTO project_status_history (project_id, old_status, new_status, changed_by, created_at) 
                       VALUES ($project_id, 'new', 'in_progress', $manager_id, $in_progress_date)");
                query("INSERT INTO project_status_history (project_id, old_status, new_status, changed_by, created_at) 
                       VALUES ($project_id, 'in_progress', 'completed', $manager_id, $completed_date)");
            }
            
        } else {
            echo "<div class='error'>✗ Ошибка создания проекта: " . mysqli_error($connection) . "</div>";
        }
    }
}

// ==================== 5. СОЗДАНИЕ ЗАЯВОК ====================
echo "<h3>📝 Создание заявок...</h3>";

// Заявки разработчиков
$dev_applications = [
    [
        'full_name' => 'Сергей Новиков',
        'email' => 'sergey.novikov@email.ru',
        'level' => 'junior',
        'skills' => 'JavaScript, React, HTML, CSS, Git',
        'experience' => 'Окончил курсы по React. Сделал несколько пет-проектов: todo-приложение, портфолио.',
        'portfolio' => 'https://github.com/sergeynovikov',
        'telegram' => '@sergey_dev',
        'github' => 'https://github.com/sergeynovikov',
        'status' => 'new'
    ],
    [
        'full_name' => 'Ольга Кузнецова',
        'email' => 'olga.kuznetsova@email.ru',
        'level' => 'middle',
        'skills' => 'PHP, Laravel, MySQL, Redis, Docker, Kubernetes',
        'experience' => '6 лет коммерческой разработки. Работала в крупных e-commerce проектах.',
        'portfolio' => 'https://github.com/olgakuznetsova',
        'telegram' => '@olga_dev',
        'github' => 'https://github.com/olgakuznetsova',
        'status' => 'approved'
    ],
];

foreach ($dev_applications as $app) {
    $created_at = $app['status'] == 'new' ? 'NOW()' : 'DATE_SUB(NOW(), INTERVAL 5 DAY)';
    $sql = "INSERT INTO developer_applications (full_name, email, level, skills, experience, portfolio, telegram, github, status, created_at) 
            VALUES (
                '{$app['full_name']}',
                '{$app['email']}',
                '{$app['level']}',
                '{$app['skills']}',
                '{$app['experience']}',
                '{$app['portfolio']}',
                '{$app['telegram']}',
                '{$app['github']}',
                '{$app['status']}',
                $created_at
            )";
    
    if (query($sql)) {
        echo "<div class='success'>✓ Создана заявка разработчика: {$app['full_name']}</div>";
    }
}

// Заявки клиентов
$client_applications = [
    [
        'company_name' => 'ООО Новые Технологии',
        'contact_person' => 'Андрей Воронов',
        'email' => 'info@newtech.ru',
        'phone' => '+7 (495) 987-65-43',
        'project_description' => 'Нужен сайт-визитка для IT-компании с портфолио и формой обратной связи.',
        'budget_range' => '30000-50000',
        'status' => 'new'
    ],
    [
        'company_name' => 'Студия Дизайна "Арт-Стиль"',
        'contact_person' => 'Марина Соколова',
        'email' => 'marina@artstyle.ru',
        'phone' => '+7 (812) 555-12-34',
        'project_description' => 'Разработка интернет-магазина для продажи картин и предметов интерьера.',
        'budget_range' => '100000-150000',
        'status' => 'approved'
    ],
];

foreach ($client_applications as $app) {
    $created_at = $app['status'] == 'new' ? 'NOW()' : 'DATE_SUB(NOW(), INTERVAL 3 DAY)';
    $sql = "INSERT INTO client_applications (company_name, contact_person, email, phone, project_description, budget_range, status, created_at) 
            VALUES (
                '{$app['company_name']}',
                '{$app['contact_person']}',
                '{$app['email']}',
                '{$app['phone']}',
                '{$app['project_description']}',
                '{$app['budget_range']}',
                '{$app['status']}',
                $created_at
            )";
    
    if (query($sql)) {
        echo "<div class='success'>✓ Создана заявка клиента: {$app['company_name']}</div>";
    }
}

// ==================== 6. СОЗДАНИЕ СООБЩЕНИЙ ====================
echo "<h3>💬 Создание сообщений...</h3>";

// Получаем проекты для добавления сообщений
$projects = query("SELECT id, client_id, developer_id FROM projects WHERE developer_id IS NOT NULL LIMIT 3");

if ($projects && mysqli_num_rows($projects) > 0) {
    $messages_data = [
        ['message' => 'Здравствуйте! Когда планируете начать работу над проектом?', 'days_ago' => 7],
        ['message' => 'Добрый день! Уточните, пожалуйста, требования по дизайну.', 'days_ago' => 6],
        ['message' => 'Отправил вам на проверку первый вариант.', 'days_ago' => 3],
        ['message' => 'Отлично! Нужно немного поправить стили.', 'days_ago' => 2],
        ['message' => 'Готово, проверяйте!', 'days_ago' => 1],
    ];
    
    while ($project = fetch($projects)) {
        // Получаем клиента
        $client_result = query("SELECT user_id FROM clients WHERE id = {$project['client_id']}");
        $client = fetch($client_result);
        
        // Добавляем несколько сообщений
        foreach ($messages_data as $index => $msg) {
            $sender_id = ($index % 2 == 0) ? $client['user_id'] : $project['developer_id'];
            $created_at = "DATE_SUB(NOW(), INTERVAL {$msg['days_ago']} DAY)";
            $sql = "INSERT INTO messages (project_id, sender_id, message, created_at, is_read) 
                    VALUES (
                        {$project['id']}, 
                        $sender_id, 
                        '{$msg['message']}', 
                        $created_at,
                        " . ($index == count($messages_data)-1 ? '0' : '1') . "
                    )";
            query($sql);
        }
        echo "<div class='success'>✓ Добавлены сообщения для проекта #{$project['id']}</div>";
    }
}

// ==================== 7. СОЗДАНИЕ УВЕДОМЛЕНИЙ ====================
echo "<h3>🔔 Создание уведомлений...</h3>";

$notifications = [
    ['user_id' => 2, 'title' => 'Новая заявка разработчика', 'message' => 'Поступила новая заявка от Сергея Новикова', 'link' => '/admin/requests/developers.php'],
    ['user_id' => 2, 'title' => 'Новый проект', 'message' => 'Клиент "ООО СтройИнвест" создал новый проект', 'link' => '/admin/projects/view.php?id=2'],
    ['user_id' => 3, 'title' => 'Назначен проект', 'message' => 'Вам назначен проект "Интернет-магазин"', 'link' => '/developer/projects/view.php?id=1'],
    ['user_id' => 5, 'title' => 'Статус проекта изменен', 'message' => 'Ваш проект "CRM система" передан в работу', 'link' => '/client/projects/view.php?id=2'],
];

foreach ($notifications as $notif) {
    $created_at = "DATE_SUB(NOW(), INTERVAL " . rand(1, 5) . " DAY)";
    $sql = "INSERT INTO notifications (user_id, title, message, link, created_at, is_read) 
            VALUES (
                {$notif['user_id']}, 
                '{$notif['title']}', 
                '{$notif['message']}', 
                '{$notif['link']}', 
                $created_at,
                0
            )";
    query($sql);
}
echo "<div class='success'>✓ Добавлены уведомления</div>";

// ==================== 8. СТАТИСТИКА ====================
echo "<h3>📊 Статистика:</h3>";
echo "<pre>";

$stats = [
    'users' => query("SELECT COUNT(*) as count FROM users"),
    'developers' => query("SELECT COUNT(*) as count FROM developers"),
    'clients' => query("SELECT COUNT(*) as count FROM clients"),
    'projects' => query("SELECT COUNT(*) as count FROM projects"),
    'projects_in_progress' => query("SELECT COUNT(*) as count FROM projects WHERE status = 'in_progress'"),
    'projects_completed' => query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'"),
    'messages' => query("SELECT COUNT(*) as count FROM messages"),
    'notifications' => query("SELECT COUNT(*) as count FROM notifications"),
];

foreach ($stats as $key => $result) {
    if ($result) {
        $row = fetch($result);
        echo str_pad($key, 25, ' ') . ": {$row['count']}\n";
    }
}

echo "</pre>";

// ==================== 9. ДОСТУПНЫЕ УЧЕТНЫЕ ЗАПИСИ ====================
echo "<h3>🔑 Доступные учетные записи:</h3>";
echo "<pre>";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "РОЛЬ        | EMAIL                    | ПАРОЛЬ\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Админ       | admin@lark.ru            | admin123\n";
echo "Менеджер    | manager1@lark.ru         | manager123\n";
echo "Менеджер    | manager2@lark.ru         | manager123\n";
echo "Клиент      | info@techpro.ru          | client123\n";
echo "Клиент      | info@romashka.ru         | client123\n";
echo "Клиент      | info@stroyinvest.ru      | client123\n";
echo "Разработчик | ivan.petrov@dev.ru       | dev123\n";
echo "Разработчик | anna.smirnova@dev.ru     | dev123\n";
echo "Разработчик | dmitry.ivanov@dev.ru     | dev123\n";
echo "Разработчик | elena.kozlov@dev.ru      | dev123\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "</pre>";

echo "<hr>";
echo "<h2 style='color: #ffd700;'>✅ Заполнение тестовыми данными завершено!</h2>";
echo "<p>Вы можете войти в систему, используя учетные данные из списка выше.</p>";
echo "<p><a href='index.php' style='color: #00f3ff;'>➡ Перейти на главную</a> | ";
echo "<a href='admin/login.php' style='color: #ffd700;'>➡ Админ-панель</a> | ";
echo "<a href='client/login.php' style='color: #00f3ff;'>➡ Клиентская панель</a> | ";
echo "<a href='developer/login.php' style='color: #ffd700;'>➡ Панель разработчика</a></p>";

?>
</body>
</html>