<?php

//Подключение к БД
$conn_name = getenv('DB_USER') ?: 'gen_user';
$conn_pass = getenv('DB_PASS') ?: 'E0ee377e4e3878e';
$host = getenv('DB_HOST') ?: '176.124.218.104';
$db_name = getenv('DB_NAME') ?: 'default_db';
$db_port = '3306';

try {
    $conn = new PDO("mysql:host=$host;port=$db_port;dbname=$db_name", $conn_name, $conn_pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Получаем токен бота из БД
    $stmt = $conn->prepare("SELECT bot_token FROM companies ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result && isset($result['bot_token'])) {
        $botToken = $result['bot_token']; // Полученный токен бота
    } else {
        die("Error: Bot token not found in database.");
    }

    //Получение информации о компании
    $stmt = $conn->prepare("SELECT company_name, company_type, company_logo, company_desc, company_phone, social_networks, company_address FROM companies");
    $stmt->execute();
    $company = $stmt->fetch(PDO::FETCH_ASSOC);

    //Получение информации о категории
    $stmt = $conn->prepare("SELECT id, category_title FROM categories");
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    //Получение информации о продукте
    $stmt = $conn->prepare("SELECT product_logo, product_title, product_desc, product_price, product_unit, id FROM products");
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Данные для подключения к Payme
    $payme_url = "https://checkout.paycom.uz/api"; // URL API Payme
    $payme_token = "4a995523bb6b69f95169c2b3e498a30cd94f8d41a6c8a9999f8cb422d04bb2e0f20772cf69bbe8a9d8c9adab015588968b54f4a14929d73469b8aa916ba41411"; // Секретный ключ
    $cashier_id = "65ae56262a93e9a5efb8d750"; // ID кассы

    // Функции
    function logError($message) 
    {
        $logFile = __DIR__ . '/webhook_log.txt';  // Путь к файлу в той же директории
        $date = date('Y-m-d H:i:s');  // Дата и время ошибки
        $logMessage = "[$date] ERROR: $message\n";
        
        // Записываем сообщение в файл
        error_log($logMessage, 3, $logFile);  // 3 — для записи в файл
    }

    function sendRequest($botToken, $method, $params)
    {
        $ch = curl_init("https://api.telegram.org/bot" . $botToken . "/" . $method);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        curl_setopt($ch, CURLOPT_HEADER, false);

        $resultQuery = curl_exec($ch);
        curl_close($ch);
        return $resultQuery;
    }

    function sendMessages($botToken, $chatId, $textMessage)
    {
        $getQuery = array(
            "chat_id" 	=> $chatId,
            "text"  	=> $textMessage,
            "parse_mode" => "HTML",
        );
        $ch = curl_init("https://api.telegram.org/bot". $botToken ."/sendMessage?" . http_build_query($getQuery));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $resultQuery = curl_exec($ch);
        curl_close($ch);
    }

    function sendImages($botToken, $chatId, $image, $caption = '')
    {
        $arrayQuery = [
            'chat_id' => $chatId,
            'caption' => $caption,
            'photo' => $image
        ];		
        $ch = curl_init('https://api.telegram.org/bot'. $botToken .'/sendPhoto');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $arrayQuery);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        $res = curl_exec($ch);
        curl_close($ch);
    }

    function setBotDescription($botToken, $description, $shortDescription) 
    {
        // Установка основного описания
        $params = ['description' => $description];
        $response = sendRequest($botToken, "setMyDescription", $params);
        error_log("Response setMyDescription: $response");
    
        // Установка короткого описания
        $params = ['short_description' => $shortDescription];
        $response = sendRequest($botToken, "setMyShortDescription", $params);
        error_log("Response setMyShortDescription: $response");
    }
    
    function sendButtons($botToken, $chatId, $text, $keyboard) 
    {
        $url = "https://api.telegram.org/bot$botToken/sendMessage";
        
        $postData = [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => json_encode($keyboard)
        ];
    
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_exec($ch);
        curl_close($ch);
    }

    function ensureCartTableExists($conn) 
    {
        $query = "
            CREATE TABLE IF NOT EXISTS cart (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id BIGINT NOT NULL,
                product_id INT NOT NULL,
                quantity INT DEFAULT 1,
                added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        $conn->exec($query);
    }

    // Функция для создания платежной сессии
    function createPaymentSession($amount, $userId) 
    {
        global $payme_url, $payme_token, $cashier_id;

        $data = [
            'amount' => $totalPrice,  // Сумма для оплаты
            'cashier_id' => $cashier_id,
            'user_id' => $userId,
            'description' => 'Оплата товаров в корзине',
            'payme_token' => $payme_token
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payme_url . "/create_session");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $response = curl_exec($ch);
        curl_close($ch);

        $response = json_decode($response, true);
        // Логируем ответ от API
        if ($response && isset($response['payment_url'])) {
            return $response['payment_url'];
        } else {
            // Логируем ошибку
            logError("Ошибка при создании сессии: " . json_encode($response));
            return false;
        }
    }

    // Получаем входящие данные от Telegram
    $data = file_get_contents('php://input');
    $arrDataAnswer = json_decode($data, true);   
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Ошибка JSON: " . json_last_error_msg());
        die("Некорректный JSON");
    }

    //Текстовое описание для продукта
    $productText = $product['product_title'] . "\n";
    $productText .= "\n";
    $productText .= $product['product_desc'] . "\n";
    $productText .= "\n";
    $productText .= "Стоимость: " . $product['product_price'] . " за 1 " . $product['product_unit'] ."\n";

    //Текстовое описание для компании
    $companyText = "Название компании: " .$company['company_name'] . "\n";
    $companyText .= "\n";
    $companyText .= "Тип компании: " . $company['company_type'] . "\n";
    $companyText .= "\n";
    $companyText .= "Описание: " . $company['company_desc'] . "\n";
    $companyText .= "\n";
    $companyText .= "Адрес компании: " . $company['company_address'] . "\n";
    $companyText .= "\n";
    $companyText .= "Контактный телефон: " . $company['company_phone'] . "\n";

    // Получаем изображение для кнопки 'menu' из таблицы categories (product_logo)
    $menuImage = isset($product['product_logo']) ? "http://rusakov-test.ru" . $product['product_logo'] : '';

    // Получаем изображение для кнопки 'about' из таблицы companies (company_logo)
    $aboutImage = isset($company['company_logo']) ? "http://rusakov-test.ru" . $company['company_logo'] : '';

    if (isset($arrDataAnswer['message'])) {
        // Обработка сообщений (например, команды /start)
        $chatId = $arrDataAnswer["message"]["chat"]["id"];
        $messageText = $arrDataAnswer["message"]["text"];
    
        switch ($messageText) {
            case '/start':
                sendMessages($botToken, $chatId, "Добро пожаловать в вашу компанию!");
                if ($company) {
                    $companyName = $company['company_name'];
                    $companyType = strtolower($company['company_type']);
                
                    // Устанавливаем описание и короткое описание
                    $description = "Добро пожаловать в $companyType '$companyName'!" . "\n";
                    $description .= "\n";
                    $description .= $company['company_desc'];
                    $shortDescription = $companyType;
                
                    setBotDescription($botToken, $description, $shortDescription);
                }

                $getQuery = array(
                    "chat_id" 	=> $chatId,
                    "text"  	=> "Выберите нужный пункт в меню",
                    "parse_mode" => "html",
                    'reply_markup' => json_encode([
                        'inline_keyboard' => [
                            [
                                ['text' => '📦 Меню', 'callback_data' => 'menu'],
                                ['text' => '🛒 Корзина', 'callback_data' => 'cart'],
                                ['text' => '🛍️ О компании', 'callback_data' => 'about'],
                                ['text' => '⭐ Помощь', 'callback_data' => 'help']
                            ]
                        ],
                        'keyboard' => [
                            [
                                ['text' => '📦 Меню'],
                                ['text' => '🛒 Корзина']
                            ],
                            [
                                ['text' => '🛍️ О компании'],
                                ['text' => '⭐ Помощь']
                            ]
                        ],
                        'resize_keyboard' => true,
                        'one_time_keyboard' => true
                    ]),
                );
                sendRequest($botToken, "sendMessage", $getQuery);
                break;
            case '📦 Меню':
                // Выводим категории в виде кнопок
                $stmt = $conn->prepare("SELECT id, category_title FROM categories");
                $stmt->execute();
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $inlineKeyboard = [];
                foreach ($categories as $category) {
                    $inlineKeyboard[] = [
                        ['text' => $category['category_title'], 'callback_data' => 'category']
                    ];
                }

                $params = [
                    "chat_id" => $chatId,
                    "text" => "Выберите категорию:",
                    'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard]),
                ];
                sendRequest($botToken, "sendMessage", $params);
                break;
            case '🛒 Корзина':
                sendMessages($botToken, $chatId, "Корзина товаров");
            
                $userId = $arrDataAnswer['message']['from']['id']; // Исправлено
            
                // Получаем товары из корзины
                $stmt = $conn->prepare("
                    SELECT p.product_title, p.product_price, c.quantity
                    FROM cart c
                    JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = :user_id
                ");
                $stmt->execute([':user_id' => $userId]);
                $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
                if (empty($cartItems)) {
                    sendMessages($botToken, $chatId, "Ваша корзина пуста.");
                } else {
                    $cartText = "Ваши товары в корзине:\n\n";
                    $totalPrice = 0;
            
                    foreach ($cartItems as $item) {
                        $cartText .= "{$item['product_title']} x{$item['quantity']} = " .
                                        ($item['product_price'] * $item['quantity']) . " UZS\n";
                        $totalPrice += $item['product_price'] * $item['quantity'];
                    }
            
                    $cartText .= "\nОбщая стоимость: $totalPrice UZS";

                    sendMessages($botToken, $chatId, $cartText);

                    // Добавляем кнопку для перехода к оплате
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => 'Перейти к оплате', 'callback_data' => 'pay_now']
                            ]
                        ]
                    ];

                    sendButtons($botToken, $chatId, "Для оформления заказа, нажмите кнопку ниже:", $keyboard);
                }
                break;                 
            case '🛍️ О компании':
                sendMessages($botToken, $chatId, "Информация о компании:");
                if ($aboutImage) {
                    sendImages($botToken, $chatId, $aboutImage, $companyText);
                }
                break;
            case '⭐ Помощь':
                sendMessages($botToken, $chatId, "Добро пожаловать в нашего бота! Вот что вы можете сделать:");
                sendMessages($botToken, $chatId, 
                    "1. Выберите категорию товара.\n" .
                    "2. Просматривайте карточки продуктов.\n" .
                    "3. Добавляйте товары в корзину.\n" .
                    "4. Оформляйте заказ и оплачивайте товары.\n\n" .
                    "Если вам нужна дополнительная помощь, не стесняйтесь спрашивать!");
                break;
            default:
                sendMessages($botToken, $chatId, "Неизвестная команда");
                break;
        }
    } elseif (isset($arrDataAnswer['callback_query'])) {
        $chatId = $arrDataAnswer['callback_query']['message']['chat']['id'];
        $callbackData = $arrDataAnswer['callback_query']['data'];

        switch ($callbackData) {
            case 'menu':
                // Выводим категории в виде кнопок
                $stmt = $conn->prepare("SELECT id, category_title FROM categories");
                $stmt->execute();
                $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $inlineKeyboard = [];
                foreach ($categories as $category) {
                    $inlineKeyboard[] = [
                        ['text' => $category['category_title'], 'callback_data' => 'add_to_cart_' . $product['id']]
                    ];
                }

                $params = [
                    "chat_id" => $chatId,
                    "text" => "Выберите категорию:",
                    'reply_markup' => json_encode(['inline_keyboard' => $inlineKeyboard]),
                ];
                sendRequest($botToken, "sendMessage", $params);
                break;
            case 'category':
                sendMessages($botToken, $chatId, "Выберите товар:");
                if ($menuImage) {
                    sendImages($botToken, $chatId, $menuImage, $productText);
                }
                $keyboard = [
                    'inline_keyboard' => [
                        [
                            ['text' => 'Добавить в корзину', 'callback_data' => 'add_to_cart']
                        ]
                    ]
                ];

                sendButtons($botToken, $chatId, "Чтобы добавить товар в корзину, нажмите кнопку ниже:", $keyboard);
                break;
            case 'cart':
                sendMessages($botToken, $chatId, "Корзина товаров");
                break;
            case 'about':
                sendMessages($botToken, $chatId, "Информация о компании:");
                if ($aboutImage) {
                    sendImages($botToken, $chatId, $aboutImage, $companyText);
                }
                break;
            case 'help':
                sendMessages($botToken, $chatId, "Добро пожаловать в нашего бота! Вот что вы можете сделать:");
                sendMessages($botToken, $chatId, 
                    "1. Выберите категорию товара.\n" .
                    "2. Просматривайте карточки продуктов.\n" .
                    "3. Добавляйте товары в корзину.\n" .
                    "4. Оформляйте заказ и оплачивайте товары.\n\n" .
                    "Если вам нужна дополнительная помощь, не стесняйтесь спрашивать!");
                break;
            case 'add_to_cart':
                ensureCartTableExists($conn); // запуск функции для создания корзины

                $userId = $arrDataAnswer['callback_query']['from']['id']; // ID пользователя
                $productId = $product['id']; // ID товара, нужно передать через callback_data
                
                // Проверяем, есть ли товар в корзине
                $stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = :user_id AND product_id = :product_id");
                $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
                $existingCartItem = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingCartItem) {
                    // Увеличиваем количество
                    $stmt = $conn->prepare("UPDATE cart SET quantity = quantity + 1 WHERE id = :id");
                    $stmt->execute([':id' => $existingCartItem['id']]);
                } else {
                    // Добавляем новый товар в корзину
                    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id) VALUES (:user_id, :product_id)");
                    $stmt->execute([':user_id' => $userId, ':product_id' => $productId]);
                }
  
                sendMessages($botToken, $chatId, "Товар добавлен в корзину");

                break;
            case 'pay_now':
                // Получаем ID пользователя и общую сумму для оплаты
                $userId = $arrDataAnswer['callback_query']['from']['id'];
            
                // Получаем товары из корзины для подсчета общей суммы
                $stmt = $conn->prepare("
                    SELECT p.product_price, c.quantity
                    FROM cart c
                    JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = :user_id
                ");
                $stmt->execute([':user_id' => $userId]);
                $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            
                // Создаем платежную сессию
                $paymentUrl = createPaymentSession($totalPrice, $userId);

                // Логируем сумму и данные запроса
                logError("Создание сессии: сумма = $totalPrice, userId = $userId");
            
                if ($paymentUrl) {
                    // Отправляем ссылку на оплату
                    sendMessages($botToken, $chatId, "Перейдите по следующей ссылке для оплаты: $paymentUrl");
                } else {
                    sendMessages($botToken, $chatId, "Произошла ошибка при создании сессии оплаты.");
                }
                break;                
            default:
                sendMessages($botToken, $chatId, "Неизвестная команда");
                break;
        }
    }
} catch (PDOException $exception) {
    error_log("Ошибка подключения к БД: " . $exception->getMessage());
    die("Ошибка подключения к базе данных.");
}