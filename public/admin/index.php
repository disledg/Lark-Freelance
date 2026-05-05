<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Если пользователь уже залогинен, отправляем в dashboard
if (isLoggedIn() && (isAdmin() || isManager())) {
    header('Location: dashboard.php');
    exit();
}

// Иначе отправляем на страницу входа
header('Location: login.php');
exit();
?>