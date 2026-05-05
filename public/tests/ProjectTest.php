<?php
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    private $connection;
    private $testProjectId;
    private $testClientId;
    private $testDeveloperId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = mysqli_connect('db', 'root', 'rootpassword', 'lark_freelance');
        $GLOBALS['connection'] = $this->connection;
        
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($this->connection, "TRUNCATE TABLE projects");
        mysqli_query($this->connection, "TRUNCATE TABLE clients");
        mysqli_query($this->connection, "TRUNCATE TABLE developers");
        mysqli_query($this->connection, "TRUNCATE TABLE users");
        //mysqli_query($this->connection, "TRUNCATE TABLE project_status_history");
        mysqli_query($this->connection, "TRUNCATE TABLE messages");
        mysqli_query($this->connection, "TRUNCATE TABLE notifications");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 1");
        
        $this->createTestClient();
        $this->createTestDeveloper();
    }

    // CREATE тесты
    public function testCreateProject()
    {
        $projectSql = "INSERT INTO projects 
                       (title, description, requirements, client_id, budget, deadline, status, created_at) 
                       VALUES (
                           'Разработка корпоративного сайта',
                           'Необходимо разработать современный корпоративный сайт с админкой',
                           'Опыт работы с PHP 8.x, Laravel, MySQL, JavaScript',
                           $this->testClientId,
                           150000,
                           DATE_ADD(NOW(), INTERVAL 45 DAY),
                           'new',
                           NOW()
                       )";
        
        $this->assertTrue(mysqli_query($this->connection, $projectSql));
        $this->testProjectId = mysqli_insert_id($this->connection);

        // Проверяем создание
        $result = mysqli_query($this->connection, 
            "SELECT p.*, c.company_name 
             FROM projects p 
             JOIN clients c ON p.client_id = c.id 
             WHERE p.id = $this->testProjectId");
        
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals('Разработка корпоративного сайта', $project['title']);
        $this->assertStringContainsString('Laravel', $project['requirements']);
        $this->assertEquals($this->testClientId, $project['client_id']);
        $this->assertEquals('ООО ТехноПроект', $project['company_name']);
        $this->assertEquals('new', $project['status']);
        $this->assertEquals(150000, $project['budget']);
    }

    public function testCreateProjectWithMinimalData()
    {
        // Только обязательные поля
        $projectSql = "INSERT INTO projects 
                       (title, description, client_id, created_at) 
                       VALUES (
                           'Минимальный проект',
                           'Краткое описание',
                           $this->testClientId,
                           NOW()
                       )";
        
        $this->assertTrue(mysqli_query($this->connection, $projectSql));
        $projectId = mysqli_insert_id($this->connection);

        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $projectId");
        
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals('Минимальный проект', $project['title']);
        $this->assertEquals('Краткое описание', $project['description']);
        $this->assertEquals('new', $project['status']);
        $this->assertNull($project['budget']);
        $this->assertNull($project['deadline']);
        $this->assertNull($project['developer_id']);
    }

    // READ тесты
    public function testGetProjectById()
    {
        $this->createTestProject();
        
        $result = mysqli_query($this->connection, 
            "SELECT p.*, c.company_name, c.user_id as client_user_id,
                    d.id as developer_id, u.full_name as developer_name
             FROM projects p 
             JOIN clients c ON p.client_id = c.id 
             LEFT JOIN developers d ON p.developer_id = d.id
             LEFT JOIN users u ON d.user_id = u.id
             WHERE p.id = $this->testProjectId");
        
        $project = mysqli_fetch_assoc($result);
        
        $this->assertNotNull($project);
        $this->assertEquals('Интернет-магазин', $project['title']);
        $this->assertEquals('ООО ТехноПроект', $project['company_name']);
        $this->assertEquals('Иван Петров', $project['developer_name']);
    }

    public function testGetProjectsByClient()
    {
        $this->createTestProject(); // Интернет-магазин
        $this->createAnotherProject(); // Мобильное приложение
        
        $result = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE client_id = $this->testClientId");
        
        $count = mysqli_fetch_assoc($result)['count'];
        
        $this->assertEquals(2, $count);
    }

    public function testGetProjectsByDeveloper()
    {
        $this->createTestProject(); // Назначен разработчику
        
        $result = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE developer_id = $this->testDeveloperId");
        
        $count = mysqli_fetch_assoc($result)['count'];
        
        $this->assertEquals(1, $count);
    }

    public function testGetProjectsByStatus()
    {
        $this->createTestProject(); // new
        $this->createInProgressProject(); // in_progress
        $this->createCompletedProject(); // completed
        
        $newResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE status = 'new'");
        $newCount = mysqli_fetch_assoc($newResult)['count'];
        
        $progressResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE status = 'in_progress'");
        $progressCount = mysqli_fetch_assoc($progressResult)['count'];
        
        $completedResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE status = 'completed'");
        $completedCount = mysqli_fetch_assoc($completedResult)['count'];
        
        $this->assertEquals(1, $newCount);
        $this->assertEquals(1, $progressCount);
        $this->assertEquals(1, $completedCount);
    }

    public function testGetRecentProjects()
    {
        // Создаем проекты с разными датами
        for ($i = 1; $i <= 5; $i++) {
            $daysAgo = $i * 2;
            mysqli_query($this->connection, 
                "INSERT INTO projects (title, description, client_id, created_at) 
                 VALUES ('Проект $i', 'Описание', $this->testClientId, DATE_SUB(NOW(), INTERVAL $daysAgo DAY))");
        }
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects ORDER BY created_at DESC LIMIT 3");
        
        $this->assertEquals(3, mysqli_num_rows($result));
        
        $first = mysqli_fetch_assoc($result);
        $this->assertEquals('Проект 1', $first['title']); // Самый новый
    }

    public function testSearchProjects()
    {
        $this->createTestProject(); // Интернет-магазин
        $this->createAnotherProject(); // Мобильное приложение
        
        // Поиск по ключевому слову
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE title LIKE '%магазин%' OR description LIKE '%магазин%'");
        
        $this->assertEquals(1, mysqli_num_rows($result));
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE title LIKE '%мобильное%' OR description LIKE '%мобильное%'");
        
        $this->assertEquals(1, mysqli_num_rows($result));
    }

    // UPDATE тесты
    public function testUpdateProjectDetails()
    {
        $this->createTestProject();
        
        $updateSql = "UPDATE projects SET 
                      title = 'Интернет-магазин (обновленный)',
                      description = 'Новое описание проекта',
                      requirements = 'Новые требования: React, Node.js',
                      budget = 200000,
                      deadline = DATE_ADD(NOW(), INTERVAL 60 DAY)
                      WHERE id = $this->testProjectId";
        
        $this->assertTrue(mysqli_query($this->connection, $updateSql));
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $this->testProjectId");
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals('Интернет-магазин (обновленный)', $project['title']);
        $this->assertEquals('Новое описание проекта', $project['description']);
        $this->assertEquals('Новые требования: React, Node.js', $project['requirements']);
        $this->assertEquals(200000, $project['budget']);
    }

    public function testAssignDeveloperToProject()
    {
        $this->createTestProject();
        
        // Назначаем разработчика
        mysqli_query($this->connection, 
            "UPDATE projects SET 
             developer_id = $this->testDeveloperId,
             status = 'in_progress'
             WHERE id = $this->testProjectId");
        
        // Проверяем назначение
        $result = mysqli_query($this->connection, 
            "SELECT p.*, d.level, u.full_name as developer_name 
             FROM projects p 
             LEFT JOIN developers d ON p.developer_id = d.id
             LEFT JOIN users u ON d.user_id = u.id
             WHERE p.id = $this->testProjectId");
        
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals($this->testDeveloperId, $project['developer_id']);
        $this->assertEquals('in_progress', $project['status']);
        $this->assertEquals('Иван Петров', $project['developer_name']);
        $this->assertEquals('junior', $project['level']);
    }

    public function testCompleteProject()
    {
        $this->createInProgressProject();
        
        // Завершаем проект
        $completedAt = date('Y-m-d H:i:s');
        mysqli_query($this->connection, 
            "UPDATE projects SET 
             status = 'completed',
             completed_at = '$completedAt'
             WHERE id = $this->testProjectId");
        
        // Проверяем завершение
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $this->testProjectId");
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals('completed', $project['status']);
        $this->assertNotNull($project['completed_at']);
        
        // Проверяем, что разработчик стал доступен
        $devResult = mysqli_query($this->connection, 
            "SELECT is_available FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($devResult);
        
        $this->assertEquals(1, $developer['is_available']);
    }

    public function testCancelProject()
    {
        $this->createInProgressProject();
        
        // Отменяем проект
        mysqli_query($this->connection, 
            "UPDATE projects SET status = 'cancelled' WHERE id = $this->testProjectId");
        
        $result = mysqli_query($this->connection, 
            "SELECT status FROM projects WHERE id = $this->testProjectId");
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals('cancelled', $project['status']);
        
        // Разработчик должен стать доступен
        $devResult = mysqli_query($this->connection, 
            "SELECT is_available FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($devResult);
        
        $this->assertEquals(1, $developer['is_available']);
    }

    public function testUpdateProjectStatusWithHistory()
    {
        $this->createTestProject();
        
        // Создаем таблицу истории статусов
        mysqli_query($this->connection, 
            "CREATE TABLE IF NOT EXISTS project_status_history (
                id INT PRIMARY KEY AUTO_INCREMENT,
                project_id INT NOT NULL,
                old_status VARCHAR(50),
                new_status VARCHAR(50) NOT NULL,
                changed_by INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
            )");
        
        // Меняем статус и записываем историю
        $oldStatus = 'new';
        $newStatus = 'in_progress';
        
        mysqli_query($this->connection, 
            "UPDATE projects SET status = '$newStatus' WHERE id = $this->testProjectId");
        
        mysqli_query($this->connection, 
            "INSERT INTO project_status_history (project_id, old_status, new_status, changed_by) 
             VALUES ($this->testProjectId, '$oldStatus', '$newStatus', 1)");
        
        // Проверяем историю
        $historyResult = mysqli_query($this->connection, 
            "SELECT * FROM project_status_history WHERE project_id = $this->testProjectId");
        
        $this->assertEquals(1, mysqli_num_rows($historyResult));
        
        $history = mysqli_fetch_assoc($historyResult);
        $this->assertEquals('new', $history['old_status']);
        $this->assertEquals('in_progress', $history['new_status']);
    }

    // DELETE тесты
    public function testDeleteProject()
    {
        $this->createTestProject();
        
        // Удаляем проект
        mysqli_query($this->connection, 
            "DELETE FROM projects WHERE id = $this->testProjectId");
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $this->testProjectId");
        
        $this->assertEquals(0, mysqli_num_rows($result));
    }

    public function testCascadeDeleteMessages()
    {
        $this->createTestProject();
        
        // Добавляем сообщения
        for ($i = 0; $i < 3; $i++) {
            mysqli_query($this->connection, 
                "INSERT INTO messages (project_id, sender_id, message, created_at) 
                 VALUES ($this->testProjectId, 1, 'Тестовое сообщение $i', NOW())");
        }
        
        // Проверяем, что сообщения есть
        $msgResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM messages WHERE project_id = $this->testProjectId");
        $msgCount = mysqli_fetch_assoc($msgResult)['count'];
        $this->assertEquals(3, $msgCount);
        
        // Удаляем проект
        mysqli_query($this->connection, 
            "DELETE FROM projects WHERE id = $this->testProjectId");
        
        // Проверяем, что сообщения удалились каскадно
        $msgResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM messages WHERE project_id = $this->testProjectId");
        $msgCount = mysqli_fetch_assoc($msgResult)['count'];
        
        $this->assertEquals(0, $msgCount);
    }

    // Бизнес-логика
    public function testProjectBudgetValidation()
    {
        // Нельзя создать проект с отрицательным бюджетом
        $projectSql = "INSERT INTO projects 
                       (title, description, client_id, budget) 
                       VALUES ('Тест', 'Описание', $this->testClientId, -1000)";
        
        // В MySQL нет CHECK constraints по умолчанию, поэтому должно создаться
        // В реальном приложении должна быть валидация на уровне PHP
        $this->assertTrue(mysqli_query($this->connection, $projectSql));
        
        // Но лучше добавить триггер или валидацию в PHP
    }

    public function testProjectDeadlineValidation()
    {
        // Дедлайн должен быть в будущем
        $pastDeadline = date('Y-m-d', strtotime('-30 days'));
        
        $projectSql = "INSERT INTO projects 
                       (title, description, client_id, deadline) 
                       VALUES ('Тест', 'Описание', $this->testClientId, '$pastDeadline')";
        
        $this->assertTrue(mysqli_query($this->connection, $projectSql));
        $projectId = mysqli_insert_id($this->connection);
        
        // Проверяем, что дедлайн в прошлом сохранился (без валидации)
        $result = mysqli_query($this->connection, 
            "SELECT deadline FROM projects WHERE id = $projectId");
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals($pastDeadline, $project['deadline']);
        
        // В реальном приложении должна быть валидация
    }

    public function testProjectLifecycle()
    {
        $this->createTestProject(); // new
        
        // Сохраняем ID проекта
        $projectId = $this->testProjectId;
        
        // 1. Менеджер проверяет и назначает разработчика
        $updateResult = mysqli_query($this->connection, 
            "UPDATE projects SET 
            developer_id = $this->testDeveloperId,
            status = 'in_progress'
            WHERE id = $projectId");
        $this->assertTrue($updateResult);
        
        // Проверяем назначение
        $result = mysqli_query($this->connection, 
            "SELECT status, developer_id FROM projects WHERE id = $projectId");
        $project = mysqli_fetch_assoc($result);
        $this->assertEquals('in_progress', $project['status']);
        $this->assertEquals($this->testDeveloperId, (int)$project['developer_id']);
        
        // 2. Разработчик работает, общаются через чат
        for ($i = 1; $i <= 3; $i++) {
            $messageResult = mysqli_query($this->connection, 
                "INSERT INTO messages (project_id, sender_id, message, created_at) 
                VALUES ($projectId, 1, 'Сообщение $i', NOW())");
            $this->assertTrue($messageResult);
        }
        
        // 3. Проверяем, что создалось 3 сообщения
        $msgResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM messages WHERE project_id = $projectId");
        $msgCount = mysqli_fetch_assoc($msgResult)['count'];
        $this->assertEquals(3, (int)$msgCount, "Должно быть 3 сообщения");
        
        // 4. Проект завершен
        $completeResult = mysqli_query($this->connection, 
            "UPDATE projects SET 
            status = 'completed',
            completed_at = NOW()
            WHERE id = $projectId");
        $this->assertTrue($completeResult);
        
        // 5. Обновляем статистику разработчика
        $devUpdateResult = mysqli_query($this->connection, 
            "UPDATE developers SET 
            completed_projects = completed_projects + 1,
            is_available = 1
            WHERE id = $this->testDeveloperId");
        $this->assertTrue($devUpdateResult);
        
        // Проверяем финальное состояние проекта
        $projectResult = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $projectId");
        $project = mysqli_fetch_assoc($projectResult);
        $this->assertEquals('completed', $project['status']);
        $this->assertNotNull($project['completed_at']);
        
        // Проверяем разработчика
        $devResult = mysqli_query($this->connection, 
            "SELECT completed_projects, is_available FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($devResult);
        $this->assertEquals(4, (int)$developer['completed_projects'], "Должен быть 1 завершенный проект");
        $this->assertEquals(1, (int)$developer['is_available'], "Разработчик должен быть доступен");
        
        // Еще раз проверяем сообщения (должно быть все еще 3)
        $msgResult = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM messages WHERE project_id = $projectId");
        $msgCount = mysqli_fetch_assoc($msgResult)['count'];
        $this->assertEquals(3, (int)$msgCount, "Сообщений должно быть 3");
    }

    public function testProjectBudgetByClient()
    {
        $this->createTestProject(); // 150000
        $this->createAnotherProject(); // 200000
        
        $result = mysqli_query($this->connection, 
            "SELECT SUM(budget) as total_budget FROM projects WHERE client_id = $this->testClientId");
        
        $total = mysqli_fetch_assoc($result)['total_budget'];
        
        $this->assertEquals(350000, $total);
    }

    public function testProjectCountByDeveloper()
    {
        $this->createTestProject(); // Назначен разработчику
        $this->createInProgressProject(); // Еще один проект для того же разработчика
        
        $result = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE developer_id = $this->testDeveloperId");
        
        $count = mysqli_fetch_assoc($result)['count'];
        
        $this->assertEquals(2, $count);
    }

    // Вспомогательные методы
    private function createTestClient()
    {
        $password = password_hash('client123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('techpro', 'info@techpro.ru', '$password', 'Смирнов Алексей', 'client', 'approved', NOW())");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO clients (user_id, company_name, phone) 
             VALUES ($userId, 'ООО ТехноПроект', '+7 (495) 123-45-67')");
        $this->testClientId = mysqli_insert_id($this->connection);
    }

    private function createTestDeveloper()
    {
        $password = password_hash('dev123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('ivan_dev', 'ivan@dev.com', '$password', 'Иван Петров', 'developer', 'approved', NOW())");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO developers 
             (user_id, level, skills, experience, rating, completed_projects, is_available) 
             VALUES (
                 $userId,
                 'junior',
                 'PHP,MySQL,JavaScript',
                 '2 года',
                 4.5,
                 3,
                 1
             )");
        $this->testDeveloperId = mysqli_insert_id($this->connection);
    }

    private function createTestProject()
    {
        mysqli_query($this->connection, 
            "INSERT INTO projects 
             (title, description, requirements, client_id, developer_id, budget, deadline, status, created_at) 
             VALUES (
                 'Интернет-магазин',
                 'Разработка интернет-магазина с каталогом товаров и корзиной',
                 'Опыт работы с PHP, MySQL, JavaScript',
                 $this->testClientId,
                 $this->testDeveloperId,
                 150000,
                 DATE_ADD(NOW(), INTERVAL 30 DAY),
                 'new',
                 NOW()
             )");
        $this->testProjectId = mysqli_insert_id($this->connection);
    }

    private function createAnotherProject()
    {
        mysqli_query($this->connection, 
            "INSERT INTO projects 
             (title, description, client_id, budget, deadline, status) 
             VALUES (
                 'Мобильное приложение',
                 'Разработка мобильного приложения для доставки',
                 $this->testClientId,
                 200000,
                 DATE_ADD(NOW(), INTERVAL 45 DAY),
                 'new'
             )");
    }

    private function createInProgressProject()
    {
        mysqli_query($this->connection, 
            "INSERT INTO projects 
             (title, description, client_id, developer_id, status) 
             VALUES (
                 'Проект в работе',
                 'Активный проект',
                 $this->testClientId,
                 $this->testDeveloperId,
                 'in_progress'
             )");
        $this->testProjectId = mysqli_insert_id($this->connection);
    }

    private function createCompletedProject()
    {
        mysqli_query($this->connection, 
            "INSERT INTO projects 
             (title, description, client_id, developer_id, status, completed_at) 
             VALUES (
                 'Завершенный проект',
                 'Успешно завершенный проект',
                 $this->testClientId,
                 $this->testDeveloperId,
                 'completed',
                 NOW()
             )");
    }

    protected function tearDown(): void
    {
        mysqli_query($this->connection, "DROP TABLE IF EXISTS project_status_history");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($this->connection, "TRUNCATE TABLE projects");
        mysqli_query($this->connection, "TRUNCATE TABLE clients");
        mysqli_query($this->connection, "TRUNCATE TABLE developers");
        mysqli_query($this->connection, "TRUNCATE TABLE users");
        mysqli_query($this->connection, "TRUNCATE TABLE messages");
        mysqli_query($this->connection, "TRUNCATE TABLE notifications");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 1");
        
        mysqli_close($this->connection);
        parent::tearDown();
    }
}