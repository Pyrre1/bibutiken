'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const hoursForm = document.getElementById('hours-form');
  if (!hoursForm) return;

  hoursForm.addEventListener('submit', function (event) {
    let openDays = 0;
    for (let d = 1; d <= 7; d++) {
      if (document.querySelector('input[name="open_' + d + '"]')?.checked) openDays++;
    }

    const ft1 = (document.querySelector('textarea[name="free_text_1"]')?.value || '').replace(/\s/g, '');
    const ft2 = (document.querySelector('textarea[name="free_text_2"]')?.value || '').replace(/\s/g, '');
    const charCount = ft1.length + ft2.length;
    const headerText = (document.querySelector('input[name="header_text"]')?.value || '').trim();
    const hasName = headerText.length > 0;

    if ((hasName || openDays > 0) && charCount < 20 && openDays === 0) {
      if (!confirm('Hoppsan, det ser ut som att planen är nästan tom — vill du spara ändå?')) {
        event.preventDefault();
      }
    }
  });
});