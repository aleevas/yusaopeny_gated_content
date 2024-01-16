/**
 * @file
 * JavaScript for openy_gc_auth_example.
 */
(function ($, Drupal, once) {

  Drupal.behaviors.openy_gc_auth_example = {
    attach: function(context, settings) {
      if ($('.openy-gc-auth-example-login-form').find('.form-submit').attr('data-autosubmit') === '1') {
        $(once('openy_gc_auth_example_form_autosubmit', '.openy-gc-auth-example-login-form', context))
          .trigger( "submit" );
      }
    }
  };

})(jQuery, Drupal, once);
