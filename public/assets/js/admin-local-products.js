document.addEventListener('DOMContentLoaded', () => {

  // ── Inline edit ──────────────────────────────────────────
  document.querySelectorAll('.btn-edit-lp').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      enterEditMode(row);
    });
  });

  document.querySelectorAll('.btn-cancel-lp').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      exitEditMode(row);
    });
  });

  // Before save-form submits, copy live input values into hidden fields
  document.querySelectorAll('.lp-save-form').forEach(form => {
    form.addEventListener('submit', () => {
      const row = form.closest('tr');
      form.querySelector('.save-name').value =
        row.querySelector('input[name="name"]').value;
      form.querySelector('.save-size').value =
        row.querySelector('input[name="size"]').value;
      form.querySelector('.save-desc').value =
        row.querySelector('textarea[name="description"]').value;
      form.querySelector('.save-price').value =
        row.querySelector('input[name="price_kr"]').value;
    });
  });

  function enterEditMode(row) {
    row.querySelectorAll('.item-display').forEach(el => el.style.display = 'none');
    row.querySelectorAll('.item-edit').forEach(el => el.style.display = '');
    row.querySelector('.btn-edit-lp').style.display = 'none';
  }

  function exitEditMode(row) {
    row.querySelectorAll('.item-display').forEach(el => el.style.display = '');
    row.querySelectorAll('.item-edit').forEach(el => el.style.display = 'none');
    row.querySelector('.btn-edit-lp').style.display = '';
  }

  // ── Sort (per-type tbody) ────────────────────────────────
  document.querySelectorAll('.lp-tbody').forEach(tbody => {
    tbody.addEventListener('click', e => {
      const upBtn = e.target.closest('.btn-sort-up');
      const downBtn = e.target.closest('.btn-sort-down');
      if (!upBtn && !downBtn) return;

      const row = (upBtn || downBtn).closest('tr');
      const rows = [...tbody.querySelectorAll('tr')];
      const idx = rows.indexOf(row);

      if (upBtn && idx > 0) {
        tbody.insertBefore(row, rows[idx - 1]);
      } else if (downBtn && idx < rows.length - 1) {
        tbody.insertBefore(rows[idx + 1], row);
      } else {
        return;
      }

      persistOrder(tbody);
    });
  });

  function persistOrder(tbody) {
    const ids = [...tbody.querySelectorAll('tr[data-id]')]
      .map(r => r.dataset.id);

    const fd = new FormData();
    fd.append('action', 'reorder');
    fd.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    ids.forEach(id => fd.append('ordered_ids[]', id));

    fetch(window.location.pathname, { method: 'POST', body: fd })
      .catch(() => console.warn('Reorder save failed'));
  }
});