const formCompany = document.querySelector('#form-company');

formCompany.addEventListener('submit', function(e){
    e.preventDefault();

    const formData = new FormData(this); // Используем FormData для отправки файлов

    $.ajax({
        url: 'php/company_data/company_data3.php',
        type: 'POST',
        data: formData,
        processData: false, // Не обрабатываем данные, как строку
        contentType: false, // Не устанавливаем content-type
        success: function(response) {
            console.log("Raw response:", response); // Логирование ответа до парсинга

            try {
                // Проверяем, что ответ сервера корректен
                const data = JSON.parse(response);

                if (data.data) {
                    console.log("Данные переданы успешно!");
                    console.log("Ответ сервера:", data);

                    // Переход на следующую страницу
                    window.location.href = './../create3.html';
                } else {
                    console.log("Ошибка регистрации: " + data.message);
                    alert("Ошибка регистрации: " + data.message);
                }
            } catch (e) {
                console.log("Ошибка при обработке ответа сервера: " + e.message);
                // alert("Ошибка при обработке ответа сервера.");
                // Переход на следующую страницу
                window.location.href = './../create3.html';
            }
        },
        error: function(xhr, status, error) {
            console.log("Ошибка регистрации: " + xhr.responseText);
            alert("Ошибка регистрации: " + xhr.responseText);
        }
    });
});