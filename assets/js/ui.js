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
})();
