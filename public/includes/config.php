<?php
// Конфигурация базы данных для OpenServer
define('DB_HOST', 'db');  // ТОЧНОЕ НАЗВАНИЕ ИЗ НАСТРОЕК
define('DB_PORT', '3306');        // Порт MySQL по умолчанию
define('DB_NAME', 'lark_freelance');
define('DB_USER', 'root');
define('DB_PASS', 'rootpassword');            // В OpenServer пароль пустой
define('DB_CHARSET', 'utf8mb4');

// Пути к сайту
define('SITE_URL', 'http://lark-freelance.local');
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Создание директории для загрузок
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// Настройки сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Включаем отображение ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>