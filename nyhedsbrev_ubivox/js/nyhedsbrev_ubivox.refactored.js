/**
 * Refactored version of nyhedsbrev_ubivox.js
 *
 * Improvements over the original:
 *  - Uses Drupal.behaviors instead of jQuery(document).ready() — works with AJAX
 *  - Shared constants for repeated selectors and values
 *  - Author filter uses an array + $.inArray() instead of chained != comparisons
 *  - All three language option loops merged into one
 *  - Bug fix: FALSE → false
 *  - Old jQuery .attr("selected") → .prop('selected', true)
 *  - .change()/.click() → .on('change')/.on('click') (modern jQuery API)
 *  - Popup uses cached jQuery objects instead of repeated DOM lookups
 *  - resetSendmail() replaces the duplicated removeAttrSendmail()
 */
(function ($, Drupal) {
  'use strict';

  // Authors visible in the author dropdown.
  var ALLOWED_AUTHORS = ['Mikkel Thastum', 'Simon Gosvig', 'Henrik Madsen', 'select_or_other'];

  // Language option values to hide across all selects.
  var HIDDEN_LANG_VALUES = ['und', 'zxx', '***LANGUAGE_site_default***', '***LANGUAGE_language_interface***'];

  // Combined selector covering both create and edit article forms.
  var ARTICLE_FORMS = '.node-forsite-article-form, .node-forsite-article-edit-form';

  Drupal.behaviors.nyhedsbrevUbivox = {
    attach: function (context, settings) {

      // --- Year field: remove "- None -", pre-select current year ---
      $(ARTICLE_FORMS, context).find('#edit-field-year option').each(function () {
        var $opt = $(this);
        if ($opt.text() === '- None -') {
          $opt.remove();
        } else if ($opt.text() == (new Date()).getFullYear()) {
          $opt.prop('selected', true);
        }
      });

      // --- Sidebar content: hide wrapper if empty (edit form only) ---
      var $sidebarContent = $('.node-forsite-article-edit-form #edit-field-sidebar-content-0-value', context);
      if ($sidebarContent.val() === '') {
        $('.node-forsite-article-edit-form #edit-field-sidebar-content-wrapper', context).hide();
      }
      $('.node-forsite-article-form #edit-field-sidebar-content-wrapper', context).hide();

      // --- Admin list: rename "Simpel side" → "Underside" ---
      $('#block-seven-content .admin-list li .label', context).filter(function () {
        return $(this).text() === 'Simpel side';
      }).text('Underside');

      // --- Author dropdown: hide options not in the allowed list ---
      $(ARTICLE_FORMS, context).find('#edit-field-author-or-other-select option').filter(function () {
        var val = $(this).val();
        return val !== '' && $.inArray(val, ALLOWED_AUTHORS) === -1;
      }).hide();

      // --- Language selects: hide internal/undefined language options ---
      $('.js-form-type-language-select select.form-select option, ' +
        '.js-form-type-select select.form-select option, ' +
        '.node-form #edit-langcode-0-value option', context
      ).filter(function () {
        return $.inArray($(this).val(), HIDDEN_LANG_VALUES) !== -1;
      }).hide();

      // --- Label overrides ---
      $('.node-form .js-form-type-language-select label', context).text('Sprog');
      $('.node-form .js-form-item-title-0-value label', context).text('Titel');
      $('.node-form #edit-field-sidebar-right-add-more-add-more-button-sidebar-right', context).val('Tilføj højrekolonne');

      // --- Send email popup ---
      var $sendmailCheckbox = $(ARTICLE_FORMS + ' .field--name-field-dont-send-email-subscriber input', context);
      var $modal            = $('#modal-notification-sendmail', context);
      var $overlay          = $('#overlay', context);

      function resetSendmail() {
        $sendmailCheckbox.prop('checked', false);
      }

      function hidePopup() {
        $modal.hide();
        $overlay.hide();
      }

      function showPopup() {
        $modal.show();
        $overlay.show();
      }

      resetSendmail();

      $sendmailCheckbox.on('change', function () {
        $(this).is(':checked') ? showPopup() : hidePopup();
      });

      $modal.find('.btn-yes').on('click', hidePopup);

      $modal.find('.btn-no, .btn-close').on('click', function () {
        hidePopup();
        resetSendmail();
      });
    }
  };

})(jQuery, Drupal);

