<?php

require_once './../registration_and_authorization/connect_db.php';
require_once './company_array.php';

header('Content-Type: application/json; charset=utf-8');

$company_name = htmlspecialchars(trim($_POST['company_name']));
$company_type = htmlspecialchars(trim($_POST['company-type']));
$company_platform = htmlspecialchars(trim($_POST['company-platform']));
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    http_response_code(403);
    echo json_encode(["message" => "Ошибка: пользователь не авторизован."]);
    exit;
}

if (!empty($company_name) && !empty($company_type) && !empty($company_platform)) {
    try {
        // Добавление данных компании в базу данных
        $stmt = $conn->prepare("
            INSERT INTO companies (user_id, company_name, company_type, company_platform) 
            VALUES (:user_id, :company_name, :company_type, :company_platform)
        ");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':company_name', $company_name);
        $stmt->bindParam(':company_type', $company_type);
        $stmt->bindParam(':company_platform', $company_platform);
        $stmt->execute();

        // Получаем ID только что добавленной компании
        $company_id = $conn->lastInsertId();

        // Сохраняем данные о компании в сессию
        $_SESSION['company_data'] = [
            'id' => $company_id,
            'name' => $company_name,
            'type' => $company_type,
            'platform' => $company_platform
        ];

        echo json_encode([
            "message" => "Данные о компании добавлены успешно!",
            "data" => [
                "company_name" => $company_name,
                "company_type" => $company_type,
                "company_platform" => $company_platform
            ]
        ]);
    } catch (PDOException $exception) {
        http_response_code(500);
        echo json_encode(["message" => "Ошибка: " . $exception->getMessage()]);
        error_log("Ошибка при добавлении данных о компании: " . $exception->getMessage());
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Пожалуйста, заполните все поля"]);
}
