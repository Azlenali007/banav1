// ApexSMM Classic Theme — User Panel Scripts
function newOrderForm(categories, services, preselectId, currency, initialQuantity) {
  return {
    categories,
    services,
    currency,
    searchQuery: '',
    selectedCategoryId: categories.length ? Number(categories[0].id) : 0,
    selectedServiceId: 0,
    quantity: Number(initialQuantity) > 0 ? Number(initialQuantity) : 100,
    submitting: false,
    init() {
      if (preselectId > 0) {
        const found = this.services.find(s => Number(s.id) === preselectId);
        if (found) {
          this.selectedCategoryId = Number(found.category_id);
          this.selectedServiceId = Number(found.id);
          if (!(Number(initialQuantity) > 0)) {
            this.quantity = Number(found.min_qty);
          }
          return;
        }
      }
      this.onCategoryChange(Number(initialQuantity) > 0);
    },
    get filteredServices() {
      const q = this.searchQuery.trim().toLowerCase();
      if (q !== '') {
        return this.services.filter(s =>
          String(s.id) === q ||
          (s.name || '').toLowerCase().includes(q) ||
          (s.description || '').toLowerCase().includes(q)
        );
      }
      return this.services.filter(s => Number(s.category_id) === Number(this.selectedCategoryId));
    },
    get activeService() {
      return this.services.find(s => Number(s.id) === Number(this.selectedServiceId)) || null;
    },
    get estimatedCharge() {
      if (!this.activeService || !this.quantity || this.quantity <= 0) return '0.00';
      const charge = (Number(this.activeService.effective_rate) * Number(this.quantity)) / 1000;
      if (charge > 0 && charge < 0.01) {
        return charge.toFixed(4);
      }
      return charge.toFixed(2);
    },
    formatRate(rate) {
      const num = Number(rate || 0);
      return num > 0 && num < 0.01 ? num.toFixed(4) : num.toFixed(2);
    },
    filterBySearch() {
      const list = this.filteredServices;
      if (list.length > 0) {
        this.selectedServiceId = Number(list[0].id);
        this.selectedCategoryId = Number(list[0].category_id);
        this.quantity = Number(list[0].min_qty);
      }
    },
    onCategoryChange(preserveQty = false) {
      this.searchQuery = '';
      const list = this.filteredServices;
      if (list.length > 0) {
        this.selectedServiceId = Number(list[0].id);
        if (!preserveQty) {
          this.quantity = Number(list[0].min_qty);
        }
      } else {
        this.selectedServiceId = 0;
      }
    },
    onServiceChange() {
      if (this.activeService) {
        this.quantity = Number(this.activeService.min_qty);
      }
    }
  };
}

function launchRazorpayCheckout(cfg) {
  if (typeof Razorpay === 'undefined') {
    if (window.SMM) {
      window.SMM.toast('Razorpay checkout script failed to load. Check internet connection or ad-blocker.', 'error');
    }
    return;
  }

  var options = {
    key: cfg.keyId,
    amount: cfg.amountSubunits,
    currency: cfg.currency,
    name: cfg.siteName,
    description: 'Wallet Deposit ' + cfg.paymentUid,
    order_id: cfg.razorpayOrderId,
    prefill: {
      name: cfg.customerName,
      email: cfg.customerEmail
    },
    theme: { color: cfg.themeColor || '#7C3AED' },
    modal: {
      ondismiss: function () {
        if (window.SMM) {
          window.SMM.toast('Payment window closed before completion.', 'info');
        }
      }
    },
    handler: function (response) {
      var form = document.createElement('form');
      form.method = 'POST';
      form.action = cfg.callbackUrl;
      var fields = {
        csrf_token: window.SMM ? window.SMM.getCsrfToken() : '',
        razorpay_order_id: response.razorpay_order_id,
        razorpay_payment_id: response.razorpay_payment_id,
        razorpay_signature: response.razorpay_signature
      };
      Object.keys(fields).forEach(function (k) {
        var inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = k;
        inp.value = fields[k];
        form.appendChild(inp);
      });
      document.body.appendChild(form);
      form.submit();
    }
  };

  var rzp = new Razorpay(options);
  rzp.on('payment.failed', function (resp) {
    var msg = (resp && resp.error && resp.error.description) ? resp.error.description : 'Payment failed at gateway.';
    if (window.SMM) {
      window.SMM.toast('Razorpay Error: ' + msg, 'error');
    }
    var box = document.getElementById('rzp-error-box');
    if (box) {
      box.textContent = 'Payment failed: ' + msg;
      box.classList.remove('hidden');
    }
  });
  rzp.open();
}
