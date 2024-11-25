$(document).ready(function() {
    const formCompany = document.querySelector('#form-company');

    formCompany.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: 'php/company_data/company_data.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                // Проверяем наличие и значение поля "message" в ответе
                if (response.message === "Данные о компании добавлены успешно!") {
                    console.log("Данные переданы успешно!");
                    console.log("Ответ сервера:", response);
                    
                    // Перенаправление на следующую страницу
                    window.location.href = './../create.html';
                } else {
                    // Выводим сообщение об ошибке
                    alert(response.message || "Произошла ошибка. Попробуйте еще раз.");
                }
            },
            error: function(xhr, status, error) {
                // Обработка ошибок AJAX запроса
                console.log("Ошибка регистрации: " + xhr.responseText);
                alert("Произошла ошибка на сервере. Попробуйте еще раз.");
            }
        });
    });
});