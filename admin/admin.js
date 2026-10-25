(() => {
  const shell = document.querySelector('[data-admin-shell]');
  if (!shell) return;

  const mobileQuery = window.matchMedia('(max-width: 760px)');
  const collapseButton = shell.querySelector('[data-sidebar-collapse]');
  const openButton = shell.querySelector('[data-sidebar-open]');
  const closeButton = shell.querySelector('[data-sidebar-close]');
  const navLinks = shell.querySelectorAll('.side-nav a');
  const storageKey = 'portfolio-admin-sidebar-collapsed';

  try {
    if (!mobileQuery.matches && localStorage.getItem(storageKey) === 'true') {
      shell.classList.add('is-collapsed');
      collapseButton?.setAttribute('aria-label', 'Expand sidebar');
      collapseButton?.setAttribute('title', 'Expand sidebar');
    }
  } catch (_) {}

  function setMobileOpen(open) {
    shell.classList.toggle('is-mobile-open', open);
    openButton?.setAttribute('aria-expanded', String(open));
    if (open) shell.querySelector('.side-nav a')?.focus();
    else openButton?.focus();
  }

  collapseButton?.addEventListener('click', () => {
    if (mobileQuery.matches) {
      setMobileOpen(false);
      return;
    }
    const collapsed = shell.classList.toggle('is-collapsed');
    collapseButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    collapseButton.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
    try { localStorage.setItem(storageKey, String(collapsed)); } catch (_) {}
  });
  openButton?.addEventListener('click', () => setMobileOpen(true));
  closeButton?.addEventListener('click', () => setMobileOpen(false));
  navLinks.forEach((link) => link.addEventListener('click', () => {
    if (mobileQuery.matches) shell.classList.remove('is-mobile-open');
  }));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && shell.classList.contains('is-mobile-open')) setMobileOpen(false);
  });
  mobileQuery.addEventListener?.('change', () => shell.classList.remove('is-mobile-open'));
})();
