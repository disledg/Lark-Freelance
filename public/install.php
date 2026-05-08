<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Диагностика и установка Lark Freelance</h1>";

// Проверяем статус OpenServer
echo "<h2>1. Проверка OpenServer:</h2>";
if (file_exists('A:/ospanel/OSPanel/modules')) {
    echo "✓ OpenServer найден<br>";
} else {
    echo "⚠ OpenServer не в стандартной папке<br>";
}

// Проверяем расширение MySQLi
echo "<h2>2. Проверка PHP расширений:</h2>";
if (extension_loaded('mysqli')) {
    echo "✓ Расширение MySQLi загружено<br>";
} else {
    echo "✗ Расширение MySQLi НЕ загружено!<br>";
    exit();
}

// СПРАШИВАЕМ ПОЛЬЗОВАТЕЛЯ О ПОРТЕ
echo "<h2>3. Настройка подключения:</h2>";
echo "<form method='POST'>";
echo "Порт MySQL (обычно 3306 или 3307): <input type='text' name='mysql_port' value='3306'><br>";
echo "<input type='submit' value='Проверить подключение'>";
echo "</form>";

$port = isset($_POST['mysql_port']) ? $_POST['mysql_port'] : '3306';

echo "<h2>4. Тестирование подключения к MySQL (порт: $port):</h2>";

$hosts = [
    'db' => "db:$port",
    '127.0.0.1' => "127.0.0.1:$port",
    'localhost' . ':' . $port => "localhost (явно порт $port)",
    '127.0.0.1' . ':' . $port => "127.0.0.1 (явно порт $port)"
];

$connected = false;
$success_host = '';
$success_port = '';

foreach ($hosts as $host => $description) {
    echo "Пробуем подключиться к <b>$host</b> ($description)... ";
    
    // Убираем предупреждения
    $conn = @mysqli_connect($host, 'root', 'rootpassword');
    
    if ($conn) {
        echo "✅ <span style='color:green; font-weight:bold;'>УСПЕШНО!</span><br>";
        $connected = true;
        $success_host = $host;
        
        // Получаем информацию о сервере
        $mysql_info = mysqli_get_server_info($conn);
        echo "Версия MySQL: $mysql_info<br>";
        
        mysqli_close($conn);
        break;
    } else {
        $error = mysqli_connect_error();
        echo "❌ Ошибка: " . ($error ?: 'Нет ответа от сервера') . "<br>";
    }
}

if (!$connected) {
    echo "<br><b style='color:red; font-size:16px;'>✗ НЕ УДАЛОСЬ ПОДКЛЮЧИТЬСЯ К MySQL</b><br>";
    echo "<h3>🔍 ДИАГНОСТИКА:</h3>";
    
    // Проверяем, запущен ли процесс MySQL
    echo "<b>Проверка процессов:</b><br>";
    
    if (function_exists('shell_exec')) {
        $result = shell_exec('tasklist 2>&1');
        if (strpos($result, 'mysqld.exe') !== false) {
            echo "✓ Процесс mysqld.exe найден<br>";
        } else {
            echo "✗ Процесс mysqld.exe НЕ найден - MySQL не запущен!<br>";
        }
    } else {
        echo "Не могу проверить процессы<br>";
    }
    
    echo "<h3>🔧 ЧТО ДЕЛАТЬ:</h3>";
    echo "<ol>";
    echo "<li><b>Запустите MySQL в OpenServer:</b> Правый клик на иконке → Запустить</li>";
    echo "<li><b>Проверьте модули:</b> Правый клик → Настройки → Модули → выберите MySQL-5.7 или MySQL-8.0</li>";
    echo "<li><b>Проверьте порт:</b> Правый клик → Настройки → Сервер → Порт MySQL (попробуйте 3306 или 3307)</li>";
    echo "<li><b>Перезапустите OpenServer</b> после изменений</li>";
    echo "<li><b>Попробуйте другой порт</b> в форме выше</li>";
    echo "</ol>";
    
    echo "<h3>📋 ИНСТРУКЦИЯ С КАРТИНКАМИ:</h3>";
    echo "1. Найдите иконку OpenServer в трее (рядом с часами)<br>";
    echo "2. Если иконка красная - нажмите 'Запустить'<br>";
    echo "3. Если иконка желтая - подождите 10 секунд<br>";
    echo "4. Если иконка зеленая - всё работает, но порт не тот<br>";
    echo "5. Проверьте порт в настройках (обычно 3306 или 3307)<br>";
    echo "6. Попробуйте оба порта в форме выше<br>";
    
    exit();
}

// Если подключились, продолжаем установку
echo "<h2 style='color:green;'>✓ Подключение к MySQL установлено через $success_host</h2>";

// Подключаемся с выбранным хостом
$conn = mysqli_connect($success_host, 'root', 'rootpassword');

// Создаем базу данных
$dbname = 'lark_freelance';
echo "<h2>5. Создание базы данных '$dbname':</h2>";

$sql = "CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (mysqli_query($conn, $sql)) {
    echo "✓ База данных создана или уже существует<br>";
} else {
    echo "✗ Ошибка: " . mysqli_error($conn) . "<br>";
    exit();
}

// Выбираем БД
mysqli_select_db($conn, $dbname);

// Отключаем проверку иностранных ключей для удаления таблиц
echo "<h2>5.5. Удаление старых таблиц:</h2>";
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");

$tables_to_drop = [
    'cases', 'messages', 'project_status_history', 'logs', 'notifications',
    'projects', 'client_applications', 'developer_applications',
    'clients', 'developers', 'users'
];

foreach ($tables_to_drop as $table) {
    if (mysqli_query($conn, "DROP TABLE IF EXISTS $table")) {
        echo "✓ Таблица $table удалена (если существовала)<br>";
    }
}

// Включаем проверку иностранных ключей обратно
mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");

// Создаем таблицы
echo "<h2>6. Создание таблиц:</h2>";

$tables_sql = [
        // Таблица пользователей
        "CREATE TABLE users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100),
            role ENUM('admin', 'manager', 'developer', 'client') NOT NULL,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login TIMESTAMP NULL
        )",
        
        // Таблица разработчиков
        "CREATE TABLE developers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNIQUE NOT NULL,
            level ENUM('junior', 'middle') DEFAULT 'junior',
            skills TEXT,
            experience TEXT,
            portfolio VARCHAR(500),
            telegram VARCHAR(100),
            github VARCHAR(200),
            rating DECIMAL(3,2) DEFAULT 0,
            completed_projects INT DEFAULT 0,
            is_available BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Таблица клиентов
        "CREATE TABLE clients (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNIQUE NOT NULL,
            company_name VARCHAR(200),
            phone VARCHAR(20),
            telegram VARCHAR(100),
            company_site VARCHAR(200),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Заявки разработчиков
        "CREATE TABLE developer_applications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            level ENUM('junior', 'middle') NOT NULL,
            skills TEXT NOT NULL,
            experience TEXT NOT NULL,
            portfolio VARCHAR(500),
            telegram VARCHAR(100),
            github VARCHAR(200),
            status ENUM('new', 'approved', 'rejected') DEFAULT 'new',
            manager_comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Заявки клиентов
        "CREATE TABLE client_applications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            company_name VARCHAR(200) NOT NULL,
            contact_person VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            project_description TEXT NOT NULL,
            budget_range VARCHAR(100),
            status ENUM('new', 'approved', 'rejected') DEFAULT 'new',
            manager_comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        
        // Таблица проектов
        "CREATE TABLE projects (
            id INT PRIMARY KEY AUTO_INCREMENT,
            title VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            requirements TEXT,
            client_id INT NOT NULL,
            developer_id INT NULL,
            manager_id INT NULL,
            budget DECIMAL(10,2),
            deadline DATE,
            status ENUM('new', 'in_progress', 'completed', 'cancelled') DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            completed_at TIMESTAMP NULL,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            FOREIGN KEY (developer_id) REFERENCES developers(id) ON DELETE SET NULL,
            FOREIGN KEY (manager_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        // История статусов проектов
        "CREATE TABLE project_status_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            project_id INT NOT NULL,
            old_status VARCHAR(50),
            new_status VARCHAR(50) NOT NULL,
            changed_by INT,
            comment TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        // Сообщения
        "CREATE TABLE messages (
            id INT PRIMARY KEY AUTO_INCREMENT,
            project_id INT NOT NULL,
            sender_id INT NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            read_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Уведомления
        "CREATE TABLE notifications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT,
            link VARCHAR(500),
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
        
        // Логи
        "CREATE TABLE logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip VARCHAR(45),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )",
        
        // Успешные кейсы
        "CREATE TABLE cases (
            id INT PRIMARY KEY AUTO_INCREMENT,
            project_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            company_name VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            deadline VARCHAR(50),
            budget DECIMAL(10,2),
            technologies TEXT,
            challenges TEXT,
            results TEXT,
            is_featured BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
        )"
    ];

$success_count = 0;
$error_count = 0;

foreach ($tables_sql as $sql) {
    try {
        if (mysqli_query($conn, $sql)) {
            echo "✓ Таблица создана<br>";
            $success_count++;
        } else {
            echo "✗ Ошибка: " . mysqli_error($conn) . "<br>";
            $error_count++;
        }
    } catch (Exception $e) {
        echo "✗ Исключение: " . $e->getMessage() . "<br>";
        $error_count++;
    }
}

echo "<h2>7. Создание тестовых пользователей:</h2>";

// Проверяем, есть ли уже пользователи
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM users");
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (username, email, password, full_name, role, status) VALUES 
            ('admin', 'admin@lark.ru', '$password', 'Admin', 'admin', 'approved'),
            ('manager', 'manager@lark.ru', '$password', 'Manager', 'manager', 'approved')";
    
    if (mysqli_query($conn, $sql)) {
        echo "✓ Тестовые пользователи созданы<br>";
        echo "Логин: admin@lark.ru / admin123<br>";
        echo "Логин: manager@lark.ru / admin123<br>";
    } else {
        echo "✗ Ошибка: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "✓ Пользователи уже существуют<br>";
}

echo "<h2>8. Создание тестовых кейсов:</h2>";

// Проверяем, есть ли уже кейсы
$result = mysqli_query($conn, "SELECT COUNT(*) as count FROM cases");
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    // Проверяем, есть ли завершенные проекты
    $projects_result = mysqli_query($conn, "SELECT id FROM projects WHERE status = 'completed' LIMIT 2");
    
    if (mysqli_num_rows($projects_result) > 0) {
        $test_cases = [
            [
                'title' => 'Платформа для онлайн-ритейлера',
                'company_name' => 'E-COMMERCE',
                'description' => 'Разработка высоконагруженной платформы с обработкой 10,000+ заказов в день',
                'deadline' => '45 дней',
                'budget' => 50000.00,
                'technologies' => 'PHP, Laravel, MySQL, Redis, Docker',
                'challenges' => 'Оптимизация производительности, масштабируемость, безопасность платежей',
                'results' => 'Увеличение конверсии на 35%, обработка 15,000 заказов в день',
                'is_featured' => 1
            ],
            [
                'title' => 'Приложение для фитнес-трекинга',
                'company_name' => 'MOBILE APP',
                'description' => 'Кроссплатформенное приложение с интеграцией умных устройств и аналитикой',
                'deadline' => '60 дней',
                'budget' => 75000.00,
                'technologies' => 'React Native, Node.js, MongoDB, AWS',
                'challenges' => 'Интеграция с 10+ типами устройств, оффлайн режим, аналитика данных',
                'results' => '1M+ скачиваний, средний рейтинг 4.8, 300k активных пользователей',
                'is_featured' => 1
            ]
        ];
        
        $project_ids = [];
        while ($p = mysqli_fetch_assoc($projects_result)) {
            $project_ids[] = $p['id'];
        }
        
        $case_count = 0;
        foreach ($test_cases as $i => $case_data) {
            if (isset($project_ids[$i])) {
                $title = mysqli_real_escape_string($conn, $case_data['title']);
                $company = mysqli_real_escape_string($conn, $case_data['company_name']);
                $description = mysqli_real_escape_string($conn, $case_data['description']);
                $deadline = mysqli_real_escape_string($conn, $case_data['deadline']);
                $technologies = mysqli_real_escape_string($conn, $case_data['technologies']);
                $challenges = mysqli_real_escape_string($conn, $case_data['challenges']);
                $results = mysqli_real_escape_string($conn, $case_data['results']);
                $project_id = $project_ids[$i];
                $featured = $case_data['is_featured'];
                $budget = $case_data['budget'];
                
                $sql = "INSERT INTO cases (project_id, title, company_name, description, deadline, budget, technologies, challenges, results, is_featured) 
                        VALUES ($project_id, '$title', '$company', '$description', '$deadline', $budget, '$technologies', '$challenges', '$results', $featured)";
                
                if (mysqli_query($conn, $sql)) {
                    echo "✓ Тестовый кейс '$title' создан<br>";
                    $case_count++;
                } else {
                    echo "✗ Ошибка создания кейса: " . mysqli_error($conn) . "<br>";
                }
            }
        }
        
        if ($case_count > 0) {
            echo "✓ Всего кейсов создано: $case_count<br>";
        }
    } else {
        echo "ℹ Завершенных проектов не найдено. Кейсы можно создавать через админ-панель<br>";
    }
} else {
    echo "✓ Кейсы уже существуют<br>";
}

mysqli_close($conn);

echo "<h2 style='color:green;'>✅ Установка завершена!</h2>";
echo "<p><a href='index.php' style='font-size:18px;'>➡ Перейти на главную страницу</a></p>";
echo "<p><a href='admin/login.php' style='font-size:18px;'>➡ Перейти в админ-панель</a></p>";
?>