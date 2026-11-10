(function () {
  'use strict';
  var copy = window.idguardShopPresentation;
  if (copy && copy.language !== 'da') {
    var nativeAlert = window.alert;
    window.alert = function (message) {
      var args = Array.prototype.slice.call(arguments);
      if (typeof message === 'string' && Object.prototype.hasOwnProperty.call(copy.alerts, message)) args[0] = copy.alerts[message];
      return nativeAlert.apply(window, args);
    };
    function localizePopup() {
      var popup = document.getElementById('idguard-popup');
      if (popup && popup.getAttribute('aria-label') === 'Aldersbekræftelse') popup.setAttribute('aria-label', copy.popupAria);
      var close = document.getElementById('idguard-close');
      if (close && close.getAttribute('aria-label') === 'Luk') close.setAttribute('aria-label', copy.popupClose);
      var loading = document.getElementById('idguard-popup-loading');
      if (loading) Array.prototype.forEach.call(loading.childNodes, function (node) {
        if (node.nodeType === 3 && node.textContent.trim() === 'Vent venligst...') node.textContent = ' ' + copy.popupLoading;
      });
    }
    localizePopup();
    if (document.body && window.MutationObserver) {
      new MutationObserver(localizePopup).observe(document.body, { childList: true, subtree: true });
    }
  }
document.querySelectorAll('.shop-language').forEach(function (menu) {
  menu.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && menu.open) {
      menu.open = false;
      menu.querySelector('summary').focus();
      event.stopPropagation();
    }
  });
  document.addEventListener('click', function (event) {
    if (menu.open && !menu.contains(event.target)) menu.open = false;
  });
});

})();
