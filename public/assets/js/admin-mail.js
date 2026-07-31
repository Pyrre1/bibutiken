'use strict';

document.addEventListener('DOMContentLoaded', function () {

  // ── Delete template (via hidden form, no nested <form>) ───
  const deleteForm = document.getElementById('form-delete-template');
  const deleteIdInput = document.getElementById('delete-template-id');

  document.querySelectorAll('.delete-template-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const id = btn.dataset.id;
      const namn = btn.dataset.namn;
      if (!confirm('Ta bort mallen "' + namn + '"? Detta går inte att ångra.')) return;
      deleteIdInput.value = id;
      deleteForm.submit();
    });
  });

  // ── Variable insert buttons ───────────────────────────────
  // Works for both existing-template forms and the new-template form.
  document.querySelectorAll('.insert-var-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const textarea = document.getElementById(btn.dataset.target);
      if (!textarea) return;
      const variable = btn.dataset.var;
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      textarea.value =
        textarea.value.slice(0, start) +
        variable +
        textarea.value.slice(end);
      textarea.selectionStart =
        textarea.selectionEnd = start + variable.length;
      textarea.focus();
    });
  });

});