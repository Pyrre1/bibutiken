'use strict';

// ── Status buttons: deliver / un-deliver ─────────────────────
document.addEventListener('DOMContentLoaded', function () {
  function bindStatusBtn(btn) {
    if (btn.classList.contains('status-deliver-btn')) {
      btn.addEventListener('click', function (event) {
        showConfirmPopup(event, 'Vill du markera denna order som hämtad?', function () {
          submitStatusChange(btn, btn.dataset.orderId, btn.dataset.csrf, '1');
        });
      });
    } else if (btn.classList.contains('status-undeliver-btn')) {
      btn.addEventListener('click', function (event) {
        showConfirmPopup(event, 'Vill du återta order som ej levererad?', function () {
          submitStatusChange(btn, btn.dataset.orderId, btn.dataset.csrf, '0');
        });
      });
    }
  }

  document.querySelectorAll('.status-deliver-btn, .status-undeliver-btn').forEach(bindStatusBtn);

  function submitStatusChange(btn, orderId, csrf, delivered) {
    const cell = btn.closest('td');
    fetch('/admin/ordrar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(csrf) +
        '&action=set_delivered' +
        '&order_id=' + encodeURIComponent(orderId) +
        '&delivered=' + encodeURIComponent(delivered)
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Nätverksfel');
        const reloadBtn = document.getElementById('stats-reload-btn');
        if (reloadBtn) reloadBtn.style.display = '';
        if (delivered === '1') {
          cell.innerHTML = '<button type="button" class="btn-icon status-undeliver-btn" ' +
            'data-order-id="' + orderId + '" data-csrf="' + csrf + '">✅</button>';
          bindStatusBtn(cell.querySelector('.status-undeliver-btn'));
        } else {
          cell.innerHTML = '<button type="button" class="btn-icon status-deliver-btn" ' +
            'data-order-id="' + orderId + '" data-csrf="' + csrf + '">📤</button>';
          bindStatusBtn(cell.querySelector('.status-deliver-btn'));
        }
      })
      .catch(() => alert('Något gick fel, försök igen.'));
  }

  // ── Paginate (list view) ───────────────────────────
  const table = document.getElementById('orders-table');
  const pagination = document.getElementById('orders-pagination');
  if (table && pagination) {
    const PAGE_SIZE = 20;
    let currentPage = 1;

    const tbody = table.querySelector('tbody');

    function getRows() {
      return Array.from(tbody.querySelectorAll('tr'));
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

    renderPage();
  }

  // ── Inline row edit (detail view) ─────────────────────────
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

  // ── Export "Alla" confirm popup ───────────────────────────
  document.querySelector('a[href="/admin/exportera/ordrar?type=all"]')
    ?.addEventListener('click', function (e) {
      e.preventDefault();
      const href = this.href;
      showConfirmPopup(e,
        'Denna export är för prisöversikt – inte för att mejla kunder. ' +
        'Mejl skickas via "Ej hämtade". Vill du fortsätta?',
        function () { window.location.href = href; }
      );
    });
});