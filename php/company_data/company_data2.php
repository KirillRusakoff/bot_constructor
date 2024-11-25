<?php

require_once './../registration_and_authorization/connect_db.php';
require_once './company_array.php';

header('Content-Type: application/json; charset=utf-8');

$bot_token = isset($_POST['token']) ? htmlspecialchars(trim($_POST['token'])) : null;

if (!empty($bot_token)) {
    try {
        // Проверяем токен с помощью Telegram API
        $url = "https://api.telegram.org/bot" . $bot_token . "/getMe";
        $response = file_get_contents($url);
        $result = json_decode($response, true);

        if ($result['ok']) {
            // Проверяем, что данные компании уже сохранены в сессии
            if (isset($_SESSION['company_data'])) {
                $company_array = $_SESSION['company_data'];
            } else {
                http_response_code(400);
                echo json_encode(["message" => "Данные о компании отсутствуют в сессии"]);
                exit;
            }

            // Добавляем $bot_token в массив компании
            $company_array['bot_token'] = $bot_token;

            // Сохраняем обновленный массив обратно в сессию
            $_SESSION['company_data'] = $company_array;

            // Обновляем данные в базе данных
            $company_id = $company_array['id']; // Получаем ID компании из сессии
            $stmt = $conn->prepare("UPDATE companies SET bot_token = :bot_token WHERE id = :company_id");
            $stmt->bindParam(':bot_token', $bot_token);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->execute();

            echo json_encode([
                "message" => "Бот успешно зарегистрирован и данные о компании обновлены!",
                "data" => $company_array
            ]);
        } else {
            http_response_code(400);
            echo json_encode(["message" => "Некорректный токен."]);
        }
    } catch (Exception $exception) {
        http_response_code(500);
        echo json_encode(["message" => "Ошибка: " . $exception->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Пожалуйста, заполните все поля"]);
}