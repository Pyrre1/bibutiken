// Shared admin helpers — usable on any admin page.
'use strict';

/**
 * Shows a small confirm popup near the triggering click, with Ja/Nej buttons.
 * @param {MouseEvent} event - the click event that triggered this (for positioning)
 * @param {string} message - confirmation text
 * @param {function} onConfirm - called if user clicks "Ja"
 */
function showConfirmPopup(event, message, onConfirm) {
  document.querySelectorAll('.admin-confirm-popup').forEach(function (el) { el.remove(); });

  const popup = document.createElement('div');
  popup.className = 'admin-confirm-popup';
  popup.innerHTML =
    '<p></p>' +
    '<div class="admin-confirm-popup-actions">' +
    '<button type="button" class="btn-secondary-link admin-confirm-cancel">Nej</button>' +
    '<button type="button" class="btn-deliver admin-confirm-yes">Ja</button>' +
    '</div>';
  popup.querySelector('p').textContent = message;

  document.body.appendChild(popup);

  const rect = popup.getBoundingClientRect();
  const x = event.clientX + window.scrollX;
  const y = event.clientY + window.scrollY;

  // Default: upper-left of click point
  let left = x - rect.width;
  let top = y - rect.height;

  // Clamp to viewport edges
  if (left < window.scrollX) left = window.scrollX + 4;
  if (top < window.scrollY) top = y + 4; // flip below if too close to top

  popup.style.left = left + 'px';
  popup.style.top = top + 'px';

  function close() {
    popup.remove();
    document.removeEventListener('click', outsideClickHandler);
  }

  function outsideClickHandler(e) {
    if (!popup.contains(e.target)) close();
  }
  // Delay binding so the triggering click doesn't immediately close it
  setTimeout(function () { document.addEventListener('click', outsideClickHandler); }, 0);

  popup.querySelector('.admin-confirm-cancel').addEventListener('click', close);
  popup.querySelector('.admin-confirm-yes').addEventListener('click', function () {
    close();
    onConfirm();
  });
}