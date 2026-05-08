<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Создаем таблицу cases если она не существует
$create_cases_table = "
CREATE TABLE IF NOT EXISTS cases (
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
)";

if (query($create_cases_table)) {
    echo "✅ Таблица cases создана успешно\n";
} else {
    echo "❌ Ошибка создания таблицы cases: " . mysqli_error($connection) . "\n";
}

// Добавляем тестовые кейсы
$test_cases = [
    [
        'project_id' => 1, // Предполагаем, что есть проект с ID 1
        'title' => 'Платформа для онлайн-ритейлера',
        'company_name' => 'E-COMMERCE',
        'description' => 'Разработка высоконагруженной платформы с обработкой 10,000+ заказов в день',
        'deadline' => '45 дней',
        'budget' => 50000.00,
        'technologies' => 'PHP, Laravel, MySQL, Redis, Docker',
        'challenges' => 'Оптимизация производительности, масштабируемость, безопасность платежей',
        'results' => 'Увеличение конверсии на 35%, обработка 15,000 заказов в день',
        'is_featured' => true
    ],
    [
        'project_id' => 2, // Предполагаем, что есть проект с ID 2
        'title' => 'Приложение для фитнес-трекинга',
        'company_name' => 'MOBILE APP',
        'description' => 'Кроссплатформенное приложение с интеграцией умных устройств и аналитикой',
        'deadline' => '60 дней',
        'budget' => 75000.00,
        'technologies' => 'React Native, Node.js, MongoDB, AWS',
        'challenges' => 'Интеграция с 10+ типами устройств, оффлайн режим, аналитика данных',
        'results' => '1M+ скачиваний, средний рейтинг 4.8, 300k активных пользователей',
        'is_featured' => true
    ]
];

// Проверяем, есть ли уже кейсы
$result = query("SELECT COUNT(*) as count FROM cases");
$row = fetch($result);
if ($row['count'] == 0) {
    foreach ($test_cases as $case) {
        // Проверяем, существует ли проект
        $project_check = query("SELECT id FROM projects WHERE id = {$case['project_id']} LIMIT 1");
        if (fetch($project_check)) {
            $sql = "INSERT INTO cases (project_id, title, company_name, description, deadline, budget, technologies, challenges, results, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $sql);
            mysqli_stmt_bind_param($stmt, "isssdssssb",
                $case['project_id'],
                $case['title'],
                $case['company_name'],
                $case['description'],
                $case['deadline'],
                $case['budget'],
                $case['technologies'],
                $case['challenges'],
                $case['results'],
                $case['is_featured']
            );

            if (mysqli_stmt_execute($stmt)) {
                echo "✅ Тестовый кейс '{$case['title']}' добавлен\n";
            } else {
                echo "❌ Ошибка добавления кейса '{$case['title']}': " . mysqli_error($connection) . "\n";
            }
        } else {
            echo "⚠️ Пропускаем кейс '{$case['title']}' - проект с ID {$case['project_id']} не найден\n";
        }
    }
} else {
    echo "ℹ️ Тестовые кейсы уже существуют\n";
}

echo "\n🎉 Обновление базы данных завершено!\n";
?>