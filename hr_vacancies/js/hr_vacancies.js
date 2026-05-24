/**
 * HR Vacancies iframe auto-height using iframeResizer v3.5.8.
 * hr-manager.net already loads iframeResizer.contentWindow.min.js (v3.5.8).
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.hrVacanciesIframe = {
    attach: function (context, settings) {
      once('hr-vacancies', '.hr-vacancies-iframe', context).forEach(function (iframe) {
        var wrapperId = iframe.getAttribute('data-wrapper-id');
        var spinnerId = 'spinner-' + wrapperId;
        var spinnerRemoved = false;

        function showIframe() {
          if (spinnerRemoved) {
            return;
          }
          spinnerRemoved = true;

          var spinner = document.getElementById(spinnerId);

          if (spinner) {
            spinner.style.opacity = '0';
            spinner.style.transition = 'opacity 0.3s';
            setTimeout(function () {
              if (spinner && spinner.parentNode) {
                spinner.parentNode.removeChild(spinner);
              }
            }, 300);
          }

          iframe.style.opacity = '1';
          iframe.style.transition = 'opacity 0.4s';
        }

        // iframeResizer v3 API.
        if (typeof iFrameResize === 'function') {
          iFrameResize({
            log: false,
            checkOrigin: false,
            heightCalculationMethod: 'bodyOffset',
            sizeHeight: true,
            sizeWidth: false,
            initCallback: function (iframeEl) {
              showIframe();
            },
            resizedCallback: function (data) {
              var wrapper = document.getElementById(wrapperId);
              if (wrapper && data.height) {
                wrapper.style.minHeight = data.height + 'px';
              }
              // Also remove spinner on first resize (backup).
              if (!spinnerRemoved) {
                showIframe();
              }
            },
          }, iframe);
        } else {
          console.warn('[HR Vacancies] iFrameResize not available, using fallback.');
          iframe.addEventListener('load', showIframe);
        }

        // Fallback 1: iframe load event.
        iframe.addEventListener('load', function () {
          setTimeout(showIframe, 500);
        });

        // Fallback 2: force remove spinner after 3s.
        setTimeout(function () {
          if (!spinnerRemoved) {
            showIframe();
          }
        }, 3000);
      });
    }
  };

})(jQuery, Drupal);
