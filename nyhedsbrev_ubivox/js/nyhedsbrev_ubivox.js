(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.nyhedsbrevUbivox = {
    attach: function (context, settings) {

      var allowedAuthors = ['Mikkel Thastum', 'Simon Gosvig', 'Henrik Madsen', 'select_or_other'];

      $('.node-forsite-article-form #edit-field-year option', context).removeAttr('selected');

      $('.node-forsite-article-form #edit-field-year option', context).each(function (index, el) {
        if ($(this).text() == '- None -') {
          $(this).remove();
        }
        if ($(this).text() == (new Date()).getFullYear()) {
          $(this).attr("selected", "selected");
        }
      });

      var getValueSidebarContent = $('.node-forsite-article-edit-form #edit-field-sidebar-content-0-value', context).val();
      if (getValueSidebarContent == '') {
        $('.node-forsite-article-edit-form #edit-field-sidebar-content-wrapper', context).hide();
      }

      $('.node-forsite-article-form #edit-field-sidebar-content-wrapper', context).hide();

      $('#block-seven-content .admin-list li', context).each(function (index, el) {
        if ($(this).find('.label').text() == 'Simpel side') {
          $(this).find('.label').text('Underside');
        }
      });

      $('.node-forsite-article-form #edit-field-author-or-other-select option, .node-forsite-article-edit-form #edit-field-author-or-other-select option', context).filter(function () {
        var val = $(this).val();
        return val !== '' && $.inArray(val, allowedAuthors) === -1;
      }).hide();

      $('.js-form-type-language-select select.form-select option', context).each(function (index, el) {
        if ($(this).val() == 'und' || $(this).val() == 'zxx') {
          $(this).hide();
        }
      });

      $('.js-form-type-select select.form-select option', context).each(function (index, el) {
        if ($(this).val() == 'und' || $(this).val() == 'zxx' || $(this).val() == '***LANGUAGE_site_default***' || $(this).val() == '***LANGUAGE_language_interface***') {
          $(this).hide();
        }
      });

      $('.node-form #edit-langcode-0-value option', context).each(function (index, el) {
        if ($(this).val() == 'und' || $(this).val() == 'zxx') {
          $(this).hide();
        }
      });

      $('.node-form .js-form-type-language-select label', context).text('Sprog');
      $('.node-form .js-form-item-title-0-value label', context).text('Titel');
      // $('.node-form .field--name-field-sidebar-right .paragraphs-dropbutton-wrapper input.field-add-more-submit:not(#edit-field-sidebar-right-1-subform-field-paragraph-type-add-more-add-more-button-videnbasen)').val('Tilføj højrekolonne');
      $('.node-form .field--name-field-sidebar-right .paragraphs-dropbutton-wrapper input#edit-field-sidebar-right-add-more-add-more-button-sidebar-right', context).val('Tilføj højrekolonne');

      function removeAttrSendmail() {
        $('.node-forsite-article-edit-form .field--name-field-dont-send-email-subscriber input, .node-forsite-article-form .field--name-field-dont-send-email-subscriber input', context).prop('checked', false);
      }

      removeAttrSendmail();

      function hidePopup() {
        $('#modal-notification-sendmail').hide();
        document.getElementById("overlay").style.display = "none";
      }

      function showPopup() {
        $('#modal-notification-sendmail').show();
        document.getElementById("overlay").style.display = "block";
      }

      $('.node-forsite-article-edit-form .field--name-field-dont-send-email-subscriber input, .node-forsite-article-form .field--name-field-dont-send-email-subscriber input', context).change(function (event) {
        if ($(this).is(':checked')) {
          showPopup();
        } else {
          hidePopup();
        }
      });

      $('#modal-notification-sendmail .btn-yes').click(function (event) {
        hidePopup();
      });

      $('#modal-notification-sendmail .btn-no').click(function (event) {
        hidePopup();
        removeAttrSendmail();
      });

      $('#modal-notification-sendmail .btn-close').click(function (event) {
        hidePopup();
        removeAttrSendmail();
      });
    }
  };

})(jQuery, Drupal);
