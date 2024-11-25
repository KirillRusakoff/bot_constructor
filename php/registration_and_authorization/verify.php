<?php
require_once 'connect_db.php';

header('Content-Type: application/json');

// Получение кода верификации из POST-запроса
$verify_code = trim($_POST['verify'] ?? '');

// Получение email или телефона из сессии
$email = $_SESSION['email'] ?? null;
$phone = $_SESSION['phone'] ?? null;

if (empty($verify_code)) {
    http_response_code(400);
    echo json_encode(["message" => "Код верификации не может быть пустым."]);
    exit;
}

if (empty($email) && empty($phone)) {
    http_response_code(400);
    echo json_encode(["message" => "Ошибка: пользователь не авторизован."]);
    exit;
}

try {
    // Извлекаем уникальный идентификатор пользователя (id)
    $stmt = $conn->prepare("SELECT id, verification_code FROM users WHERE (email = :email OR phone = :phone)");
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':phone', $phone);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || empty($result['verification_code'])) {
        http_response_code(400);
        echo json_encode(["message" => "Пользователь не найден или код верификации отсутствует."]);
        exit;
    }

    // Проверка совпадения кода верификации
    if ($result['verification_code'] === $verify_code) {
        // Сохраняем уникальный идентификатор пользователя (id) в сессии
        $_SESSION['user_id'] = $result['id'];

        echo json_encode(["message" => "Верификация прошла успешно!"]);
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Код верификации неверен."]);
    }
} catch (PDOException $exception) {
    http_response_code(500);
    echo json_encode(["message" => "Ошибка верификации: " . $exception->getMessage()]);
    error_log("Ошибка при верификации пользователя: " . $exception->getMessage());
}