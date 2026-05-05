<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Если пользователь был залогинен, записываем лог
if (isset($_SESSION['user_id'])) {
    addLog($_SESSION['user_id'], 'logout', 'User logged out');
}

// Очищаем сессию
$_SESSION = array();

// Уничтожаем сессию
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Перенаправляем на главную
header('Location: ../index.php');
exit();
?>