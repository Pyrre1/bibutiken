(function () {
  // ── Info toggle ──────────────────────────────────────────
  const infoBtn = document.querySelector('.site-header__info-toggle');
  const infoPanel = document.getElementById('site-info-panel');

  if (infoBtn && infoPanel) {
    infoBtn.addEventListener('click', function () {
      const open = infoBtn.getAttribute('aria-expanded') === 'true';
      infoBtn.setAttribute('aria-expanded', String(!open));
      infoPanel.hidden = open;
    });
    document.addEventListener('click', function (e) {
      if (!infoPanel.hidden && !infoPanel.contains(e.target) && !infoBtn.contains(e.target)) {
        infoBtn.setAttribute('aria-expanded', 'false');
        infoPanel.hidden = true;
      }
    });
  }

  // ── Hamburger toggle ─────────────────────────────────────
  const hamburger = document.querySelector('.site-header__hamburger');
  const mobileNav = document.getElementById('site-nav-mobile');

  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', function () {
      const open = hamburger.getAttribute('aria-expanded') === 'true';
      hamburger.setAttribute('aria-expanded', String(!open));
      mobileNav.hidden = open;
    });
    document.addEventListener('click', function (e) {
      if (!mobileNav.hidden && !mobileNav.contains(e.target) && !hamburger.contains(e.target)) {
        hamburger.setAttribute('aria-expanded', 'false');
        mobileNav.hidden = true;
      }
    });
  }

  // ── Mobile sub-nav toggle ────────────────────────────────
  document.querySelectorAll('.site-nav-mobile__parent').forEach(function (parent) {
    parent.addEventListener('click', function () {
      const sub = parent.nextElementSibling;
      if (!sub) return;
      const isOpen = sub.classList.contains('is-open');
      document.querySelectorAll('.site-nav-mobile__sub').forEach(function (s) {
        s.classList.remove('is-open');
      });
      document.querySelectorAll('.site-nav-mobile__parent').forEach(function (p) {
        p.classList.remove('is-open');
      });
      if (!isOpen) {
        sub.classList.add('is-open');
        parent.classList.add('is-open');
      }
    });
  });
  // ── Reset panels on resize above mobile breakpoint ───────
  window.addEventListener('resize', function () {
    if (window.innerWidth > 700) {
      if (infoBtn && infoPanel) {
        infoBtn.setAttribute('aria-expanded', 'false');
        infoPanel.hidden = true;
      }
      if (hamburger && mobileNav) {
        hamburger.setAttribute('aria-expanded', 'false');
        mobileNav.hidden = true;
      }
    }
  });
})();