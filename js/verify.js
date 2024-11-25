// отправка запроса на верификацию
const formVerify = document.querySelector('.form--verify');

$(document).ready(function() {
    $('.form--verify').on('submit', function(e) {
        e.preventDefault();

        const formData = $(this).serialize(); // Только код верификации

        $.ajax({
            url: 'php/registration_and_authorization/verify.php',
            type: 'POST',
            data: formData,
            beforeSend: function() {
                $('.button--ok').prop('disabled', true).text('Проверка...');
            },
            success: function(response) {
                // Проверяем сообщение от сервера
                if (response.message === "Верификация прошла успешно!") {
                    window.location.href = './../welcome.html';
                } else {
                    alert(response.message || "Неизвестная ошибка.");
                    $('.button--ok').prop('disabled', false).text('Подтвердить');
                }
            },
            error: function(xhr, status, error) {
                try {
                    const errorResponse = JSON.parse(xhr.responseText);
                    console.log("Ошибка верификации: ", errorResponse);
                    alert(errorResponse.message || "Произошла ошибка. Попробуйте еще раз.");
                } catch (e) {
                    console.log("Ошибка верификации: " + xhr.responseText);
                    alert("Произошла ошибка. Попробуйте еще раз.");
                }
                $('.button--ok').prop('disabled', false).text('Подтвердить');
            }
        });
    });
});