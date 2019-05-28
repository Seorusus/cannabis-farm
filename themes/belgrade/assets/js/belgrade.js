/**
 * @file
 * Belgrade Theme JS.
 */
(function ($) {

    'use strict';

    /**
     * Close behaviour.
     */
    Drupal.behaviors.closeCartblockcontents = {
      attach: function (context, settings) {
        $('.cart-block--contents .close-btn').click(function() {
          $(this).parent().removeClass('cart-block--contents__expanded');
        });
      }
    };

    $(document).ready(function () {
        var $menu = $("#menuF");
        $(window).scroll(function () {
            if ($(this).scrollTop() > 50 && $menu.hasClass("default")) {
                $menu.fadeOut('fast', function () {
                    $(this).removeClass("default")
                        .addClass("fixed transbg")
                        .fadeIn('fast');
                });
            } else if ($(this).scrollTop() <= 100 && $menu.hasClass("fixed")) {
                $menu.fadeOut('fast', function () {
                    $(this).removeClass("fixed transbg")
                        .addClass("default")
                        .fadeIn('fast');
                });
            }
        });
    });
  })(jQuery);
