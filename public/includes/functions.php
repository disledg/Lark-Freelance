<?php
/**
 * Функции для Lark Freelance
 */

// Проверка авторизации
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Проверка ролей
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function isManager() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'manager';
}

function isDeveloper() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'developer';
}

function isClient() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'client';
}

// Редирект
function redirect($url) {
    header("Location: $url");
    exit();
}

// Добавление уведомления
function addNotification($user_id, $title, $message, $link = '') {
    global $connection;
    if (!isset($connection) || !($connection instanceof mysqli)) {
        return false;
    }
    $user_id = (int)$user_id;
    $title = mysqli_real_escape_string($connection, $title);
    $message = mysqli_real_escape_string($connection, $message);
    $link = mysqli_real_escape_string($connection, $link);
    
    $sql = "INSERT INTO notifications (user_id, title, message, link, created_at) 
            VALUES ($user_id, '$title', '$message', '$link', NOW())";
    return mysqli_query($connection, $sql);
}

// Добавление лога
function addLog($user_id, $action, $details = '') {
    global $connection;
    if (!isset($connection) || !($connection instanceof mysqli)) {
        return false;
    }
    $user_id = $user_id ? (int)$user_id : 'NULL';
    $action = mysqli_real_escape_string($connection, $action);
    $details = mysqli_real_escape_string($connection, $details);
    $ip = mysqli_real_escape_string($connection, $_SERVER['REMOTE_ADDR'] ?? '');
    
    $sql = "INSERT INTO logs (user_id, action, details, ip, created_at) 
            VALUES ($user_id, '$action', '$details', '$ip', NOW())";
    return mysqli_query($connection, $sql);
}

// Получение статуса
function getStatusBadge($status) {
    $colors = [
        'new' => 'blue',
        'approved' => 'green',
        'rejected' => 'red',
        'in_progress' => 'purple',
        'completed' => 'green',
        'cancelled' => 'gray'
    ];
    $color = $colors[$status] ?? 'gray';
    return "<span class='badge badge-$color'>$status</span>";
}
?>