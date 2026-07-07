(function () {
    'use strict';

    // Cart is kept as a simple in-memory array of {productId, name, priceOre, quantity}.
    // Re-rendered fully on every change rather than doing fine-grained DOM patches —
    // the list is tiny (a handful of products max), so simplicity wins over micro-optimizing.
    let cart = [];
    let editingIndex = -1;

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

        if (editingIndex >= 0) {
            cart[editingIndex] = { productId, name, priceOre, quantity };
            editingIndex = -1;
        } else {
            const existing = cart.find(function (i) { return i.productId === productId; });
            if (existing) {
                existing.quantity += quantity;
            } else {
                cart.push({ productId: productId, name: name, priceOre: priceOre, quantity: quantity });
            }
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
        const index = cart.findIndex(function (i) { return i.productId === productId; });
        if (index === -1) return;
        const item = cart[index];
        productSelect.value = item.productId;
        quantityInput.value = item.quantity;
        editingIndex = index;
        renderCart();
        productSelect.focus();
    }

    function renderCart() {
        const isEditing = editingIndex >= 0;
        cartTableBody.innerHTML = '';
        hiddenInputsContainer.innerHTML = '';

        let totalQty = 0;
        let totalSum = 0;

        cart.forEach(function (item, index) {
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
            priceCell.textContent = formatKr(item.priceOre).replace(' kr', '');
            row.appendChild(priceCell);

            const lineTotalCell = document.createElement('td');
            lineTotalCell.textContent = formatKr(lineTotal).replace(' kr', '');
            row.appendChild(lineTotalCell);

            const editCell = document.createElement('td');
            const editBtn = document.createElement('button');
            editBtn.type = 'button';
            editBtn.textContent = '✎';
            editBtn.title = 'Ändra antal';
            if (isEditing) {
                editBtn.disabled = true;
            } else {
                editBtn.addEventListener('click', function () { startEdit(item.productId); });
            }
            editCell.appendChild(editBtn);
            row.appendChild(editCell);

            const removeCell = document.createElement('td');
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.textContent = '✕';
            removeBtn.title = 'Ta bort';
            if (isEditing) {
                removeBtn.disabled = true;
            } else {
                removeBtn.addEventListener('click', function () { removeItem(item.productId); });
            }
            removeCell.appendChild(removeBtn);
            row.appendChild(removeCell);

            if (index === editingIndex) {
                row.classList.add('cart-row-editing');
            }

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

        productSelect.disabled = isEditing;
        addButton.textContent = isEditing ? 'Uppdatera' : 'Lägg till';

        let cancelEditBtn = document.getElementById('cancel-edit-btn');
        if (isEditing) {
            if (!cancelEditBtn) {
                cancelEditBtn = document.createElement('button');
                cancelEditBtn.type = 'button';
                cancelEditBtn.id = 'cancel-edit-btn';
                cancelEditBtn.textContent = 'Avbryt';
                cancelEditBtn.classList.add('btn-secondary');
                cancelEditBtn.addEventListener('click', function () {
                    editingIndex = -1;
                    productSelect.value = '';
                    quantityInput.value = '1';
                    renderCart();
                });
                addButton.insertAdjacentElement('beforebegin', cancelEditBtn);
            }
        } else {
            if (cancelEditBtn) cancelEditBtn.remove();
        }
    }

    if (form) {
        const infoBanner = document.getElementById('preorder-info-banner');
        const closeInfoBtn = document.getElementById('close-info-banner');
        if (infoBanner && closeInfoBtn) {
            closeInfoBtn.addEventListener('click', function () {
                infoBanner.style.display = 'none';
            });
        }


        addButton.addEventListener('click', addItem);

        const modal = document.getElementById('confirm-modal');
        const modalMessage = document.getElementById('modal-message');
        const modalConfirm = document.getElementById('modal-confirm');
        const modalCancel = document.getElementById('modal-cancel');

        form.addEventListener('submit', function (event) {
            if (cart.length === 0) {
                event.preventDefault();
                return;
            }

            event.preventDefault();

            const hasFeedBox = cart.some(function (i) { return i.name.toLowerCase().includes('obehandlad'); });
            const hasLack = cart.some(function (i) { return i.name.toLowerCase().includes('lack'); });

            let msg = 'Är du säker på att allt stämmer? När du skickat in beställningen kommer du inte längre kunna ändra utan att kontakta butiken.';

            if (!hasFeedBox) {
                msg += '\n\nTips: Du har väl inte glömt att lägga till foderlåda och lack till foderlåda?';
            } else if (!hasLack) {
                msg += '\n\nTips: Att lacka lådan gör det lättare att hålla den ren och ger större chans att kunna återanvända den längre tid.';
            }

            modalMessage.textContent = msg;
            modal.removeAttribute('hidden');
        });

        modalCancel.addEventListener('click', function () {
            modal.setAttribute('hidden', '');
        });

        modalConfirm.addEventListener('click', function () {
            modal.setAttribute('hidden', '');
            form.submit();
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

// ── Orders: confirm deliver ──────────────────────────────────
function confirmDeliver(btn) {
    const name = btn.dataset.name;
    return confirm(`Är du säker på att ${name} hämtat alla sina varor och att ordern ska utlevereras?`);
}

// ── Orders: sort + paginate ──────────────────────────────────
(function () {
    const table = document.getElementById('orders-table');
    const pagination = document.getElementById('orders-pagination');
    if (!table || !pagination) return;

    const PAGE_SIZE = 20;
    let currentPage = 1;
    let sortCol = 3; // Datum column index (0-based)
    let sortAsc = true;

    const tbody = table.querySelector('tbody');
    const headers = table.querySelectorAll('th');

    function getRows() {
        return Array.from(tbody.querySelectorAll('tr[data-sortable]'));
    }

    function sortRows() {
        const rows = getRows();
        rows.sort((a, b) => {
            const aVal = a.children[sortCol]?.dataset.sort ?? a.children[sortCol]?.textContent.trim() ?? '';
            const bVal = b.children[sortCol]?.dataset.sort ?? b.children[sortCol]?.textContent.trim() ?? '';
            return sortAsc ? aVal.localeCompare(bVal, 'sv') : bVal.localeCompare(aVal, 'sv');
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    function renderPage() {
        const rows = getRows();
        const start = (currentPage - 1) * PAGE_SIZE;
        rows.forEach((r, i) => {
            r.style.display = (i >= start && i < start + PAGE_SIZE) ? '' : 'none';
        });
        renderPagination(rows.length);
    }

    function renderPagination(total) {
        const pages = Math.ceil(total / PAGE_SIZE);
        pagination.innerHTML = '';
        if (pages <= 1) return;
        for (let i = 1; i <= pages; i++) {
            const btn = document.createElement('button');
            btn.textContent = i;
            if (i === currentPage) btn.classList.add('active');
            btn.addEventListener('click', () => { currentPage = i; renderPage(); });
            pagination.appendChild(btn);
        }
    }

    // Mark rows as sortable
    getRows().forEach(r => r.setAttribute('data-sortable', '1'));

    // Sort indicators on headers
    headers.forEach((th, i) => {
        th.addEventListener('click', () => {
            if (sortCol === i) { sortAsc = !sortAsc; }
            else { sortCol = i; sortAsc = true; }
            headers.forEach(h => h.textContent = h.textContent.replace(/ [▲▼]$/, ''));
            th.textContent += sortAsc ? ' ▲' : ' ▼';
            currentPage = 1;
            sortRows();
            renderPage();
        });
    });

    // Initial render
    sortRows();
    renderPage();
}());

// ── Order detail: inline edit toggle ────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.btn-edit-row').forEach(function (btn) {
        const row = btn.closest('tr');

        btn.addEventListener('click', function () {
            row.querySelectorAll('.item-display').forEach(el => el.style.display = 'none');
            row.querySelectorAll('.item-edit').forEach(el => el.style.display = '');
            btn.style.display = 'none';

            const productSelect = row.querySelector('select[name="product_id"]');
            const qtyInput = row.querySelector('input[type="number"]');
            const hiddenProduct = row.querySelector('.save-product-id');
            const hiddenQty = row.querySelector('.save-quantity');

            if (productSelect) productSelect.addEventListener('change', () => hiddenProduct.value = productSelect.value);
            if (qtyInput) qtyInput.addEventListener('input', () => hiddenQty.value = qtyInput.value);
        });

        row.querySelector('.btn-cancel-row')?.addEventListener('click', function () {
            row.querySelectorAll('.item-display').forEach(el => el.style.display = '');
            row.querySelectorAll('.item-edit').forEach(el => el.style.display = 'none');
            btn.style.display = '';
        });
    });
});


// ── Products: inline edit ────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('#products-tbody .btn-edit-row').forEach(function (btn) {
        const row = btn.closest('tr');

        btn.addEventListener('click', function () {
            row.querySelectorAll('.item-display').forEach(el => el.style.display = 'none');
            row.querySelectorAll('.item-edit').forEach(el => el.style.display = '');
            btn.style.display = 'none';

            // Sync hidden inputs
            const nameInput = row.querySelector('input[name="name"]');
            const priceInput = row.querySelector('input[name="price_kr"]');
            const manualSel = row.querySelector('select[name="needs_manual_work"]');
            const saveName = row.querySelector('.save-name');
            const savePrice = row.querySelector('.save-price');
            const saveManual = row.querySelector('.save-manual');

            if (nameInput) nameInput.addEventListener('input', () => saveName.value = nameInput.value);
            if (priceInput) priceInput.addEventListener('input', () => savePrice.value = priceInput.value);
            if (manualSel) manualSel.addEventListener('change', () => saveManual.value = manualSel.value);
        });

        row.querySelector('.btn-cancel-row')?.addEventListener('click', function () {
            row.querySelectorAll('.item-display').forEach(el => el.style.display = '');
            row.querySelectorAll('.item-edit').forEach(el => el.style.display = 'none');
            btn.style.display = '';
        });
    });

    // ── Sort: up/down with live swap ─────────────────────────
    const tbody = document.getElementById('products-tbody');
    const applyRow = document.getElementById('sort-apply-row');
    const applyBtn = document.getElementById('apply-sort-btn');
    const cancelBtn = document.getElementById('cancel-sort-btn');
    let originalOrder = null;

    function getRows() {
        return Array.from(tbody.querySelectorAll('tr.product-row'));
    }

    function saveOriginalOrder() {
        if (!originalOrder) {
            originalOrder = getRows().map(r => r.dataset.id);
        }
    }

    function swapRows(rowA, rowB) {
        const parent = rowA.parentNode;
        const nextB = rowB.nextSibling;
        parent.insertBefore(rowB, rowA);
        parent.insertBefore(rowA, nextB);

        // CSS transition feedback
        [rowA, rowB].forEach(r => {
            r.style.opacity = '0.5';
            setTimeout(() => r.style.opacity = '1', 200);
        });

        applyRow.style.display = '';
    }

    tbody?.addEventListener('click', function (e) {
        const upBtn = e.target.closest('.btn-sort-up');
        const downBtn = e.target.closest('.btn-sort-down');
        if (!upBtn && !downBtn) return;

        saveOriginalOrder();
        const row = (upBtn || downBtn).closest('tr');
        const rows = getRows();
        const idx = rows.indexOf(row);

        if (upBtn && idx > 0) {
            swapRows(rows[idx - 1], row);
        } else if (downBtn && idx < rows.length - 1) {
            swapRows(row, rows[idx + 1]);
        }
    });

    applyBtn?.addEventListener('click', function () {
        const ids = getRows().map(r => r.dataset.id);
        const csrf = document.querySelector('input[name="csrf_token"]')?.value;

        fetch('/admin/products.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=reorder&csrf_token=' + encodeURIComponent(csrf) + '&' +
                ids.map(id => 'ordered_ids[]=' + id).join('&')
        }).then(() => {
            originalOrder = null;
            applyRow.style.display = 'none';
        });
    });

    cancelBtn?.addEventListener('click', function () {
        if (!originalOrder) return;
        // Restore original DOM order
        originalOrder.forEach(id => {
            const row = tbody.querySelector(`tr[data-id="${id}"]`);
            if (row) tbody.appendChild(row);
        });
        originalOrder = null;
        applyRow.style.display = 'none';
    });
});