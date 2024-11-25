<?php

require_once './../registration_and_authorization/connect_db.php';

header('Content-Type: application/json; charset=utf-8');

// Проверка наличия данных компании в сессии
if (!isset($_SESSION['company_data']['id'])) {
    echo json_encode(["status" => "error", "message" => "Компания не найдена."]);
    exit;
}

$company_id = $_SESSION['company_data']['id'];

// Проверка и обработка загруженного файла и данных формы
$category_logo = $_FILES['category-logo'];
$category_title = htmlspecialchars(trim($_POST['category-title']));

if (!empty($category_title)) {
    try {
        // Обработка загрузки изображения категории
        if ($category_logo['size'] > 0 && $category_logo['size'] <= 10 * 1024 * 1024) {
            $allowed_extensions = ['jpg', 'png', 'svg'];
            $file_extension = pathinfo($category_logo['name'], PATHINFO_EXTENSION);

            if (in_array($file_extension, $allowed_extensions)) {
                $upload_dir = 'uploads/categories/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $upload_file = $upload_dir . basename($category_logo['name']);
                if (move_uploaded_file($category_logo['tmp_name'], $upload_file)) {
                    // Подготовка и выполнение SQL-запроса для вставки данных
                    $stmt = $conn->prepare("INSERT INTO categories (company_id, category_logo, category_title) VALUES (:company_id, :category_logo, :category_title)");

                    $stmt->bindParam(':company_id', $company_id);
                    $stmt->bindParam(':category_logo', $upload_file);
                    $stmt->bindParam(':category_title', $category_title);

                    $stmt->execute();

                    // Получаем ID последней вставленной категории
                    $category_id = $conn->lastInsertId();

                    // Сохраняем данные о категории в сессии
                    $_SESSION['category_data'] = [
                        'id' => $category_id,
                        'category_title' => $category_title,
                        'category_logo' => $category_logo,
                        'company_id' => $_SESSION['company_data']['id']
                    ];

                    echo json_encode(["status" => "success", "message" => "Категория успешно добавлена!"]);
                } else {
                    throw new Exception("Ошибка при загрузке файла.");
                }
            } else {
                throw new Exception("Недопустимый формат файла.");
            }
        } else {
            throw new Exception("Файл не загружен или превышает допустимый размер.");
        }
    } catch (Exception $exception) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Ошибка: " . $exception->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Пожалуйста, введите название категории."]);
}