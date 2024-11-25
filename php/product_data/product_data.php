<?php

require_once './../registration_and_authorization/connect_db.php';

header('Content-Type: application/json; charset=utf-8');

var_dump($_SESSION);

// Проверка наличия данных категории в сессии
if (!isset($_SESSION['category_data']['id'])) {
    echo json_encode(["status" => "error", "message" => "Категория не найдена."]);
    exit;
}

$category_id = $_SESSION['category_data']['id'];

$product_logo = isset($_FILES['product-logo']) ? $_FILES['product-logo'] : null;
$product_title = isset($_POST['product-title']) ? htmlspecialchars(trim($_POST['product-title'])) : '';
$product_desc = isset($_POST['product-desc']) ? htmlspecialchars(trim($_POST['product-desc'])) : '';
$product_price = isset($_POST['price']) ? htmlspecialchars(trim($_POST['price'])) : '';
$product_unit = isset($_POST['unit']) ? htmlspecialchars(trim($_POST['unit'])) : '';

error_log('Product data: ' . print_r($_POST, true));
error_log('Files data: ' . print_r($_FILES, true));

if (!empty($product_title)) {
    try {
        // Обработка загрузки изображения продукта
        if ($product_logo['size'] > 0 && $product_logo['size'] <= 10 * 1024 * 1024) {
            $allowed_extensions = ['jpg', 'png', 'svg'];
            $file_extension = pathinfo($product_logo['name'], PATHINFO_EXTENSION);

            if (in_array($file_extension, $allowed_extensions)) {
                $upload_dir = 'uploads/products/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $upload_file = $upload_dir . basename($product_logo['name']);
                if (move_uploaded_file($product_logo['tmp_name'], $upload_file)) {
                    // Подготовка и выполнение SQL-запроса для вставки данных
                    $stmt = $conn->prepare("INSERT INTO products (product_logo, product_title, product_desc, product_price, product_unit, category_id) VALUES (:product_logo, :product_title, :product_desc, :product_price, :product_unit, :category_id)");

                    $stmt->bindParam(':product_logo', $upload_file);
                    $stmt->bindParam(':product_title', $product_title);
                    $stmt->bindParam(':product_desc', $product_desc);
                    $stmt->bindParam(':product_price', $product_price);
                    $stmt->bindParam(':product_unit', $product_unit);
                    $stmt->bindParam(':category_id', $category_id); // Привязываем только к категории

                    $stmt->execute();

                    // Получаем ID последнего вставленного продукта
                    $product_id = $conn->lastInsertId();

                    // Сохраняем данные о продукте в сессии
                    $_SESSION['product_data'] = [
                        'id' => $product_id,
                        'product_title' => $product_title,
                        'product_desc' => $product_desc,
                        'product_price' => $product_price,
                        'product_unit' => $product_unit,
                        'product_logo' => $upload_file,
                        'category_id' => $category_id
                    ];

                    echo json_encode(["status" => "success", "message" => "Продукт успешно добавлен!"]);
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
    echo json_encode(["status" => "error", "message" => "Пожалуйста, заполните все обязательные поля."]);
}
?>