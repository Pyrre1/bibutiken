'use strict';
(function () {
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

  if (!form) return;

  function formatKr(ore) {
    return (ore / 100).toLocaleString('sv-SE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' kr';
  }

  function addItem() {
    const productId = parseInt(productSelect.value, 10);
    const quantity = parseInt(quantityInput.value, 10);
    if (!productId || isNaN(quantity) || quantity < 1) return;

    const selectedOption = productSelect.options[productSelect.selectedIndex];
    const name = selectedOption.dataset.name;
    const priceOre = parseInt(selectedOption.dataset.priceOre, 10);

    if (editingIndex >= 0) {
      cart[editingIndex] = { productId, name, priceOre, quantity };
      editingIndex = -1;
    } else {
      const existing = cart.find(i => i.productId === productId);
      if (existing) {
        existing.quantity += quantity;
      } else {
        cart.push({ productId, name, priceOre, quantity });
      }
    }

    quantityInput.value = '1';
    productSelect.value = '';
    renderCart();
  }

  function removeItem(productId) {
    cart = cart.filter(item => item.productId !== productId);
    renderCart();
  }

  function startEdit(productId) {
    const index = cart.findIndex(i => i.productId === productId);
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
      if (isEditing) { editBtn.disabled = true; }
      else { editBtn.addEventListener('click', () => startEdit(item.productId)); }
      editCell.appendChild(editBtn);
      row.appendChild(editCell);

      const removeCell = document.createElement('td');
      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.textContent = '✕';
      removeBtn.title = 'Ta bort';
      if (isEditing) { removeBtn.disabled = true; }
      else { removeBtn.addEventListener('click', () => removeItem(item.productId)); }
      removeCell.appendChild(removeBtn);
      row.appendChild(removeCell);

      if (index === editingIndex) row.classList.add('cart-row-editing');
      cartTableBody.appendChild(row);

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

  // Info banner close
  const infoBanner = document.getElementById('preorder-info-banner');
  const closeInfoBtn = document.getElementById('close-info-banner');
  if (infoBanner && closeInfoBtn) {
    closeInfoBtn.addEventListener('click', () => infoBanner.style.display = 'none');
  }

  addButton.addEventListener('click', addItem);

  // Submit confirm modal
  const modal = document.getElementById('confirm-modal');
  const modalMessage = document.getElementById('modal-message');
  const modalConfirm = document.getElementById('modal-confirm');
  const modalCancel = document.getElementById('modal-cancel');

  form.addEventListener('submit', function (event) {
    if (cart.length === 0) { event.preventDefault(); return; }
    event.preventDefault();

    const hasFeedBox = cart.some(i => i.name.toLowerCase().includes('obehandlad'));
    const hasLack = cart.some(i => i.name.toLowerCase().includes('lack'));
    const hasHandmade = cart.some(i => i.name.toLowerCase().includes('rdiglackad'));

    let msg = 'Är du säker på att allt stämmer? När du skickat in beställningen kommer du inte längre kunna ändra utan att kontakta butiken.';
    if (!hasFeedBox && !hasHandmade) {
      msg += '\n\nTips: Du har väl inte glömt att lägga till foderlåda och lack till foderlåda?';
    } else if (!hasLack && !hasHandmade) {
      msg += '\n\nTips: Att lacka lådan gör det lättare att hålla den ren och ger större chans att kunna återanvända den längre tid.';
    }

    modalMessage.textContent = msg;
    modal.removeAttribute('hidden');
  });

  modalCancel.addEventListener('click', () => modal.setAttribute('hidden', ''));
  modalConfirm.addEventListener('click', function () {
    modal.setAttribute('hidden', '');
    form.submit();
  });

  renderCart();
})();