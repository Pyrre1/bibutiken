'use strict';
document.addEventListener('DOMContentLoaded', function () {
  const table = document.getElementById('customers-table');
  const pagination = document.getElementById('customers-pagination');
  if (!table || !pagination) return;

  const PAGE_SIZE = 25;
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
});