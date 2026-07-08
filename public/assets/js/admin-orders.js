// Orders page — Status column click handlers (📤 deliver / ✅ un-deliver).
'use strict';

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

    fetch('/admin/orders.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'csrf_token=' + encodeURIComponent(csrf) +
        '&action=set_delivered' +
        '&order_id=' + encodeURIComponent(orderId) +
        '&delivered=' + encodeURIComponent(delivered)
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Nätverksfel');
        // Swap icon and rebind — no reload needed
        if (delivered === '1') {
          cell.innerHTML = '<button type="button" class="btn-icon status-undeliver-btn" ' +
            'data-order-id="' + orderId + '" ' +
            'data-csrf="' + csrf + '">✅</button>';
          bindStatusBtn(cell.querySelector('.status-undeliver-btn'));
        } else {
          cell.innerHTML = '<button type="button" class="btn-icon status-deliver-btn" ' +
            'data-order-id="' + orderId + '" ' +
            'data-csrf="' + csrf + '">📤</button>';
          bindStatusBtn(cell.querySelector('.status-deliver-btn'));
        }
      })
      .catch(function () {
        alert('Något gick fel, försök igen.');
      });
  }
});