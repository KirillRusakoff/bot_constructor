const formCategory = document.querySelector('#form-category');

formCategory.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: 'php/category_data/category_data.php',
        type: 'POST',
        data: formData,
        processData: false, // Не обрабатываем данные как строку
        contentType: false, // Не устанавливаем content-type
        success: function(response) {
            console.log("Данные переданы успешно!");
            console.log("Ответ сервера:", response);

            if (response.status === 'success') {
                window.location.href = './../../create4.html'; // Перенаправление только при успешной передаче данных
            } else {
                alert(response.message); // Отображение ошибки, если произошла ошибка
            }
        },
        error: function(xhr, status, error) {
            console.log("Ошибка регистрации: " + xhr.responseText);
            alert("Произошла ошибка при добавлении категории. Пожалуйста, попробуйте позже."); // Уведомление пользователя о проблеме
        }
    });
});