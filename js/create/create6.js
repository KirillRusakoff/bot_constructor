function openTelegramBot() {
    const botName = $('#telegram-bot').val();
    const url = `https://t.me/${botName}`;

    // Открываем Telegram бота в новом окне
    window.open(url, '_blank');

    // Отправляем запрос на установку вебхуков, не дожидаясь ответа
    $.ajax({
        url: 'php/webhooks/send_token.php',
        type: 'POST',
        data: JSON.stringify({ botName: botName }),
        contentType: 'application/json'
    });
}

$('#telegram-question').on('click', function(){
    $('#telegram-help').css('display', 'block');

    setTimeout(function(){
        $('#telegram-help').css('display', 'none');
    }, 2000);
});

// function openTelegramBot() {
//     const botName = $('#telegram-bot').val();
//     const url = `https://t.me/${botName}`;

//     $.ajax({
//         url: 'php/webhooks/webhook.php',
//         type: 'POST',
//         data: JSON.stringify({ botName: botName }),
//         contentType: 'application/json',
//         success: function(response) {
//             const data = JSON.parse(response);
//             if (data.status === 'success') {
//                 window.open(url, '_blank');
//             } else {
//                 alert('Не удалось выполнить действие.');
//             }
//         },
//         error: function(xhr, status, error) {
//             console.error('Ошибка:', error);
//             alert('Произошла ошибка при отправке запроса.');
//             // window.open(url, '_blank');
//         },
//     });
// }