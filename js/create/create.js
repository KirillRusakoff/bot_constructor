const formCompany = document.querySelector('#bot-token');

formCompany.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = $(this).serialize();

    $.ajax({
        url: 'php/company_data/company_data2.php',
        type: 'POST',
        data: formData,
        success: function(response) {
            console.log("Raw response:", response); // Логирование ответа до парсинга

            try {
                // Проверяем, что ответ сервера корректен
                const data = JSON.parse(response);

                if (data.data) {
                    console.log("Данные переданы успешно!");
                    console.log("Ответ сервера:", data);

                    // Переход на следующую страницу
                    window.location.href = './../create2.html';
                } else {
                    console.log("Ошибка регистрации: " + data.message);
                    alert("Ошибка регистрации: " + data.message);
                }
            } catch (e) {
                console.log("Ошибка при обработке ответа сервера: " + e.message);
                // alert("Ошибка при обработке ответа сервера.");

                // Принудительный переход, несмотря на ошибку
                window.location.href = './../create2.html';
            }
        },
        error: function(xhr, status, error) {
            console.log("Ошибка регистрации: " + xhr.responseText);
            alert("Ошибка регистрации: " + xhr.responseText);

            // Принудительный переход, несмотря на ошибку
            window.location.href = './../create2.html';
        }
    });
});