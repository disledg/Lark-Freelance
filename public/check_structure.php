<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<h1>Проверка структуры таблиц</h1>";

// Проверяем структуру таблицы projects
$result = query("DESCRIBE projects");
echo "<h2>Структура таблицы projects:</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолч.</th></tr>";

if ($result) {
    while ($row = fetch($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
}
echo "</table>";

// Проверяем есть ли данные в таблице
$result = query("SELECT COUNT(*) as count FROM projects");
$row = fetch($result);
echo "<p>Всего проектов: " . $row['count'] . "</p>";

$result = query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'");
$row = fetch($result);
echo "<p>Завершенных проектов: " . $row['count'] . "</p>";
?>