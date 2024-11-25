const deliveryButton = document.querySelector('#delivery');
const deliveryDesc = document.querySelector('#delivery-desc');

deliveryButton.addEventListener('change', function() {
    deliveryDesc.style.display = deliveryButton.checked ? 'block' : 'none';
});

if (deliveryButton.checked) {
    deliveryDesc.style.display = 'block';
} else {
    deliveryDesc.style.display = 'none';
}

const freeDelivery = document.querySelector('#free-delivery');
const fixDelivery = document.querySelector('#fix-delivery');
const flexDelivery = document.querySelector('#flex-delivery');
const customDelivery = document.querySelector('#custom-delivery');

const priceDeliveryTitle = document.querySelector('#price-delivery-title');
const priceDelivery = document.querySelector('#price-delivery');

const calcDeliveryTitle = document.querySelector('#calc-delivery-title');
const calcDelivery = document.querySelector('#calc-delivery');

const customDeliveryDescTitle = document.querySelector('#сustom-delivery-desc-title');
const customDeliveryDesc = document.querySelector('#сustom-delivery-desc');

function toggleDeliveryOptions() {
    if (freeDelivery.checked) {
        priceDelivery.style.display = 'none';
        priceDeliveryTitle.style.display = 'none';
        calcDelivery.style.display = 'none';
        calcDeliveryTitle.style.display = 'none';
        customDeliveryDesc.style.display = 'none';
        customDeliveryDescTitle.style.display = 'none';
    } else if (fixDelivery.checked) {
        priceDelivery.style.display = 'block';
        priceDeliveryTitle.style.display = 'block';
        calcDelivery.style.display = 'none';
        calcDeliveryTitle.style.display = 'none';
        customDeliveryDesc.style.display = 'none';
        customDeliveryDescTitle.style.display = 'none';
    } else if (flexDelivery.checked) {
        priceDelivery.style.display = 'none';
        priceDeliveryTitle.style.display = 'none';
        calcDelivery.style.display = 'block';
        calcDeliveryTitle.style.display = 'block';
        customDeliveryDesc.style.display = 'none';
        customDeliveryDescTitle.style.display = 'none';
    } else if (customDelivery.checked) {
        priceDelivery.style.display = 'none';
        priceDeliveryTitle.style.display = 'none';
        calcDelivery.style.display = 'none';
        calcDeliveryTitle.style.display = 'none';
        customDeliveryDesc.style.display = 'block';
        customDeliveryDescTitle.style.display = 'block';
    }
}

freeDelivery.addEventListener('change', toggleDeliveryOptions);
fixDelivery.addEventListener('change', toggleDeliveryOptions);
flexDelivery.addEventListener('change', toggleDeliveryOptions);
customDelivery.addEventListener('change', toggleDeliveryOptions);

toggleDeliveryOptions();

const formDelivery = document.querySelector('#form-delivery');

formDelivery.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    $.ajax({
        url: 'php/delivery_data/delivery_data.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            console.log("Данные переданы успешно!");
            console.log("Ответ сервера:", response);
            if (response.message === "Данные о доставке успешно добавлены!") {
                window.location.href = './../../create6.php'; //изменен для теста
            }
        },
        error: function(xhr, status, error) {
            console.log("Ошибка регистрации: " + xhr.responseText);
            alert("Ошибка при добавлении данных о доставке. Пожалуйста, попробуйте еще раз.");
        }
    });
});