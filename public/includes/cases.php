<?php
/**
 * Функции для работы с успешными кейсами
 */

// Получить все кейсы
function getAllCases($featured_only = false) {
    try {
        $where = $featured_only ? "WHERE c.is_featured = TRUE" : "";
        $sql = "
            SELECT c.*, p.title as project_title, cl.company_name as client_company,
                   u.full_name as client_name, du.full_name as developer_name
            FROM cases c
            JOIN projects p ON c.project_id = p.id
            LEFT JOIN clients cl ON p.client_id = cl.id
            LEFT JOIN users u ON cl.user_id = u.id
            LEFT JOIN developers dev ON p.developer_id = dev.id
            LEFT JOIN users du ON dev.user_id = du.id
            $where
            ORDER BY c.created_at DESC
        ";
        return query($sql);
    } catch (Exception $e) {
        error_log("Ошибка при получении кейсов: " . $e->getMessage());
        return false;
    }
}

// Получить кейс по ID
function getCaseById($id) {
    $sql = "
        SELECT c.*, p.title as project_title, p.description as project_description,
               cl.company_name as client_company, u.full_name as client_name,
               du.full_name as developer_name
        FROM cases c
        JOIN projects p ON c.project_id = p.id
        LEFT JOIN clients cl ON p.client_id = cl.id
        LEFT JOIN users u ON cl.user_id = u.id
        LEFT JOIN developers dev ON p.developer_id = dev.id
        LEFT JOIN users du ON dev.user_id = du.id
        WHERE c.id = ?
    ";
    $stmt = mysqli_prepare($GLOBALS['connection'], $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Создать кейс
function createCase($data) {
    $sql = "
        INSERT INTO cases (project_id, title, company_name, description, deadline, budget,
                          technologies, challenges, results, is_featured)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = mysqli_prepare($GLOBALS['connection'], $sql);
    $is_featured = isset($data['is_featured']) ? (int)$data['is_featured'] : 0;
    mysqli_stmt_bind_param($stmt, "isssdssssi",
        $data['project_id'],
        $data['title'],
        $data['company_name'],
        $data['description'],
        $data['deadline'],
        $data['budget'],
        $data['technologies'],
        $data['challenges'],
        $data['results'],
        $is_featured
    );
    return mysqli_stmt_execute($stmt);
}

// Обновить кейс
function updateCase($id, $data) {
    $sql = "
        UPDATE cases SET
            title = ?, company_name = ?, description = ?, deadline = ?,
            budget = ?, technologies = ?, challenges = ?, results = ?,
            is_featured = ?, updated_at = NOW()
        WHERE id = ?
    ";
    $stmt = mysqli_prepare($GLOBALS['connection'], $sql);
    $is_featured = isset($data['is_featured']) ? (int)$data['is_featured'] : 0;
    mysqli_stmt_bind_param($stmt, "sssdssssii",
        $data['title'],
        $data['company_name'],
        $data['description'],
        $data['deadline'],
        $data['budget'],
        $data['technologies'],
        $data['challenges'],
        $data['results'],
        $is_featured,
        $id
    );
    return mysqli_stmt_execute($stmt);
}

// Удалить кейс
function deleteCase($id) {
    $sql = "DELETE FROM cases WHERE id = ?";
    $stmt = mysqli_prepare($GLOBALS['connection'], $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    return mysqli_stmt_execute($stmt);
}

// Получить завершенные проекты для создания кейсов
function getCompletedProjectsWithoutCases() {
    $sql = "
        SELECT p.*, cl.company_name, u.full_name as client_name
        FROM projects p
        LEFT JOIN clients cl ON p.client_id = cl.id
        LEFT JOIN users u ON cl.user_id = u.id
        LEFT JOIN cases c ON p.id = c.project_id
        WHERE p.status = 'completed' AND c.id IS NULL
        ORDER BY p.updated_at DESC
    ";
    return query($sql);
}

// Проверить, существует ли кейс для проекта
function caseExistsForProject($project_id) {
    $sql = "SELECT COUNT(*) as count FROM cases WHERE project_id = ?";
    $stmt = mysqli_prepare($GLOBALS['connection'], $sql);
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    return $row['count'] > 0;
}
?>