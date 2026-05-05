<?php
// ПОДКЛЮЧЕНИЕ К MySQL
$host = 'db';  // Имя сервиса MySQL из docker-compose
$user = 'root';
$pass = 'rootpassword';  // Пароль из docker-compose
$dbname = 'lark_freelance';

// Создаем подключение
$connection = mysqli_connect($host, $user, $pass, $dbname);

// Проверяем подключение
if (!$connection) {
    die("Ошибка подключения к БД: " . mysqli_connect_error());
}

// Устанавливаем кодировку
mysqli_set_charset($connection, "utf8mb4");

// Функции для работы с БД
function query($sql) {
    global $connection;
    return mysqli_query($connection, $sql);
}

function fetch($result) {
    return mysqli_fetch_assoc($result);
}

function escape($str) {
    global $connection;
    return mysqli_real_escape_string($connection, $str);
}

function insert_id() {
    global $connection;
    return mysqli_insert_id($connection);
}

// СОЗДАЕМ ГЛОБАЛЬНУЮ ПЕРЕМЕННУЮ $db ДЛЯ СТАРОГО КОДА
$db = new stdClass();
$db->conn = $connection;
$db->query = 'query';
$db->escape = 'escape';

// Для обратной совместимости
if (!function_exists('db_query')) {
    function db_query($sql) {
        return query($sql);
    }
}
?>