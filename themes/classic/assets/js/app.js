// ApexSMM Classic Theme Client Helpers
(function () {
  window.SMM = {
    getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    },

    async postJson(url, payload) {
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': this.getCsrfToken(),
        },
        body: JSON.stringify(payload || {}),
      });
      return res.json();
    },

    confirmAction(options) {
      const detail = {
        title: (options && options.title) || 'Confirm Action',
        message: (options && options.message) || 'Are you sure you want to continue?',
        actionUrl: (options && options.actionUrl) || window.location.href,
        fields: (options && options.fields) || {},
        confirmLabel: (options && options.confirmLabel) || 'Confirm',
      };
      window.dispatchEvent(new CustomEvent('open-confirm-modal', { detail }));
    },

    copyText(text, btnEl) {
      if (!navigator.clipboard) {
        return;
      }
      navigator.clipboard.writeText(text).then(() => {
        if (btnEl) {
          const orig = btnEl.textContent;
          btnEl.textContent = 'Copied!';
          setTimeout(() => { btnEl.textContent = orig; }, 1600);
        }
        this.toast('Copied to clipboard', 'success');
      });
    },

    toast(message, type = 'info') {
      let container = document.getElementById('smm-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.id = 'smm-toast-container';
        container.className = 'fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm';
        document.body.appendChild(container);
      }
      const colors = {
        success: 'bg-emerald-600 text-white',
        error: 'bg-red-600 text-white',
        info: 'bg-violet-700 text-white',
      };
      const el = document.createElement('div');
      el.className = `px-4 py-3 rounded-xl shadow-lg text-sm font-medium animate-fade-in ${colors[type] || colors.info}`;
      el.textContent = message;
      container.appendChild(el);
      setTimeout(() => el.remove(), 3500);
    },
  };
})();
