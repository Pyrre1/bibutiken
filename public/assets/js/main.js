(function () {
    'use strict';

    // Cart is kept as a simple in-memory array of {productId, name, priceOre, quantity}.
    // Re-rendered fully on every change rather than doing fine-grained DOM patches —
    // the list is tiny (a handful of products max), so simplicity wins over micro-optimizing.
    let cart = [];

    const productSelect = document.getElementById('staging_product_id');
    const quantityInput = document.getElementById('staging_quantity');
    const addButton = document.getElementById('add-item-btn');
    const cartTableBody = document.getElementById('cart-table-body');
    const cartTable = document.getElementById('cart-table');
    const emptyMessage = document.getElementById('cart-empty-message');
    const hiddenInputsContainer = document.getElementById('cart-hidden-inputs');
    const totalQtyEl = document.getElementById('cart-total-qty');
    const totalSumEl = document.getElementById('cart-total-sum');
    const submitButton = document.getElementById('submit-order-btn');
    const form = document.getElementById('preorder-form');

    function formatKr(ore) {
        return (ore / 100).toLocaleString('sv-SE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kr';
    }

    function addItem() {
        const productId = parseInt(productSelect.value, 10);
        const quantity = parseInt(quantityInput.value, 10);

        if (!productId || isNaN(quantity) || quantity < 1) {
            return; // staging row itself is required+min=1 via HTML, this is just a guard
        }

        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const name = selectedOption.dataset.name;
        const priceOre = parseInt(selectedOption.dataset.priceOre, 10);

        // If the product is already in the cart, increase its quantity instead of
        // adding a duplicate row — keeps the list clean if someone adds the same
        // product twice in a row.
        const existing = cart.find(function (item) { return item.productId === productId; });
        if (existing) {
            existing.quantity += quantity;
        } else {
            cart.push({ productId: productId, name: name, priceOre: priceOre, quantity: quantity });
        }

        quantityInput.value = '1';
        productSelect.value = '';
        renderCart();
    }

    function removeItem(productId) {
        cart = cart.filter(function (item) { return item.productId !== productId; });
        renderCart();
    }

    function startEdit(productId) {
        const item = cart.find(function (i) { return i.productId === productId; });
        if (!item) return;
        const newQuantity = parseInt(prompt('Nytt antal för ' + item.name + ':', item.quantity), 10);
        if (!isNaN(newQuantity) && newQuantity >= 1 && newQuantity <= 9999) {
            item.quantity = newQuantity;
            renderCart();
        }
    }

    function renderCart() {
        cartTableBody.innerHTML = '';
        hiddenInputsContainer.innerHTML = '';

        let totalQty = 0;
        let totalSum = 0;

        cart.forEach(function (item) {
            const lineTotal = item.priceOre * item.quantity;
            totalQty += item.quantity;
            totalSum += lineTotal;

            const row = document.createElement('tr');

            const nameCell = document.createElement('td');
            nameCell.textContent = item.name;
            row.appendChild(nameCell);

            const qtyCell = document.createElement('td');
            qtyCell.textContent = item.quantity;
            row.appendChild(qtyCell);

            const priceCell = document.createElement('td');
            priceCell.textContent = formatKr(item.priceOre);
            row.appendChild(priceCell);

            const lineTotalCell = document.createElement('td');
            lineTotalCell.textContent = formatKr(lineTotal);
            row.appendChild(lineTotalCell);

            const editCell = document.createElement('td');
            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.textContent = 'Ändra';
            editBtn.addEventListener('click', function () { startEdit(item.productId); });
            editCell.appendChild(editBtn);
            row.appendChild(editCell);

            const removeCell = document.createElement('td');
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = 'Ta bort';
            removeBtn.addEventListener('click', function () { removeItem(item.productId); });
            removeCell.appendChild(removeBtn);
            row.appendChild(removeCell);

            cartTableBody.appendChild(row);

            // Hidden inputs mirrored 1:1 with the cart, so the real <form> POST
            // carries product_id[] / quantity[] for the server to validate and price.
            const productIdInput = document.createElement('input');
            productIdInput.type = 'hidden';
            productIdInput.name = 'product_id[]';
            productIdInput.value = item.productId;
            hiddenInputsContainer.appendChild(productIdInput);

            const quantityHiddenInput = document.createElement('input');
            quantityHiddenInput.type = 'hidden';
            quantityHiddenInput.name = 'quantity[]';
            quantityHiddenInput.value = item.quantity;
            hiddenInputsContainer.appendChild(quantityHiddenInput);
        });

        totalQtyEl.textContent = totalQty;
        totalSumEl.textContent = formatKr(totalSum);

        const isEmpty = cart.length === 0;
        cartTable.style.display = isEmpty ? 'none' : '';
        emptyMessage.style.display = isEmpty ? '' : 'none';
        submitButton.disabled = isEmpty;
    }

    if (form) {
        addButton.addEventListener('click', addItem);
        
        form.addEventListener('submit', function (event) {
            if (cart.length === 0) {
                event.preventDefault();
                return;
            }

            const confirmed = confirm(
                'Är du säker på att allt stämmer? När du skickat beställningen kommer du inte kunna ändra själv utan att mejla.\n\n' +
                'Tips: Du har väl inte glömt att lägga till foderlådor om du behöver det?'
            );
            if (!confirmed) {
                event.preventDefault();
            }
        });

        renderCart(); // initial state: empty cart, table hidden, submit disabled
    }

    // Admin hours form — soft warning for nearly-empty plans
    const hoursForm = document.getElementById('hours-form');
    if (hoursForm) {
        hoursForm.addEventListener('submit', function (event) {
            // Count open days
            let openDays = 0;
            for (let d = 1; d <= 7; d++) {
                if (document.querySelector('input[name="open_' + d + '"]')?.checked) {
                    openDays++;
                }
            }

            const ft1 = (document.querySelector('textarea[name="free_text_1"]')?.value || '').replace(/\s/g, '');
            const ft2 = (document.querySelector('textarea[name="free_text_2"]')?.value || '').replace(/\s/g, '');
            const charCount = ft1.length + ft2.length;

            const headerText = (document.querySelector('input[name="header_text"]')?.value || '').trim();
            const hasName = headerText.length > 0;

            // Soft warn: has a name or some days, but text is thin and no days open
            if ((hasName || openDays > 0) && charCount < 20 && openDays === 0) {
                const confirmed = confirm('Hoppsan, det ser ut som att planen är nästan tom — vill du spara ändå?');
                if (!confirmed) {
                    event.preventDefault();
                }
            }
        });
    }
})();