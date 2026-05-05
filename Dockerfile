FROM php:8.2-apache

# Установка необходимых расширений PHP
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    docker-php-ext-enable mysqli

# Включение mod_rewrite для Apache
RUN a2enmod rewrite

# Установка дополнительных утилит
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка PHPUnit глобально через Composer
RUN composer global require phpunit/phpunit

# Добавление глобального bin в PATH
ENV PATH="/root/.composer/vendor/bin:${PATH}"

# Копирование проекта
COPY . /var/www/html/

# Установка прав на папку uploads
RUN chown -R www-data:www-data /var/www/html/public/uploads \
    && chmod -R 755 /var/www/html/public/uploads

# Настройка виртуального хоста (если нужно)
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html