<?php

require_once './../registration_and_authorization/connect_db.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['company_data']['id'])) {
    http_response_code(403);
    echo json_encode(["message" => "Необходима авторизация."]);
    exit();
}

$company_id = $_SESSION['company_data']['id'];
$delivery = isset($_POST['delivery']) ? 1 : 0;
$pickup = isset($_POST['pickup']) ? 1 : 0;
$delivery_place = htmlspecialchars(trim($_POST['delivery-place']));
$delivery_type = htmlspecialchars(trim($_POST['delivery-type']));
$min_order_amount = !empty($_POST['min-order-amount']) ? htmlspecialchars(trim($_POST['min-order-amount'])) : null;
$delivery_country = htmlspecialchars(trim($_POST['delivery-country']));
$price_delivery = !empty($_POST['price-delivery']) ? htmlspecialchars(trim($_POST['price-delivery'])) : null;
$calc_delivery = !empty($_POST['calc-delivery']) ? htmlspecialchars(trim($_POST['calc-delivery'])) : null;
$custom_delivery_desc = !empty($_POST['custom-delivery-desc']) ? htmlspecialchars(trim($_POST['custom-delivery-desc'])) : null;

if (!empty($delivery_place) && !empty($delivery_type) && !empty($delivery_country)) {
    try {
        $stmt = $conn->prepare("INSERT INTO deliveries (company_id, delivery_type, delivery_place, min_order_amount, delivery_country, price_delivery, calc_delivery, custom_delivery_desc) 
                                VALUES (:company_id, :delivery_type, :delivery_place, :min_order_amount, :delivery_country, :price_delivery, :calc_delivery, :custom_delivery_desc)");

        $stmt->bindParam(':company_id', $company_id);
        $stmt->bindParam(':delivery_type', $delivery_type);
        $stmt->bindParam(':delivery_place', $delivery_place);
        $stmt->bindParam(':min_order_amount', $min_order_amount);
        $stmt->bindParam(':delivery_country', $delivery_country);
        $stmt->bindParam(':price_delivery', $price_delivery);
        $stmt->bindParam(':calc_delivery', $calc_delivery);
        $stmt->bindParam(':custom_delivery_desc', $custom_delivery_desc);

        $stmt->execute();

        // Сохраняем данные о доставке в сессии
        $_SESSION['delivery_data'] = [
            'company_id' => $company_id,
            'delivery_type' => $delivery_type,
            'delivery_place' => $delivery_place,
            'min_order_amount' => $min_order_amount,
            'delivery_country' => $delivery_country,
            'price_delivery' => $price_delivery,
            'calc_delivery' => $calc_delivery,
            'custom_delivery_desc' => $custom_delivery_desc,
        ];

        echo json_encode(["message" => "Данные о доставке успешно добавлены!"]);
    } catch (Exception $exception) {
        http_response_code(500);
        echo json_encode(["message" => "Ошибка: " . $exception->getMessage()]);
    }
} else {
    http_response_code(400);
    echo json_encode(["message" => "Пожалуйста, заполните все обязательные поля."]);
}