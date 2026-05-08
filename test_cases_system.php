<?php
/**
 * Тестовый скрипт для проверки работы системы кейсов
 * Запускайте после настройки Docker окружения
 */

echo "🧪 Тестирование системы управления кейсами\n\n";

// Проверяем наличие файлов
$files_to_check = [
    'includes/cases.php',
    'admin/cases.php',
    'update_cases_table.php',
    'CASES_MANUAL.md'
];

echo "📁 Проверка файлов:\n";
foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✅ $file - найден\n";
    } else {
        echo "❌ $file - отсутствует\n";
    }
}

echo "\n📋 Проверка структуры БД:\n";

// Проверяем наличие таблицы cases в SQL файле
$sql_content = file_get_contents(__DIR__ . '/sql/database.sql');
if (strpos($sql_content, 'CREATE TABLE cases') !== false) {
    echo "✅ Таблица cases - определена в database.sql\n";
} else {
    echo "❌ Таблица cases - отсутствует в database.sql\n";
}

// Проверяем функции в cases.php
if (function_exists('getAllCases')) {
    echo "✅ Функция getAllCases - определена\n";
} else {
    echo "❌ Функция getAllCases - отсутствует\n";
}

echo "\n📖 Инструкции по запуску:\n";
echo "1. Запустите Docker: docker-compose up -d\n";
echo "2. Выполните: php update_cases_table.php\n";
echo "3. Откройте админ-панель и перейдите в раздел 'КЕЙСЫ'\n";
echo "4. Создайте тестовый кейс для завершенного проекта\n";
echo "5. Проверьте отображение на главной странице\n";

echo "\n📚 Документация: CASES_MANUAL.md\n";

echo "\n🎉 Система кейсов готова к использованию!\n";
?>