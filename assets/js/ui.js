/* CharterDesk - shared UI helpers (toasts + loading) */
(function () {
  function container() {
    let c = document.getElementById('toastWrap');
    if (!c) {
      c = document.createElement('div');
      c.id = 'toastWrap';
      c.className = 'toast-wrap';
      document.body.appendChild(c);
    }
    return c;
  }

  // toast(message, type) - type: 'info' | 'success' | 'error'
  window.toast = function (msg, type) {
    const c = container();
    const t = document.createElement('div');
    t.className = 'toast toast-' + (type || 'info');
    t.textContent = String(msg);
    c.appendChild(t);
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
      t.classList.remove('show');
      setTimeout(() => t.remove(), 250);
    }, 3400);
  };

  // Route legacy alert() calls through the toast system (most are warnings/errors).
  window.alert = function (m) { window.toast(m, 'error'); };

  /* ---------- Theme (light/dark) ---------- */
  const THEME_KEY = 'cd_theme';
  if (localStorage.getItem(THEME_KEY) === 'dark') {
    document.documentElement.setAttribute('data-theme', 'dark');
  }
  function isDark(){ return document.documentElement.getAttribute('data-theme') === 'dark'; }
  function syncToggles(){
    document.querySelectorAll('[data-theme-toggle]').forEach(b => { b.textContent = isDark() ? '☀️' : '🌙'; });
  }
  window.toggleTheme = function () {
    if (isDark()) { document.documentElement.removeAttribute('data-theme'); localStorage.setItem(THEME_KEY, 'light'); }
    else { document.documentElement.setAttribute('data-theme', 'dark'); localStorage.setItem(THEME_KEY, 'dark'); }
    syncToggles();
  };
  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-toggle]').forEach(b => { b.onclick = window.toggleTheme; });
    syncToggles();
  });
})();
