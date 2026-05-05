<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Обновление структуры таблиц</h1>";

// Добавляем колонку completed_at в projects, если её нет
$result = query("SHOW COLUMNS FROM projects LIKE 'completed_at'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE projects ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at";
    if (query($sql)) {
        echo "✓ Добавлена колонка completed_at в таблицу projects<br>";
    } else {
        echo "✗ Ошибка при добавлении колонки: " . mysqli_error($connection) . "<br>";
    }
} else {
    echo "✓ Колонка completed_at уже существует<br>";
}

// Добавляем колонку deadline, если её нет
$result = query("SHOW COLUMNS FROM projects LIKE 'deadline'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE projects ADD COLUMN deadline DATE NULL DEFAULT NULL AFTER budget";
    if (query($sql)) {
        echo "✓ Добавлена колонка deadline в таблицу projects<br>";
    } else {
        echo "✗ Ошибка при добавлении колонки: " . mysqli_error($connection) . "<br>";
    }
} else {
    echo "✓ Колонка deadline уже существует<br>";
}

// Добавляем тестовые данные, если их нет
$result = query("SELECT COUNT(*) as count FROM projects");
$row = fetch($result);
if ($row['count'] == 0) {
    echo "<h2>Добавление тестовых данных:</h2>";
    
    // Сначала создадим тестового клиента, если его нет
    $result = query("SELECT id FROM clients LIMIT 1");
    if ($result->num_rows == 0) {
        // Создаем тестового пользователя-клиента
        $password = password_hash('test123', PASSWORD_DEFAULT);
        query("INSERT INTO users (username, email, password, full_name, role, status) VALUES ('testclient', 'client@test.ru', '$password', 'Тестовый Клиент', 'client', 'approved')");
        $user_id = insert_id();
        query("INSERT INTO clients (user_id, company_name) VALUES ($user_id, 'ООО Тестовая Компания')");
    }
    
    $client_result = query("SELECT id FROM clients LIMIT 1");
    $client = fetch($client_result);
    
    if ($client) {
        // Добавляем тестовые проекты
        $sql = "INSERT INTO projects (title, description, client_id, status, budget, deadline) VALUES 
                ('Платформа для онлайн-ритейлера', 'Разработка высоконагруженной платформы с обработкой 10,000+ заказов в день', {$client['id']}, 'completed', 50000, DATE_ADD(NOW(), INTERVAL 45 DAY)),
                ('Приложение для фитнес-трекинга', 'Кроссплатформенное приложение с интеграцией умных устройств и аналитикой', {$client['id']}, 'completed', 75000, DATE_ADD(NOW(), INTERVAL 60 DAY))";
        
        if (query($sql)) {
            echo "✓ Тестовые проекты добавлены<br>";
        } else {
            echo "✗ Ошибка при добавлении проектов: " . mysqli_error($connection) . "<br>";
        }
    }
}

echo "<p><a href='index.php'>Перейти на главную</a></p>";
?>