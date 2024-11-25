<?php

require_once 'connect_db.php';

try {
    // Таблица пользователей
    $table1 = "CREATE TABLE IF NOT EXISTS users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(15) UNIQUE,
        username VARCHAR(50),
        pass VARCHAR(255) NOT NULL,
        verification_code VARCHAR(10),
        is_verified BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";

    // Таблица компаний
    $table2 = "CREATE TABLE IF NOT EXISTS companies (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        company_name VARCHAR(100) NOT NULL,
        company_type VARCHAR(50),
        company_platform VARCHAR(50),
        bot_token VARCHAR(255) DEFAULT NULL,
        company_logo VARCHAR(255) DEFAULT NULL,
        company_lang JSON DEFAULT NULL,
        company_desc TEXT DEFAULT NULL,
        company_phone VARCHAR(20) DEFAULT NULL,
        social_networks JSON DEFAULT NULL,
        currency VARCHAR(10) DEFAULT NULL,
        working_hours JSON DEFAULT NULL,
        company_address VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    // Таблица категорий
    $table3 = "CREATE TABLE IF NOT EXISTS categories (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        company_id INT(11) NOT NULL,
        category_logo VARCHAR(255) NOT NULL,
        category_title TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )";

    // Таблица продуктов
    $table4 = "CREATE TABLE IF NOT EXISTS products (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        product_logo VARCHAR(255),
        product_title VARCHAR(100) NOT NULL,
        product_desc TEXT,
        product_price DECIMAL(10, 2) NOT NULL,
        product_unit VARCHAR(50) NOT NULL,
        category_id INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
    )";

    // Обновленная структура таблицы deliveries
    $table5 = "CREATE TABLE IF NOT EXISTS deliveries (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        company_id INT(11) NOT NULL,
        delivery_type VARCHAR(255) NOT NULL,
        delivery_place VARCHAR(255) NOT NULL,
        min_order_amount DECIMAL(10, 2) DEFAULT NULL,
        delivery_country VARCHAR(255) NOT NULL,
        price_delivery DECIMAL(10, 2) DEFAULT NULL,
        calc_delivery DECIMAL(10, 2) DEFAULT NULL,
        custom_delivery_desc TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )";

    // Выполнение создания таблиц
    $conn->exec($table1);
    $conn->exec($table2);
    $conn->exec($table3);
    $conn->exec($table4);
    $conn->exec($table5);

    // echo "Таблицы успешно созданы и связаны! \n";
} catch (PDOException $exception) {
    error_log("Ошибка создания таблиц: " . $exception->getMessage());
    die("Ошибка создания таблиц.");
}