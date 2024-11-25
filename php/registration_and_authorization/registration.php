<?php 

require_once './create_table.php';

function generateVerificationCode($length = 6) {
    return random_int(100000, 999999);
}

$email = isset($_POST['email']) ? filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL) : null;
$phone = isset($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : null;
$pass = isset($_POST['password']) ? trim($_POST['password']) : null;
$pass_double = isset($_POST['password_double']) ? trim($_POST['password_double']) : null;

if (!empty($pass) && $pass === $pass_double) {
    $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
    $verification_code = generateVerificationCode();

    try {
        if (!empty($email)) {
            // Проверка на существование email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Email уже зарегистрирован."]);
                exit;
            }

            // Регистрация по email
            $stmt = $conn->prepare("INSERT INTO users (email, pass, verification_code) VALUES (:email, :pass, :verification_code)");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':pass', $pass_hash);
            $stmt->bindParam(':verification_code', $verification_code);
            $stmt->execute();

            // Отправка email с кодом верификации (mail())
            mail($email, "Verification Code", "Ваш код подтверждения: $verification_code");

        } elseif (!empty($phone)) {
            // Проверка на существование телефона
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = :phone");
            $stmt->bindParam(':phone', $phone);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                http_response_code(400);
                echo json_encode(["message" => "Телефон уже зарегистрирован."]);
                exit;
            }

            // Регистрация по телефону
            $stmt = $conn->prepare("INSERT INTO users (phone, pass, verification_code) VALUES (:phone, :pass, :verification_code)");
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':pass', $pass_hash);
            $stmt->bindParam(':verification_code', $verification_code);
            $stmt->execute();

            // Отправка SMS через Eskiz.uz (sendSms())
            $tokenResponse = getEskizToken();
            if (is_null($tokenResponse)) {
                http_response_code(500);
                echo json_encode(["message" => "Не удалось получить токен для отправки SMS. Проверьте учетные данные и попробуйте снова."]);
                exit;
            }
            if (isset($tokenResponse['token'])) {
                $smsSent = sendSms($phone, $verification_code, $tokenResponse['token']);
                if (!$smsSent) {
                    // Если отправка SMS не удалась, удалить пользователя из базы данных
                    $stmt = $conn->prepare("DELETE FROM users WHERE phone = :phone");
                    $stmt->bindParam(':phone', $phone);
                    $stmt->execute();

                    http_response_code(500);
                    echo json_encode(["message" => "Не удалось отправить SMS. Попробуйте позже."]);
                    exit;
                }
            } else {
                http_response_code(500);
                echo json_encode(["message" => "Не удалось получить токен для отправки SMS."]);
                exit;
            }

        } else {
            http_response_code(400);
            echo json_encode(["message" => "Пожалуйста, укажите либо email, либо телефон для регистрации."]);
        }

        if (!empty($email)) {
            $_SESSION['email'] = $email;
        } elseif (!empty($phone)) {
            $_SESSION['phone'] = $phone;
        }
    } catch (PDOException $exception) {
        error_log("Ошибка регистрации: " . $exception->getMessage());
        http_response_code(500);
        echo json_encode(["message" => "Ошибка регистрации."]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Пожалуйста, заполните все поля и убедитесь, что пароли совпадают."]);
}

function getEskizToken() {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://notify.eskiz.uz/api/auth/login');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'email' => 'your_email',
            'password' => 'your_password'
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            error_log("Ошибка CURL: " . curl_error($ch));
            return null;
        }

        $responseData = json_decode($response, true);
        curl_close($ch);

        if (isset($responseData['token'])) {
            return $responseData;
        } else {
            error_log("Ошибка получения токена: " . $response);
            return null;
        }
    } catch (Exception $e) {
        error_log("Исключение при получении токена: " . $e->getMessage());
        return null;
    }
}

function sendSms($phone, $verification_code, $token) {
    $ch = curl_init();

    $postData = [
        "mobile_phone" => $phone,
        "message" => "Код подтверждения для регистрации на сайте Onlinesell.uz: $verification_code",
        "from" => "4546",
    ];

    curl_setopt($ch, CURLOPT_URL, "https://notify.eskiz.uz/api/message/sms/send");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $token
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $responseData = json_decode($response, true);
    if ($httpCode == 200 && isset($responseData['status']) && $responseData['status'] === 'success') {
        return true; // Успешная отправка SMS
    } else {
        error_log("Ошибка отправки SMS: " . ($responseData['message'] ?? 'Неизвестная ошибка'));
        return false; // Ошибка при отправке SMS
    }
}
?>