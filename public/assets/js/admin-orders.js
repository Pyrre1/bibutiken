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

  // ── Mail modals ───────────────────────────────────────────

  const csrfToken = document.querySelector('input[name="csrf_token"]')?.value ?? '';

  function fetchRecipientCount(mailType, callback) {
    const excludeSent = mailType === 'info'
      ? (document.getElementById('info-exclude-sent')?.checked ? '1' : '0')
      : '1';
    const minDays = document.getElementById('reminder-min-days')?.value ?? '7';

    const body = 'csrf_token=' + encodeURIComponent(csrfToken) +
      '&action=get_recipient_count' +
      '&mail_type=' + mailType +
      '&exclude_sent=' + excludeSent +
      '&min_days=' + encodeURIComponent(minDays);

    fetch('/admin/ordrar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: body
    })
      .then(r => r.json())
      .then(data => callback(data))
      .catch(() => callback({ count: '?', preview: null }));
  }

  function setupModal(modalId, openBtnId, cancelBtnId, sendBtnId, countSpanId, previewBtnId, previewBoxId, mailType) {
    const modal = document.getElementById(modalId);
    const openBtn = document.getElementById(openBtnId);
    const cancelBtn = document.getElementById(cancelBtnId);
    const sendBtn = document.getElementById(sendBtnId);
    const countSpan = document.getElementById(countSpanId);
    const previewBtn = document.getElementById(previewBtnId);
    const previewBox = document.getElementById(previewBoxId);

    if (!modal || !openBtn) return;

    function refreshCount() {
      countSpan.textContent = '…';
      fetchRecipientCount(mailType, function (data) {
        countSpan.textContent = data.count;
      });
    }

    openBtn.addEventListener('click', function () {
      previewBox.style.display = 'none';
      sendBtn.style.display = 'none';
      refreshCount();
      modal.showModal();
    });

    cancelBtn?.addEventListener('click', () => modal.close());

    // Close on backdrop click — but not on drag-release outside textarea
    let mousedownTarget = null;
    modal.addEventListener('mousedown', function (e) {
      mousedownTarget = e.target;
    });
    modal.addEventListener('click', function (e) {
      if (e.target === modal && mousedownTarget === modal) modal.close();
    });

    // Live count update on filter change
    if (mailType === 'info') {
      document.getElementById('info-exclude-sent')
        ?.addEventListener('change', refreshCount);
    } else {
      document.getElementById('reminder-min-days')
        ?.addEventListener('change', refreshCount);
    }

    // Variable insert buttons
    modal.querySelectorAll('.insert-var-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        const targetId = btn.dataset.target;
        const variable = btn.dataset.var;
        const textarea = document.getElementById(targetId);
        if (!textarea) return;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        textarea.value = text.slice(0, start) + variable + text.slice(end);
        textarea.selectionStart = textarea.selectionEnd = start + variable.length;
        textarea.focus();
      });
    });

    // Preview
    previewBtn?.addEventListener('click', function () {
      fetchRecipientCount(mailType, function (data) {
        if (!data.preview) {
          previewBox.textContent = 'Inga mottagare att förhandsgranska.';
        } else {
          const subjectEl = document.getElementById(mailType === 'info' ? 'info-subject' : 'reminder-subject');
          const bodyEl = document.getElementById(mailType === 'info' ? 'info-body' : 'reminder-body');
          let subject = subjectEl?.value ?? '';
          let body = bodyEl?.value ?? '';

          // Substitute placeholders
          [subject, body].forEach((_, i) => {
            const s = [subject, body][i]
              .replaceAll('{namn}', data.preview.namn)
              .replaceAll('{vara}', data.preview.vara)
              .replaceAll('{pris}', data.preview.pris);
            if (i === 0) subject = s; else body = s;
          });

          previewBox.textContent = 'Ämne: ' + subject + '\n\n' + body;
        }
        previewBox.style.display = 'block';
        sendBtn.style.display = '';
      });
    });

    // Send confirmation
    sendBtn?.addEventListener('click', function (e) {
      e.preventDefault();
      fetchRecipientCount(mailType, function (data) {
        const count = data.count;
        if (count === 0) {
          alert('Inga mottagare matchade filtren.');
          return;
        }
        const btnRect = sendBtn.getBoundingClientRect();
        const fakeEvent = {
          clientX: btnRect.left + btnRect.width / 2,
          clientY: btnRect.top
        };
        showConfirmPopup(fakeEvent,
          'Skicka till ' + count + ' kunder. Vill du fortsätta?',
          function () {
            const formId = mailType === 'info' ? 'form-info-mail' : 'form-reminder-mail';
            const form = document.getElementById(formId);
            form.submit();
          }
        );
        // Move popup inside dialog so it renders above the modal backdrop
        const popup = document.querySelector('.admin-confirm-popup');
        if (popup) {
          modal.appendChild(popup);
          popup.style.position = 'absolute';
          popup.style.left = '50%';
          popup.style.top = '50%';
          popup.style.transform = 'translate(-50%, -50%)';
        }
      });
    });
  }

  setupModal(
    'modal-info-mail', 'btn-open-info-mail', 'btn-info-cancel',
    'btn-info-send', 'info-recipient-count', 'btn-info-preview',
    'info-preview-box', 'info'
  );

  setupModal(
    'modal-reminder-mail', 'btn-open-reminder-mail', 'btn-reminder-cancel',
    'btn-reminder-send', 'reminder-recipient-count', 'btn-reminder-preview',
    'reminder-preview-box', 'reminder'
  );

  // ── Export "Alla" confirm popup ───────────────────────────
  document.querySelector('a[href="/admin/exportera/ordrar?type=all"]')
    ?.addEventListener('click', function (e) {
      e.preventDefault();
      const href = this.href;
      showConfirmPopup(e,
        'Denna export är för prisöversikt - inte för att mejla kunder. ' +
        'Mejl skickas via "Ej hämtade". Vill du fortsätta?',
        function () { window.location.href = href; }
      );
    });
});