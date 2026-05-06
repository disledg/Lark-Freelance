<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isManager() && !isAdmin()) {
    redirect('../login.php');
}

$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($project_id <= 0) {
    redirect('index.php');
}

redirect("view.php?id=$project_id");

