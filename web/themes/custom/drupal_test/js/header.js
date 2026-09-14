(function (Drupal, once) {
    'use strict';
  
    Drupal.behaviors.drupalTestHeaderScroll = {
      attach: function (context) {
        once('drupal-test-header-scroll', 'html', context).forEach(function () {
          var header = document.querySelector('.header');
          if (!header) {
            return;
          }
  
          var onScroll = function () {
            if (window.scrollY > 20) {
              header.classList.add('header--scrolled');
            } else {
              header.classList.remove('header--scrolled');
            }
          };
  
          window.addEventListener('scroll', onScroll, { passive: true });
          onScroll();
        });
      },
    };
  })(Drupal, once);