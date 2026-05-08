#!/bin/bash

# Скрипт для создания таблицы cases в базе данных Lark Freelance

echo "🔧 Создание таблицы cases..."

# Проверяем, установлен ли mysql клиент
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL клиент не найден. Установите mysql-client или используйте Docker."
    echo "💡 Рекомендация: запустите 'docker-compose up -d' и используйте update_cases_table.php"
    exit 1
fi

# Параметры подключения
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_USER="root"
DB_PASS="rootpassword"
DB_NAME="lark_freelance"

echo "📡 Подключение к БД $DB_HOST:$DB_PORT..."

# Проверяем подключение
mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS -e "SELECT 1;" $DB_NAME 2>/dev/null
if [ $? -ne 0 ]; then
    echo "❌ Не удалось подключиться к базе данных"
    echo "💡 Убедитесь, что MySQL сервер запущен и параметры подключения верны"
    echo "💡 Или запустите Docker: docker-compose up -d"
    exit 1
fi

echo "✅ Подключение успешно"

# Проверяем, существует ли таблица cases
TABLE_EXISTS=$(mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS -e "SHOW TABLES LIKE 'cases';" $DB_NAME 2>/dev/null | wc -l)

if [ $TABLE_EXISTS -gt 0 ]; then
    echo "ℹ️ Таблица cases уже существует"
else
    echo "📝 Создаю таблицу cases..."

    SQL="CREATE TABLE cases (
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
    );"

    mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS -e "$SQL" $DB_NAME 2>/dev/null

    if [ $? -eq 0 ]; then
        echo "✅ Таблица cases создана успешно"
    else
        echo "❌ Ошибка создания таблицы cases"
        exit 1
    fi
fi

# Проверяем структуру таблицы
echo "🔍 Проверяю структуру таблицы..."
mysql -h $DB_HOST -P $DB_PORT -u $DB_USER -p$DB_PASS -e "DESCRIBE cases;" $DB_NAME

echo ""
echo "🎉 Готово! Теперь можно использовать систему кейсов."
echo "📖 Подробная инструкция: FIX_CASES_ERROR.md"