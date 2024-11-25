<?php

require_once './connect_db.php';

$email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
$phone = htmlspecialchars(trim($_POST['phone']));
$pass = trim($_POST['password']);

try {
    if (!empty($phone) && !empty($pass)) {
        // Авторизация по телефону
        $stmt = $conn->prepare('SELECT phone, pass FROM users WHERE phone = :phone');
        $stmt->bindParam(':phone', $phone);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($pass, $user['pass'])) {
                echo json_encode(["message" => "Авторизация прошла успешно."]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Неправильный телефон или пароль."]);
            }
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Пользователь с таким телефоном не найден."]);
        }
    } elseif (!empty($email) && !empty($pass)) {
        // Авторизация по email
        $stmt = $conn->prepare('SELECT email, pass FROM users WHERE email = :email');
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($pass, $user['pass'])) {
                echo json_encode(["message" => "Авторизация прошла успешно."]);
            } else {
                http_response_code(401);
                echo json_encode(["message" => "Неправильный email или пароль."]);
            }
        } else {
            http_response_code(401);
            echo json_encode(["message" => "Пользователь с таким email не найден."]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Пожалуйста, заполните все поля."]);
    }
} catch (PDOException $exception) {
    error_log("Ошибка авторизации: " . $exception->getMessage());
    http_response_code(500);
    echo json_encode(["message" => "Ошибка авторизации."]);
}