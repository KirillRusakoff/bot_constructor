//фильтр блоков телефон и email
const radioEmail = document.querySelector('#email_radio');
const radioPhone = document.querySelector('#phone_radio');
const formEmail = document.querySelector('.form--email');
const formPhone = document.querySelector('.form--phone');
const formEmail2 = document.querySelector('.form--email2');
const formPhone2 = document.querySelector('.form--phone2');
const formEmailInputs = formEmail.querySelectorAll('input');
const formPhoneInputs = formPhone.querySelectorAll('input');

function clearInputs(inputs) {
    inputs.forEach(input => {
        input.value = '';
    });
}

radioEmail.addEventListener('change', function(){
    formEmail.style = 'display:flex';
    formPhone.style = 'display:none';
    formEmail2.style = 'display:flex';
    formPhone2.style = 'display:none';
    clearInputs(formPhoneInputs);
});

radioPhone.addEventListener('change', function(){
    formEmail.style = 'display:none';
    formPhone.style = 'display:flex';
    formEmail2.style = 'display:none';
    formPhone2.style = 'display:flex';
    clearInputs(formEmailInputs);
});

//фильтр блоков регистрации и авторизации
const onReg = document.querySelector('#on-reg');
const onReg2 = document.querySelector('#on-reg2');
const onAuth = document.querySelector('#on-auth');
const onAuth2 = document.querySelector('#on-auth2');
const formReg = document.querySelector('.forms--reg');
const formAuth = document.querySelector('.forms--auth');

onReg.addEventListener('click', function(){
    formReg.style = 'display:block';
    formAuth.style = 'display:none';
});

onReg2.addEventListener('click', function(){
    formReg.style = 'display:block';
    formAuth.style = 'display:none';
});

onAuth.addEventListener('click', function(){
    formReg.style = 'display:none';
    formAuth.style = 'display:block';
});

onAuth2.addEventListener('click', function(){
    formReg.style = 'display:none';
    formAuth.style = 'display:block';
});

//отправка запроса на регистрацию
const formsReg = document.querySelectorAll('.forms--reg form');
const verify = document.querySelector('.verify');

formsReg.forEach(function(item){
    item.addEventListener('submit', function(e){
        e.preventDefault();

        const formData = $(this).serialize();

        
        $.ajax({
            url: 'php/registration_and_authorization/registration.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log("Регистрация прошла успешно!");

                window.location.href = './../verify.html';
            },
            error: function(xhr, status, error) {
                console.log("Ошибка регистрации: " + xhr.responseText);
                console.log("Статус: " + status);
                console.log("Ошибка: " + error);
            }
        });
    })
});

//отправка запроса на авторизацию
const formsAuth = document.querySelectorAll('.forms--auth form');

formsAuth.forEach(function(item){
    item.addEventListener('submit', function(e){
        e.preventDefault();

        const formData = $(this).serialize();

        $.ajax({
            url: 'php/registration_and_authorization/authorization.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                console.log("Авторизация прошла успешно!");

                window.location.href = './../main.html';
            },
            error: function(xhr, status, error) {
                console.log("Ошибка авторизации: " + xhr.responseText);
            }
        });
    })
});