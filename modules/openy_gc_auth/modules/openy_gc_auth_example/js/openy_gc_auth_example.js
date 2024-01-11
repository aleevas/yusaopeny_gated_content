/**
 * @file
 * JavaScript for openy_gc_auth_example.
 */
(function ($, Drupal, once) {

  Drupal.behaviors.openy_gc_auth_example = {
    attach: function(context, settings) {
      $(once('openy_gc_auth-data-autosubmit', 'input[data-autosubmit=1]'))
        .form().submit();
    }
  };

})(jQuery, Drupal, once);
