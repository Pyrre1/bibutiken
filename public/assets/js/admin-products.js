'use strict';

document.addEventListener('DOMContentLoaded', function () {
  // ── Inline row edit ───────────────────────────────────────
  document.querySelectorAll('#products-tbody .btn-edit-row').forEach(function (btn) {
    const row = btn.closest('tr');
    btn.addEventListener('click', function () {
      row.querySelectorAll('.item-display').forEach(el => el.style.display = 'none');
      row.querySelectorAll('.item-edit').forEach(el => el.style.display = '');
      btn.style.display = 'none';

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

  // ── Sort: up/down with live swap ──────────────────────────
  const tbody = document.getElementById('products-tbody');
  const applyRow = document.getElementById('sort-apply-row');
  const applyBtn = document.getElementById('apply-sort-btn');
  const cancelBtn = document.getElementById('cancel-sort-btn');
  let originalOrder = null;

  function getRows() {
    return Array.from(tbody.querySelectorAll('tr.product-row'));
  }

  function saveOriginalOrder() {
    if (!originalOrder) originalOrder = getRows().map(r => r.dataset.id);
  }

  function swapRows(rowA, rowB) {
    const parent = rowA.parentNode;
    const nextB = rowB.nextSibling;
    parent.insertBefore(rowB, rowA);
    parent.insertBefore(rowA, nextB);
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

    if (upBtn && idx > 0) swapRows(rows[idx - 1], row);
    else if (downBtn && idx < rows.length - 1) swapRows(row, rows[idx + 1]);
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
    originalOrder.forEach(id => {
      const row = tbody.querySelector(`tr[data-id="${id}"]`);
      if (row) tbody.appendChild(row);
    });
    originalOrder = null;
    applyRow.style.display = 'none';
  });

  // ── Lagersaldo: show old entries toggle ───────────────────
  document.getElementById('show-old-saldo-btn')?.addEventListener('click', function () {
    const tbl = document.getElementById('old-saldo-table');
    if (tbl) {
      tbl.style.display = '';
      this.style.display = 'none';
    }
  });

  // ── Local sales: dynamic row add/remove ───────────────────
  const lsContainer = document.getElementById('local-sales-rows');
  const lsAddBtn = document.getElementById('ls-add-row-btn');

  function buildProductOptions() {
    // Clone options from first row's select
    const firstSelect = lsContainer.querySelector('select[name="ls_product_id[]"]');
    return firstSelect ? firstSelect.innerHTML : '';
  }

  function updateRemoveButtons() {
    const rows = lsContainer.querySelectorAll('.ls-row');
    rows.forEach(function (row) {
      const btn = row.querySelector('.ls-remove-btn');
      if (btn) btn.style.visibility = rows.length > 1 ? 'visible' : 'hidden';
    });
  }

  lsAddBtn?.addEventListener('click', function () {
    const newRow = document.createElement('div');
    newRow.className = 'ls-row';
    newRow.innerHTML =
      '<div class="saldo-field">' +
      '<label>Produkt</label>' +
      '<select name="ls_product_id[]" required>' + buildProductOptions() + '</select>' +
      '</div>' +
      '<div class="saldo-field">' +
      '<label>Antal</label>' +
      '<input type="number" name="ls_quantity[]" min="1" required style="width:80px">' +
      '</div>' +
      '<div class="saldo-field">' +
      '<label>Datum</label>' +
      '<input type="date" name="ls_date[]" required value="' + new Date().toISOString().slice(0, 10) + '">' +
      '</div>' +
      '<div class="saldo-field ls-remove-col">' +
      '<label>&nbsp;</label>' +
      '<button type="button" class="btn-icon btn-icon--danger ls-remove-btn" title="Ta bort rad">✕</button>' +
      '</div>';
    lsContainer.appendChild(newRow);
    updateRemoveButtons();
  });

  lsContainer?.addEventListener('click', function (e) {
    const removeBtn = e.target.closest('.ls-remove-btn');
    if (!removeBtn) return;
    removeBtn.closest('.ls-row').remove();
    updateRemoveButtons();
  });
});