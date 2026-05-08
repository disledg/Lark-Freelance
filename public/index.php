<?php
// Подключаем базу данных
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/cases.php';

// Проверяем подключение и получаем данные
$stats = [
    'projects' => 0,
    'developers' => 0,
    'clients' => 0
];

// Получаем количество проектов
$result = query("SELECT COUNT(*) as count FROM projects WHERE status = 'completed'");
if ($result) {
    $row = fetch($result);
    $stats['projects'] = $row['count'];
}

// Получаем количество разработчиков
$result = query("SELECT COUNT(*) as count FROM users WHERE role = 'developer' AND status = 'approved'");
if ($result) {
    $row = fetch($result);
    $stats['developers'] = $row['count'];
}

// Получаем количество клиентов
$result = query("SELECT COUNT(*) as count FROM users WHERE role = 'client' AND status = 'approved'");
if ($result) {
    $row = fetch($result);
    $stats['clients'] = $row['count'];
}

// Получаем специалистов из базы данных
$specialists = [];
$result = query("
    SELECT u.full_name, d.level, d.skills, d.experience, d.rating
    FROM developers d
    JOIN users u ON d.user_id = u.id
    WHERE u.status = 'approved' AND d.is_available = TRUE
    ORDER BY d.rating DESC
    LIMIT 4
");
if ($result) {
    while ($row = fetch($result)) {
        $specialists[] = [
            'full_name' => $row['full_name'],
            'level' => $row['level'],
            'skills' => $row['skills'],
            'experience' => $row['experience'],
            'rating' => (float)$row['rating']
        ];
    }
}

// Если нет специалистов в БД, используем демо-данные
if (empty($specialists)) {
    $specialists = [
        ['full_name' => 'Иван Петров', 'level' => 'junior', 'skills' => '["React","JS","HTML"]', 'experience' => 'Фронтенд-разработчик', 'rating' => 3.5],
        ['full_name' => 'Анна Смирнова', 'level' => 'middle', 'skills' => '["PHP","Laravel","MySQL"]', 'experience' => 'Бэкенд-разработчик', 'rating' => 4.2],
        ['full_name' => 'Дмитрий Иванов', 'level' => 'junior', 'skills' => '["Python","Django"]', 'experience' => 'Разработчик на Python', 'rating' => 3.8],
        ['full_name' => 'Елена Козлова', 'level' => 'middle', 'skills' => '["UI/UX","Figma"]', 'experience' => 'UI/UX дизайнер', 'rating' => 4.5]
    ];
}

// Получаем кейсы из базы данных
$cases = [];
try {
    $result = getAllCases(true); // Только избранные кейсы
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $cases[] = [
                'title' => $row['title'],
                'company_name' => $row['company_name'],
                'description' => $row['description'],
                'deadline' => $row['deadline'],
                'budget' => (float)$row['budget']
            ];
        }
    }
} catch (Exception $e) {
    // Если таблицы cases нет, используем демо-данные
    error_log("Таблица cases не найдена, используем демо-данные: " . $e->getMessage());
}

// Если нет кейсов в БД, используем демо-данные
if (empty($cases)) {
    $cases = [
        ['title' => 'Платформа для онлайн-ритейлера', 'company_name' => 'E-COMMERCE', 'description' => 'Разработка высоконагруженной платформы с обработкой 10,000+ заказов в день', 'deadline' => '45 дней', 'budget' => 50000],
        ['title' => 'Приложение для фитнес-трекинга', 'company_name' => 'MOBILE APP', 'description' => 'Кроссплатформенное приложение с интеграцией умных устройств и аналитикой', 'deadline' => '60 дней', 'budget' => 75000]
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lark Freelance | Футуристичная IT-платформа</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Exo+2:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="dark-theme">
    <!-- Анимированный фон -->
    <div class="cyber-background">
        <div class="grid-lines"></div>
        <div class="floating-shapes">
            <div class="shape shape-1"></div>
            <div class="shape shape-2"></div>
            <div class="shape shape-3"></div>
        </div>
        <div class="particles-container" id="particles"></div>
    </div>

    <!-- Шапка с футуристичным дизайном -->
    <header class="cyber-header">
        <div class="container">
            <nav class="cyber-nav">
                <!-- Логотип с золотыми буквами и подсветкой -->
                <a href="/" class="cyber-logo">
                    <div class="logo-glow"></div>
                    <div class="logo-text">
                        <span class="logo-gold">LARK</span>
                        <span class="logo-light">FREELANCE</span>
                    </div>
                    <div class="logo-line"></div>
                </a>

                <div class="nav-hologram">
                    <div class="hologram-line"></div>
                    <a href="#about" class="nav-link" data-text="О НАС">О НАС</a>
                    <a href="#how-it-works" class="nav-link" data-text="КАК ЭТО РАБОТАЕТ">КАК ЭТО РАБОТАЕТ</a>
                    <a href="#specialists" class="nav-link" data-text="СПЕЦИАЛИСТЫ">СПЕЦИАЛИСТЫ</a>
                    <a href="#cases" class="nav-link" data-text="КЕЙСЫ">КЕЙСЫ</a>
                    <a href="#pricing" class="nav-link" data-text="ТАРИФЫ">ТАРИФЫ</a>
                    <a href="#contact" class="nav-link" data-text="КОНТАКТЫ">КОНТАКТЫ</a>
                    
                    <div class="auth-buttons">
                        <a href="client/login.php" class="nav-link client-portal" data-text="ЗАКАЗЧИК">
                            <i class="fas fa-building"></i> ЗАКАЗЧИК
                        </a>
                        <a href="developer/login.php" class="nav-link dev-portal" data-text="РАЗРАБОТЧИК">
                            <i class="fas fa-code"></i> РАЗРАБОТЧИК
                        </a>
                        <a href="admin/login.php" class="nav-link admin-portal" data-text="МЕНЕДЖЕР">
                            <i class="fas fa-terminal"></i> МЕНЕДЖЕР
                        </a>
                    </div>
                </div>

                <!-- Мобильное меню -->
                <button class="cyber-menu-btn" id="menuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </nav>
        </div>
    </header>

    <!-- Герой-секция -->
    <section class="cyber-hero">
        <div class="container">
            <div class="hero-matrix">
                <div class="matrix-line"></div>
                <div class="hero-content">
                    <h1 class="cyber-title">
                        <span class="title-glow">МОСТЫ МЕЖДУ</span>
                        <span class="title-gold">ТАЛАНТОМ</span>
                        <span class="title-glow">И БИЗНЕСОМ</span>
                    </h1>
                    
                    <p class="cyber-subtitle">
                        Платформа для профессионального взаимодействия 
                        <span class="text-highlight">IT-специалистов</span> и 
                        <span class="text-highlight">заказчиков</span> через надежное посредничество менеджеров
                    </p>

                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $stats['projects'] ?>"><?= $stats['projects'] ?></div>
                            <div class="stat-label">Успешных проектов</div>
                        </div>
                        <div class="stat-divider">//</div>
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $stats['developers'] ?>"><?= $stats['developers'] ?></div>
                            <div class="stat-label">Специалистов</div>
                        </div>
                        <div class="stat-divider">//</div>
                        <div class="stat-item">
                            <div class="stat-number" data-count="<?= $stats['clients'] ?>"><?= $stats['clients'] ?></div>
                            <div class="stat-label">Клиентов</div>
                        </div>
                    </div>

                    <div class="cyber-buttons">
                        <a href="developer/register.php" class="btn-cyber btn-gold">
                            <span class="btn-glow"></span>
                            <span class="btn-text">СТАТЬ РАЗРАБОТЧИКОМ</span>
                            <i class="fas fa-arrow-right btn-icon"></i>
                        </a>
                        <a href="client/projects/create.php" class="btn-cyber btn-neon">
                            <span class="btn-glow"></span>
                            <span class="btn-text">РАЗМЕСТИТЬ ПРОЕКТ</span>
                            <i class="fas fa-briefcase btn-icon"></i>
                        </a>
                    </div>
                </div>
                <div class="matrix-line"></div>
            </div>
        </div>
    </section>

    <!-- Секция "О нас" -->
    <section id="about" class="cyber-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-gold">// О НАС</span>
                    <div class="title-line"></div>
                </h2>
                <p class="section-subtitle">Футуристичный подход к IT-рекрутингу</p>
            </div>

            <div class="about-grid">
                <div class="about-card cyber-card">
                    <div class="card-icon">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h3>Умный подбор</h3>
                    <p>Менеджеры тщательно подбирают разработчиков под каждый проект, учитывая все требования и особенности задачи</p>
                </div>

                <div class="about-card cyber-card">
                    <div class="card-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Посредничество</h3>
                    <p>Вся коммуникация проходит через менеджера, что исключает недопонимание и гарантирует качество работы</p>
                </div>

                <div class="about-card cyber-card">
                    <div class="card-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Гарантии</h3>
                    <p>Мы контролируем каждый этап работы и гарантируем выполнение обязательств обеими сторонами</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Секция "Как это работает" -->
    <section id="how-it-works" class="cyber-section dark-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-gold">// КАК ЭТО РАБОТАЕТ</span>
                    <div class="title-line"></div>
                </h2>
                <p class="section-subtitle">Процесс работы на платформе</p>
            </div>

            <div class="steps-grid">
                <div class="step-card cyber-card">
                    <div class="step-number">1</div>
                    <h3>Заявка</h3>
                    <p>Разработчик или заказчик оставляет заявку на сайте</p>
                </div>

                <div class="step-card cyber-card">
                    <div class="step-number">2</div>
                    <h3>Модерация</h3>
                    <p>Менеджер проверяет заявку и принимает решение</p>
                </div>

                <div class="step-card cyber-card">
                    <div class="step-number">3</div>
                    <h3>Проект</h3>
                    <p>Заказчик создает проект, менеджер назначает разработчика</p>
                </div>

                <div class="step-card cyber-card">
                    <div class="step-number">4</div>
                    <h3>Работа</h3>
                    <p>Коммуникация через менеджера, контроль качества на всех этапах</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Секция "Специалисты" -->
    <section id="specialists" class="cyber-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-gold">// НАШИ СПЕЦИАЛИСТЫ</span>
                    <div class="title-line"></div>
                </h2>
                <p class="section-subtitle">Лучшие разработчики на платформе</p>
            </div>

            <div class="specialists-grid">
                <?php foreach ($specialists as $spec): 
                    $skills = json_decode($spec['skills'], true) ?: ['PHP', 'JavaScript', 'MySQL'];
                    $skills = array_slice($skills, 0, 3);
                ?>
                <div class="specialist-card cyber-card">
                    <div class="card-glitch"></div>
                    <div class="specialist-avatar">
                        <div class="avatar-glow"></div>
                        <img src="assets/images/avatars/default.jpg" alt="<?= htmlspecialchars($spec['full_name']) ?>">
                    </div>
                    <div class="specialist-info">
                        <h3 class="cyber-name"><?= htmlspecialchars($spec['full_name']) ?></h3>
                        <div class="specialist-tags">
                            <span class="tag tag-<?= strtolower($spec['level']) ?>"><?= strtoupper($spec['level']) ?></span>
                            <?php foreach ($skills as $skill): ?>
                                <span class="tag"><?= htmlspecialchars($skill) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <p class="specialist-bio"><?= htmlspecialchars(mb_substr($spec['experience'], 0, 100)) ?>...</p>
                        <div class="skill-meter">
                            <div class="meter-label">Рейтинг</div>
                            <div class="meter-bar">
                                <div class="meter-fill" style="width: <?= ($spec['rating'] / 5) * 100 ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Секция "Кейсы" -->
    <section id="cases" class="cyber-section dark-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-gold">// УСПЕШНЫЕ КЕЙСЫ</span>
                    <div class="title-line"></div>
                </h2>
                <p class="section-subtitle">Реализованные проекты и достижения</p>
            </div>

            <div class="cases-grid">
                <?php foreach ($cases as $case): ?>
                <div class="case-card cyber-card">
                    <div class="case-header">
                        <span class="case-tag"><?= htmlspecialchars($case['company_name']) ?></span>
                        <span class="case-status">Завершен</span>
                    </div>
                    <h3><?= htmlspecialchars($case['title']) ?></h3>
                    <p><?= htmlspecialchars(mb_substr($case['description'], 0, 100)) ?>...</p>
                    <div class="case-stats">
                        <div class="case-stat">
                            <i class="fas fa-clock"></i>
                            <span><?= htmlspecialchars($case['deadline']) ?></span>
                        </div>
                        <div class="case-stat">
                            <i class="fas fa-code"></i>
                            <span><?= number_format($case['budget'], 0, ',', ' ') ?> ₽</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Секция "Тарифы" -->
    <section id="pricing" class="cyber-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-gold">// ТАРИФЫ</span>
                    <div class="title-line"></div>
                </h2>
                <p class="section-subtitle">Выберите оптимальный план для вашего бизнеса</p>
            </div>

            <div class="pricing-grid">
                <div class="pricing-card cyber-card">
                    <div class="pricing-header">
                        <h3 class="pricing-name">СТАРТ</h3>
                        <div class="pricing-price">
                            <span class="price-amount">35 000</span>
                            <span class="price-currency">₽</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> Адаптивный дизайн</li>
                        <li><i class="fas fa-check feature-check"></i> До 5 страниц</li>
                        <li><i class="fas fa-check feature-check"></i> Базовая SEO</li>
                        <li><i class="fas fa-check feature-check"></i> Форма обратной связи</li>
                    </ul>
                </div>

                <div class="pricing-card cyber-card pricing-highlighted">
                    <div class="pricing-badge">ПОПУЛЯРНЫЙ</div>
                    <div class="pricing-header">
                        <h3 class="pricing-name">БИЗНЕС</h3>
                        <div class="pricing-price">
                            <span class="price-amount">55 000</span>
                            <span class="price-currency">₽</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> Все из тарифа "Старт"</li>
                        <li><i class="fas fa-check feature-check"></i> До 15 страниц</li>
                        <li><i class="fas fa-check feature-check"></i> CRM интеграция</li>
                        <li><i class="fas fa-check feature-check"></i> Блог / CMS</li>
                    </ul>
                </div>

                <div class="pricing-card cyber-card">
                    <div class="pricing-header">
                        <h3 class="pricing-name">ПРО</h3>
                        <div class="pricing-price">
                            <span class="price-amount">75 000</span>
                            <span class="price-currency">₽</span>
                        </div>
                    </div>
                    <ul class="pricing-features">
                        <li><i class="fas fa-check feature-check"></i> Все из тарифа "Бизнес"</li>
                        <li><i class="fas fa-check feature-check"></i> Неограниченно страниц</li>
                        <li><i class="fas fa-check feature-check"></i> Платежные системы</li>
                        <li><i class="fas fa-check feature-check"></i> Личный кабинет</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Секция контактов -->
    <section id="contact" class="cyber-section dark-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="title-gold">// КОНТАКТЫ</span>
                    <div class="title-line"></div>
                </h2>
                <p class="section-subtitle">Свяжитесь с нами любым удобным способом</p>
            </div>

            <div class="contact-info">
                <p><i class="fas fa-envelope"></i> info@lark.ru</p>
                <p><i class="fas fa-phone"></i> +7 (999) 123-45-67</p>
                <p><i class="fas fa-telegram"></i> @lark_support</p>
            </div>
        </div>
    </section>

    <!-- Футер -->
    <footer class="cyber-footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-column">
                    <h3>LARK FREELANCE</h3>
                    <p>Футуристичная платформа для соединения талантов с возможностями</p>
                </div>
                <div class="footer-column">
                    <h3>НАВИГАЦИЯ</h3>
                    <ul class="footer-links">
                        <li><a href="#about">О нас</a></li>
                        <li><a href="#how-it-works">Как это работает</a></li>
                        <li><a href="#specialists">Специалисты</a></li>
                        <li><a href="#cases">Кейсы</a></li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>КАБИНЕТЫ</h3>
                    <ul class="footer-links">
                        <li><a href="client/login.php">Для заказчиков</a></li>
                        <li><a href="developer/login.php">Для разработчиков</a></li>
                        <li><a href="admin/login.php">Для менеджеров</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="copyright">© <?= date('Y') ?> LARK FREELANCE</div>
            </div>
        </div>
    </footer>

    <script src="assets/js/main.js"></script>
</body>
</html>