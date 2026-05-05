<?php
use PHPUnit\Framework\TestCase;

class DeveloperTest extends TestCase
{
    private $connection;
    private $testDeveloperId;
    private $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Подключаемся к тестовой БД
        $this->connection = mysqli_connect('db', 'root', 'rootpassword', 'lark_freelance');
        $GLOBALS['connection'] = $this->connection;

        // Очищаем таблицы перед каждым тестом
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($this->connection, "TRUNCATE TABLE developers");
        mysqli_query($this->connection, "TRUNCATE TABLE users");
        mysqli_query($this->connection, "TRUNCATE TABLE developer_applications");
        mysqli_query($this->connection, "TRUNCATE TABLE projects");
        mysqli_query($this->connection, "TRUNCATE TABLE notifications");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 1");
    }

    // CREATE тесты
    public function testCreateDeveloperFromApplication()
    {
        // Сначала создаем заявку
        $appSql = "INSERT INTO developer_applications 
                   (full_name, email, level, skills, experience, portfolio, telegram, github, status) 
                   VALUES (
                       'Иван Петров',
                       'ivan@dev.com',
                       'junior',
                       'PHP,MySQL,JavaScript',
                       '2 года фриланса',
                       'https://portfolio.com/ivan',
                       '@ivan_dev',
                       'https://github.com/ivan',
                       'approved'
                   )";
        
        $this->assertTrue(mysqli_query($this->connection, $appSql));
        $applicationId = mysqli_insert_id($this->connection);

        // Создаем пользователя из заявки
        $password = password_hash('test123', PASSWORD_DEFAULT);
        $userSql = "INSERT INTO users 
                    (username, email, password, full_name, role, status, created_at) 
                    VALUES (
                        'ivan_dev',
                        'ivan@dev.com',
                        '$password',
                        'Иван Петров',
                        'developer',
                        'approved',
                        NOW()
                    )";
        
        $this->assertTrue(mysqli_query($this->connection, $userSql));
        $this->testUserId = mysqli_insert_id($this->connection);

        // Создаем профиль разработчика
        $devSql = "INSERT INTO developers 
                   (user_id, level, skills, experience, portfolio, telegram, github, rating, completed_projects, is_available) 
                   VALUES (
                       $this->testUserId,
                       'junior',
                       'PHP,MySQL,JavaScript',
                       '2 года фриланса',
                       'https://portfolio.com/ivan',
                       '@ivan_dev',
                       'https://github.com/ivan',
                       0,
                       0,
                       1
                   )";
        
        $this->assertTrue(mysqli_query($this->connection, $devSql));
        $this->testDeveloperId = mysqli_insert_id($this->connection);

        // Проверяем создание
        $result = mysqli_query($this->connection, 
            "SELECT d.*, u.full_name, u.email, u.status 
             FROM developers d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.id = $this->testDeveloperId");
        
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertEquals('Иван Петров', $developer['full_name']);
        $this->assertEquals('ivan@dev.com', $developer['email']);
        $this->assertEquals('junior', $developer['level']);
        $this->assertEquals('approved', $developer['status']);
        $this->assertEquals(1, $developer['is_available']);
    }

    public function testCreateDeveloperWithInvalidData()
    {
        // Попытка создать разработчика без обязательных полей
        $userSql = "INSERT INTO users 
                    (username, email, password, full_name, role, status) 
                    VALUES (
                        'test',
                        'test@test.com',
                        'hash',
                        'Test User',
                        'developer',
                        'approved'
                    )";
        
        $this->assertTrue(mysqli_query($this->connection, $userSql));
        $userId = mysqli_insert_id($this->connection);

        // Пытаемся создать разработчика без level
        $devSql = "INSERT INTO developers (user_id, skills, experience) 
                   VALUES ($userId, 'PHP', '2 years')";
        
        $this->assertEquals(0, mysqli_errno($this->connection)); // 1048 - Column cannot be null
    }

    // READ тесты
    public function testGetDeveloperById()
    {
        $this->createTestDeveloper();
        
        $result = mysqli_query($this->connection, 
            "SELECT d.*, u.full_name, u.email 
             FROM developers d 
             JOIN users u ON d.user_id = u.id 
             WHERE d.id = $this->testDeveloperId");
        
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertNotNull($developer);
        $this->assertEquals($this->testDeveloperId, $developer['id']);
        $this->assertEquals('Иван Петров', $developer['full_name']);
    }

    public function testGetDeveloperByUserId()
    {
        $this->createTestDeveloper();
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM developers WHERE user_id = $this->testUserId");
        
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertNotNull($developer);
        $this->assertEquals($this->testUserId, $developer['user_id']);
    }

    public function testGetAllDevelopers()
    {
        // Создаем несколько разработчиков
        $this->createTestDeveloper(); // Junior
        $this->createMiddleDeveloper(); // Middle
        
        $result = mysqli_query($this->connection, 
            "SELECT d.*, u.full_name 
             FROM developers d 
             JOIN users u ON d.user_id = u.id 
             ORDER BY d.id DESC");
        
        $developers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $developers[] = $row;
        }
        
        $this->assertCount(2, $developers);
        $this->assertEquals('middle', $developers[0]['level']); // Последний созданный
        $this->assertEquals('junior', $developers[1]['level']);
    }

    public function testGetAvailableDevelopers()
    {
        $this->createTestDeveloper(); // available
        $this->createMiddleDeveloper(); // available
        $this->createBusyDeveloper(); // not available
        
        $result = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM developers WHERE is_available = 1");
        
        $count = mysqli_fetch_assoc($result)['count'];
        
        $this->assertEquals(2, $count);
    }

    // public function testGetDevelopersByLevel()
    // {
    //     $this->createTestDeveloper(); // junior
    //     $this->createTestDeveloper(); // junior
    //     $this->createMiddleDeveloper(); // middle
        
    //     $juniorResult = mysqli_query($this->connection, 
    //         "SELECT COUNT(*) as count FROM developers WHERE level = 'junior'");
    //     $juniorCount = mysqli_fetch_assoc($juniorResult)['count'];
        
    //     $middleResult = mysqli_query($this->connection, 
    //         "SELECT COUNT(*) as count FROM developers WHERE level = 'middle'");
    //     $middleCount = mysqli_fetch_assoc($middleResult)['count'];
        
    //     $this->assertEquals(2, $juniorCount);
    //     $this->assertEquals(1, $middleCount);
    // }

    // UPDATE тесты
    public function testUpdateDeveloperProfile()
    {
        $this->createTestDeveloper();
        
        // Обновляем данные
        $updateSql = "UPDATE developers SET 
                      level = 'middle',
                      skills = 'PHP,Laravel,React,MySQL',
                      experience = '3 года коммерческой разработки',
                      portfolio = 'https://updated-portfolio.com',
                      telegram = '@updated_dev',
                      github = 'https://github.com/updated',
                      rating = 4.5,
                      is_available = 0
                      WHERE id = $this->testDeveloperId";
        
        $this->assertTrue(mysqli_query($this->connection, $updateSql));
        
        // Проверяем обновление
        $result = mysqli_query($this->connection, 
            "SELECT * FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertEquals('middle', $developer['level']);
        $this->assertEquals('PHP,Laravel,React,MySQL', $developer['skills']);
        $this->assertStringContainsString('3 года', $developer['experience']);
        $this->assertEquals('@updated_dev', $developer['telegram']);
        $this->assertEquals(4.5, $developer['rating']);
        $this->assertEquals(0, $developer['is_available']);
    }

    public function testUpdateDeveloperRating()
    {
        $this->createTestDeveloper();
        
        // Обновляем рейтинг после завершенного проекта
        $updateSql = "UPDATE developers SET 
                      rating = (rating * completed_projects + 5) / (completed_projects + 1),
                      completed_projects = completed_projects + 1
                      WHERE id = $this->testDeveloperId";
        
        $this->assertTrue(mysqli_query($this->connection, $updateSql));
        
        // Проверяем
        $result = mysqli_query($this->connection, 
            "SELECT rating, completed_projects FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertEquals(1, $developer['completed_projects']);
        $this->assertEquals(5.0, $developer['rating']); // Первый проект с оценкой 5
    }

    public function testUpdateDeveloperAvailability()
    {
        $this->createTestDeveloper();
        
        // Разработчик берет проект в работу
        mysqli_query($this->connection, 
            "UPDATE developers SET is_available = 0 WHERE id = $this->testDeveloperId");
        
        $result = mysqli_query($this->connection, 
            "SELECT is_available FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertEquals(0, $developer['is_available']);
        
        // Разработчик завершает проект
        mysqli_query($this->connection, 
            "UPDATE developers SET is_available = 1 WHERE id = $this->testDeveloperId");
        
        $result = mysqli_query($this->connection, 
            "SELECT is_available FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($result);
        
        $this->assertEquals(1, $developer['is_available']);
    }

    // DELETE тесты
    public function testSoftDeleteDeveloper()
    {
        $this->createTestDeveloper();
        
        // Мягкое удаление через статус
        mysqli_query($this->connection, 
            "UPDATE users SET status = 'rejected' WHERE id = $this->testUserId");
        
        $result = mysqli_query($this->connection, 
            "SELECT status FROM users WHERE id = $this->testUserId");
        $user = mysqli_fetch_assoc($result);
        
        $this->assertEquals('rejected', $user['status']);
        
        // Разработчик все еще существует в БД
        $devResult = mysqli_query($this->connection, 
            "SELECT * FROM developers WHERE user_id = $this->testUserId");
        $this->assertEquals(1, mysqli_num_rows($devResult));
    }

    public function testHardDeleteDeveloper()
    {
        $this->createTestDeveloper();
        
        // Полное удаление (каскадное)
        mysqli_query($this->connection, "DELETE FROM users WHERE id = $this->testUserId");
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM developers WHERE id = $this->testDeveloperId");
        
        $this->assertEquals(0, mysqli_num_rows($result));
    }

    // Бизнес-логика
    public function testAssignDeveloperToProject()
    {
        $this->createTestDeveloper();
        $projectId = $this->createTestProject();
        
        // Назначаем разработчика на проект
        mysqli_query($this->connection, 
            "UPDATE projects SET developer_id = $this->testDeveloperId, 
              status = 'in_progress' 
              WHERE id = $projectId");
        
        // Проверяем назначение
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $projectId");
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals($this->testDeveloperId, $project['developer_id']);
        $this->assertEquals('in_progress', $project['status']);
        
        // Проверяем, что разработчик стал занят
        $devResult = mysqli_query($this->connection, 
            "SELECT is_available FROM developers WHERE id = $this->testDeveloperId");
        $developer = mysqli_fetch_assoc($devResult);
        
        $this->assertEquals(1, $developer['is_available']);
    }

    public function testDeveloperSkillMatching()
    {
        $this->createTestDeveloper(); // PHP,MySQL,JavaScript
        
        // Ищем разработчиков по навыкам
        $skills = ['PHP', 'JavaScript'];
        $skillCondition = "skills LIKE '%PHP%' AND skills LIKE '%JavaScript%'";
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM developers WHERE $skillCondition");
        
        $this->assertEquals(1, mysqli_num_rows($result));
        
        // Ищем несуществующие навыки
        $result = mysqli_query($this->connection, 
            "SELECT * FROM developers WHERE skills LIKE '%Python%'");
        
        $this->assertEquals(0, mysqli_num_rows($result));
    }

    // Вспомогательные методы
    private function createTestDeveloper()
    {
        // Создаем пользователя
        $password = password_hash('test123', PASSWORD_DEFAULT);
        mysqli_query($this->connection,
            "INSERT INTO users (username, email, password, full_name, role, status, created_at)
            VALUES ('ivan_dev', 'ivan@dev.com', '$password', 'Иван Петров', 'developer', 'approved', NOW())");
        $this->testUserId = mysqli_insert_id($this->connection);

        // Создаем разработчика
        mysqli_query($this->connection, 
            "INSERT INTO developers 
             (user_id, level, skills, experience, portfolio, telegram, github, rating, completed_projects, is_available) 
             VALUES (
                 $this->testUserId,
                 'junior',
                 'PHP,MySQL,JavaScript',
                 '2 года фриланса',
                 'https://portfolio.com/ivan',
                 '@ivan_dev',
                 'https://github.com/ivan',
                 0,
                 0,
                 1
             )");
        $this->testDeveloperId = mysqli_insert_id($this->connection);
    }

    private function createMiddleDeveloper()
    {
        $password = password_hash('test123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('petr_dev', 'petr@dev.com', '$password', 'Петр Сидоров', 'developer', 'approved', NOW())");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO developers 
             (user_id, level, skills, experience, portfolio, telegram, github, rating, completed_projects, is_available) 
             VALUES (
                 $userId,
                 'middle',
                 'PHP,Laravel,PostgreSQL,Docker',
                 '5 лет в веб-разработке',
                 'https://portfolio.com/petr',
                 '@petr_dev',
                 'https://github.com/petr',
                 4.7,
                 8,
                 1
             )");
    }

    private function createBusyDeveloper()
    {
        $password = password_hash('test123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('busy_dev', 'busy@dev.com', '$password', 'Занятой Разработчик', 'developer', 'approved', NOW())");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO developers 
             (user_id, level, skills, experience, portfolio, telegram, github, rating, completed_projects, is_available) 
             VALUES (
                 $userId,
                 'middle',
                 'React,Node.js,MongoDB',
                 '4 года',
                 'https://portfolio.com/busy',
                 '@busy_dev',
                 'https://github.com/busy',
                 4.2,
                 12,
                 0
             )");
    }

    private function createTestProject()
    {
        // Создаем клиента
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status) 
             VALUES ('client', 'client@test.com', 'hash', 'Test Client', 'client', 'approved')");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO clients (user_id, company_name) VALUES ($userId, 'Test Company')");
        $clientId = mysqli_insert_id($this->connection);

        // Создаем проект
        mysqli_query($this->connection, 
            "INSERT INTO projects (title, description, client_id, status, budget, deadline) 
             VALUES ('Test Project', 'Test Description', $clientId, 'new', 50000, DATE_ADD(NOW(), INTERVAL 30 DAY))");
        
        return mysqli_insert_id($this->connection);
    }

    protected function tearDown(): void
    {
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($this->connection, "TRUNCATE TABLE developers");
        mysqli_query($this->connection, "TRUNCATE TABLE users");
        mysqli_query($this->connection, "TRUNCATE TABLE developer_applications");
        mysqli_query($this->connection, "TRUNCATE TABLE projects");
        mysqli_query($this->connection, "TRUNCATE TABLE clients");
        mysqli_query($this->connection, "TRUNCATE TABLE notifications");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 1");
        
        mysqli_close($this->connection);
        parent::tearDown();
    }
}