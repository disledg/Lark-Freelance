-- SQL скрипт для создания таблицы cases
-- Выполните этот скрипт в phpMyAdmin или через командную строку MySQL

USE lark_freelance;

-- Создаем таблицу cases
CREATE TABLE IF NOT EXISTS cases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    deadline VARCHAR(50),
    budget DECIMAL(10,2),
    technologies TEXT,
    challenges TEXT,
    results TEXT,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE
);

-- Проверяем, что таблица создана
SHOW TABLES LIKE 'cases';

-- Проверяем структуру таблицы
DESCRIBE cases;