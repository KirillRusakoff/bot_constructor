const priceInput = document.getElementById('price');
const unitSelect = document.getElementById('unit');
const productPriceOutput = document.getElementById('product-price-output');

// Функция для обновления текста в span
function updateProductPriceOutput() {
    const price = priceInput.value;
    const unit = unitSelect.value;

    if (price > 0 && unit) {
        const unitText = price > 1 ? `${unit}ов` : unit.toLowerCase();
        productPriceOutput.textContent = `${price} UZS за 1 ${unitText}`;
    } else {
        productPriceOutput.textContent = '';
    }
}

// Добавляем обработчики событий для input и select
priceInput.addEventListener('input', updateProductPriceOutput);
unitSelect.addEventListener('change', updateProductPriceOutput);

const formProduct = document.querySelector('#form-product');

formProduct.addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    $.ajax({
        url: 'php/product_data/product_data.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            try {
                const data = JSON.parse(response);
                console.log("Ответ сервера:", data);
                if (data.status === 'success') {
                    window.location.href = './../../create5.html';
                } else {
                    alert(data.message || 'Произошла ошибка при добавлении продукта.');
                }
            } catch (e) {
                console.error('Некорректный JSON-ответ:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ошибка отправки данных:', error);
            window.location.href = './../../create5.html';
            // alert('Произошла ошибка при добавлении продукта. Попробуйте позже.');
        }
    });    
});