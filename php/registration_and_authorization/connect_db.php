<?php

session_start();

$conn_name = getenv('DB_USER') ?: 'gen_user';
$conn_pass = getenv('DB_PASS') ?: 'E0ee377e4e3878e';
$host = getenv('DB_HOST') ?: '176.124.218.104';
$db_name = getenv('DB_NAME') ?: 'default_db';
$db_port = '3306';

try {
    $conn = new PDO("mysql:host=$host;port=$db_port;dbname=$db_name", $conn_name, $conn_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Подключение к БД успешно! \n";
    
} catch (PDOException $exception) {
    error_log("Ошибка подключения к БД: " . $exception->getMessage());
    die("Ошибка подключения к базе данных.");
}

// $conn_name = getenv('DB_USER') ?: 'ce91942_onlinedb';
// $conn_pass = getenv('DB_PASS') ?: 'tetriandox5';
// $host = getenv('DB_HOST') ?: 'localhost';
// $db_name = getenv('DB_NAME') ?: 'ce91942_onlinedb';

// try {
//     $conn = new PDO("mysql:host=$host;dbname=$db_name", $conn_name, $conn_pass);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
//     // echo "Подключение к БД успешно! \n";
    
// } catch (PDOException $exception) {
//     error_log("Ошибка подключения к БД: " . $exception->getMessage());
//     die("Ошибка подключения к базе данных.");
// }