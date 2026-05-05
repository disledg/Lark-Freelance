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
        )"
    ];

$success_count = 0;
foreach ($tables_sql as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✓ Таблица создана<br>";
        $success_count++;
    } else {
        echo "✗ Ошибка: " . mysqli_error($conn) . "<br>";
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

mysqli_close($conn);

echo "<h2 style='color:green;'>✅ Установка завершена!</h2>";
echo "<p><a href='index.php' style='font-size:18px;'>➡ Перейти на главную страницу</a></p>";
echo "<p><a href='admin/login.php' style='font-size:18px;'>➡ Перейти в админ-панель</a></p>";
?>