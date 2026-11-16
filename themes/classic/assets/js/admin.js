// ApexSMM Classic Theme — Admin Panel Scripts
(function () {
  window.SMMAdmin = {
    toggleCheckboxes(masterEl, selector) {
      document.querySelectorAll(selector).forEach(function (el) {
        el.checked = Boolean(masterEl.checked);
      });
    },
  };
})();
