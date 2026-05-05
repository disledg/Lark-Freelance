<?php
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $connection;
    private $testClientId;
    private $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->connection = mysqli_connect('db', 'root', 'rootpassword', 'lark_freelance');
        $GLOBALS['connection'] = $this->connection;
        
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($this->connection, "TRUNCATE TABLE clients");
        mysqli_query($this->connection, "TRUNCATE TABLE users");
        mysqli_query($this->connection, "TRUNCATE TABLE client_applications");
        mysqli_query($this->connection, "TRUNCATE TABLE projects");
        mysqli_query($this->connection, "TRUNCATE TABLE notifications");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 1");
    }

    // CREATE тесты
    public function testCreateClientFromApplication()
    {
        // Создаем заявку клиента
        $appSql = "INSERT INTO client_applications 
                   (company_name, contact_person, email, phone, project_description, budget_range, status) 
                   VALUES (
                       'ООО Ромашка',
                       'Иванов Иван Иванович',
                       'ivan@romashka.ru',
                       '+7 (999) 123-45-67',
                       'Разработка интернет-магазина',
                       '50000-100000',
                       'approved'
                   )";
        
        $this->assertTrue(mysqli_query($this->connection, $appSql));

        // Создаем пользователя
        $password = password_hash('client123', PASSWORD_DEFAULT);
        $userSql = "INSERT INTO users 
                    (username, email, password, full_name, role, status, created_at) 
                    VALUES (
                        'romashka',
                        'ivan@romashka.ru',
                        '$password',
                        'Иванов Иван Иванович',
                        'client',
                        'approved',
                        NOW()
                    )";
        
        $this->assertTrue(mysqli_query($this->connection, $userSql));
        $this->testUserId = mysqli_insert_id($this->connection);

        // Создаем профиль клиента
        $clientSql = "INSERT INTO clients 
                      (user_id, company_name, phone, telegram, company_site) 
                      VALUES (
                          $this->testUserId,
                          'ООО Ромашка',
                          '+7 (999) 123-45-67',
                          '@romashka_company',
                          'https://romashka.ru'
                      )";
        
        $this->assertTrue(mysqli_query($this->connection, $clientSql));
        $this->testClientId = mysqli_insert_id($this->connection);

        // Проверяем создание
        $result = mysqli_query($this->connection, 
            "SELECT c.*, u.full_name, u.email, u.status 
             FROM clients c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.id = $this->testClientId");
        
        $client = mysqli_fetch_assoc($result);
        
        $this->assertEquals('ООО Ромашка', $client['company_name']);
        $this->assertEquals('ivan@romashka.ru', $client['email']);
        $this->assertEquals('Иванов Иван Иванович', $client['full_name']);
        $this->assertEquals('approved', $client['status']);
    }

    public function testCreateClientWithMinimalData()
    {
        $password = password_hash('client123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status) 
             VALUES ('minimal', 'minimal@test.ru', '$password', 'Минимальный Клиент', 'client', 'approved')");
        $userId = mysqli_insert_id($this->connection);

        // Только обязательные поля
        $clientSql = "INSERT INTO clients (user_id, company_name) 
                      VALUES ($userId, 'ИП Минимальный')";
        
        $this->assertTrue(mysqli_query($this->connection, $clientSql));
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM clients WHERE user_id = $userId");
        $client = mysqli_fetch_assoc($result);
        
        $this->assertEquals('ИП Минимальный', $client['company_name']);
        $this->assertNull($client['phone']);
        $this->assertNull($client['company_site']);
    }

    // READ тесты
    public function testGetClientById()
    {
        $this->createTestClient();
        
        $result = mysqli_query($this->connection, 
            "SELECT c.*, u.full_name, u.email 
             FROM clients c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.id = $this->testClientId");
        
        $client = mysqli_fetch_assoc($result);
        
        $this->assertNotNull($client);
        $this->assertEquals('ООО ТехноПроект', $client['company_name']);
    }

    public function testGetClientByUserId()
    {
        $this->createTestClient();
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM clients WHERE user_id = $this->testUserId");
        
        $client = mysqli_fetch_assoc($result);
        
        $this->assertNotNull($client);
        $this->assertEquals($this->testUserId, $client['user_id']);
    }

    public function testGetAllClients()
    {
        $this->createTestClient(); // ООО ТехноПроект
        $this->createAnotherClient(); // ООО СтройИнвест
        
        $result = mysqli_query($this->connection, 
            "SELECT c.*, u.full_name, u.email, u.status 
             FROM clients c 
             JOIN users u ON c.user_id = u.id 
             ORDER BY c.id DESC");
        
        $clients = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $clients[] = $row;
        }
        
        $this->assertCount(2, $clients);
        $this->assertEquals('ООО СтройИнвест', $clients[0]['company_name']);
        $this->assertEquals('ООО ТехноПроект', $clients[1]['company_name']);
    }

    public function testGetActiveClients()
    {
        $this->createTestClient(); // approved
        $this->createAnotherClient(); // approved
        $this->createPendingClient(); // pending
        
        $result = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count 
             FROM clients c 
             JOIN users u ON c.user_id = u.id 
             WHERE u.status = 'approved'");
        
        $count = mysqli_fetch_assoc($result)['count'];
        
        $this->assertEquals(2, $count);
    }

    public function testGetClientProjects()
    {
        $this->createTestClient();
        
        // Создаем несколько проектов для клиента
        for ($i = 1; $i <= 3; $i++) {
            mysqli_query($this->connection, 
                "INSERT INTO projects (title, description, client_id, status, budget) 
                 VALUES ('Проект $i', 'Описание проекта $i', $this->testClientId, 'new', 50000)");
        }
        
        $result = mysqli_query($this->connection, 
            "SELECT COUNT(*) as count FROM projects WHERE client_id = $this->testClientId");
        
        $count = mysqli_fetch_assoc($result)['count'];
        
        $this->assertEquals(3, $count);
    }

    // UPDATE тесты
    public function testUpdateClientProfile()
    {
        $this->createTestClient();
        
        // Обновляем данные
        $updateSql = "UPDATE clients SET 
                      company_name = 'ООО ТехноПроект (переименовано)',
                      phone = '+7 (999) 888-77-66',
                      telegram = '@techpro_new',
                      company_site = 'https://techpro-new.ru'
                      WHERE id = $this->testClientId";
        
        $this->assertTrue(mysqli_query($this->connection, $updateSql));
        
        // Обновляем контактное лицо
        mysqli_query($this->connection, 
            "UPDATE users SET full_name = 'Петров Петр Петрович' WHERE id = $this->testUserId");
        
        // Проверяем обновления
        $result = mysqli_query($this->connection, 
            "SELECT c.*, u.full_name 
             FROM clients c 
             JOIN users u ON c.user_id = u.id 
             WHERE c.id = $this->testClientId");
        
        $client = mysqli_fetch_assoc($result);
        
        $this->assertEquals('ООО ТехноПроект (переименовано)', $client['company_name']);
        $this->assertEquals('+7 (999) 888-77-66', $client['phone']);
        $this->assertEquals('@techpro_new', $client['telegram']);
        $this->assertEquals('https://techpro-new.ru', $client['company_site']);
        $this->assertEquals('Петров Петр Петрович', $client['full_name']);
    }

    public function testUpdateClientStatus()
    {
        $this->createTestClient();
        
        // Менеджер блокирует клиента
        mysqli_query($this->connection, 
            "UPDATE users SET status = 'rejected' WHERE id = $this->testUserId");
        
        $result = mysqli_query($this->connection, 
            "SELECT status FROM users WHERE id = $this->testUserId");
        $user = mysqli_fetch_assoc($result);
        
        $this->assertEquals('rejected', $user['status']);
    }

    // DELETE тесты
    public function testSoftDeleteClient()
    {
        $this->createTestClient();
        
        // Мягкое удаление
        mysqli_query($this->connection, 
            "UPDATE users SET status = 'rejected' WHERE id = $this->testUserId");
        
        $result = mysqli_query($this->connection, 
            "SELECT status FROM users WHERE id = $this->testUserId");
        $user = mysqli_fetch_assoc($result);
        
        $this->assertEquals('rejected', $user['status']);
        
        // Клиент все еще существует
        $clientResult = mysqli_query($this->connection, 
            "SELECT * FROM clients WHERE id = $this->testClientId");
        $this->assertEquals(1, mysqli_num_rows($clientResult));
    }

    public function testHardDeleteClient()
    {
        $this->createTestClient();
        
        // Полное удаление (каскадное)
        mysqli_query($this->connection, "DELETE FROM users WHERE id = $this->testUserId");
        
        $result = mysqli_query($this->connection, 
            "SELECT * FROM clients WHERE id = $this->testClientId");
        
        $this->assertEquals(0, mysqli_num_rows($result));
    }

    public function testCascadeDeleteProjects()
    {
        $this->createTestClient();
        
        // Создаем проект
        mysqli_query($this->connection, 
            "INSERT INTO projects (title, description, client_id, status) 
             VALUES ('Тестовый проект', 'Описание', $this->testClientId, 'new')");
        $projectId = mysqli_insert_id($this->connection);
        
        // Удаляем клиента
        mysqli_query($this->connection, "DELETE FROM users WHERE id = $this->testUserId");
        
        // Проверяем, что проекты тоже удалились (каскадное удаление)
        $result = mysqli_query($this->connection, 
            "SELECT * FROM projects WHERE id = $projectId");
        
        $this->assertEquals(0, mysqli_num_rows($result));
    }

    // Бизнес-логика
    public function testClientCanCreateProject()
    {
        $this->createTestClient();
        
        // Создаем проект
        $projectSql = "INSERT INTO projects 
                       (title, description, client_id, requirements, budget, deadline, status) 
                       VALUES (
                           'Интернет-магазин',
                           'Разработка интернет-магазина с каталогом и корзиной',
                           $this->testClientId,
                           'Опыт работы с Laravel, знание JavaScript',
                           150000,
                           DATE_ADD(NOW(), INTERVAL 60 DAY),
                           'new'
                       )";
        
        $this->assertTrue(mysqli_query($this->connection, $projectSql));
        $projectId = mysqli_insert_id($this->connection);
        
        // Проверяем создание
        $result = mysqli_query($this->connection, 
            "SELECT p.*, c.company_name 
             FROM projects p 
             JOIN clients c ON p.client_id = c.id 
             WHERE p.id = $projectId");
        
        $project = mysqli_fetch_assoc($result);
        
        $this->assertEquals('Интернет-магазин', $project['title']);
        $this->assertEquals($this->testClientId, $project['client_id']);
        $this->assertEquals('ООО ТехноПроект', $project['company_name']);
        $this->assertEquals('new', $project['status']);
    }

    public function testGetClientProjectStats()
    {
        $this->createTestClient();
        
        // Создаем проекты с разными статусами
        $statuses = ['new', 'new', 'in_progress', 'completed', 'cancelled'];
        foreach ($statuses as $status) {
            mysqli_query($this->connection, 
                "INSERT INTO projects (title, description, client_id, status) 
                 VALUES ('Проект', 'Описание', $this->testClientId, '$status')");
        }
        
        $stats = [
            'total' => 0,
            'new' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'cancelled' => 0
        ];
        
        $result = mysqli_query($this->connection, 
            "SELECT status, COUNT(*) as count 
             FROM projects 
             WHERE client_id = $this->testClientId 
             GROUP BY status");
        
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[$row['status']] = $row['count'];
            $stats['total'] += $row['count'];
        }
        
        $this->assertEquals(5, $stats['total']);
        $this->assertEquals(2, $stats['new']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['completed']);
        $this->assertEquals(1, $stats['cancelled']);
    }

    public function testClientBudgetTotal()
    {
        $this->createTestClient();
        
        // Создаем проекты с бюджетами
        $budgets = [50000, 75000, 100000];
        foreach ($budgets as $budget) {
            mysqli_query($this->connection, 
                "INSERT INTO projects (title, description, client_id, budget, status) 
                 VALUES ('Проект', 'Описание', $this->testClientId, $budget, 'new')");
        }
        
        $result = mysqli_query($this->connection, 
            "SELECT SUM(budget) as total_budget FROM projects WHERE client_id = $this->testClientId");
        
        $total = mysqli_fetch_assoc($result)['total_budget'];
        
        $this->assertEquals(225000, $total);
    }

    // Вспомогательные методы
    private function createTestClient()
    {
        $password = password_hash('client123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('techpro', 'info@techpro.ru', '$password', 'Смирнов Алексей', 'client', 'approved', NOW())");
        $this->testUserId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO clients (user_id, company_name, phone, telegram, company_site) 
             VALUES (
                 $this->testUserId,
                 'ООО ТехноПроект',
                 '+7 (495) 123-45-67',
                 '@techpro_company',
                 'https://techpro.ru'
             )");
        $this->testClientId = mysqli_insert_id($this->connection);
    }

    private function createAnotherClient()
    {
        $password = password_hash('client123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('stroyinvest', 'info@stroyinvest.ru', '$password', 'Петров Иван', 'client', 'approved', NOW())");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO clients (user_id, company_name, phone) 
             VALUES ($userId, 'ООО СтройИнвест', '+7 (495) 765-43-21')");
    }

    private function createPendingClient()
    {
        $password = password_hash('client123', PASSWORD_DEFAULT);
        mysqli_query($this->connection, 
            "INSERT INTO users (username, email, password, full_name, role, status, created_at) 
             VALUES ('pending', 'pending@test.ru', '$password', 'Ожидающий Клиент', 'client', 'pending', NOW())");
        $userId = mysqli_insert_id($this->connection);

        mysqli_query($this->connection, 
            "INSERT INTO clients (user_id, company_name) 
             VALUES ($userId, 'ООО В ожидании')");
    }

    protected function tearDown(): void
    {
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 0");
        mysqli_query($this->connection, "TRUNCATE TABLE clients");
        mysqli_query($this->connection, "TRUNCATE TABLE users");
        mysqli_query($this->connection, "TRUNCATE TABLE client_applications");
        mysqli_query($this->connection, "TRUNCATE TABLE projects");
        mysqli_query($this->connection, "SET FOREIGN_KEY_CHECKS = 1");
        
        mysqli_close($this->connection);
        parent::tearDown();
    }
}