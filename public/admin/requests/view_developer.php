<?php
require_once __DIR__ . '/../../includes/config.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
header('Location: developers.php?modal=view&id=' . $id);
exit;

