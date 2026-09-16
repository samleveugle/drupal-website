(function (Drupal, once) {
  "use strict";

  Drupal.behaviors.drupalTestHeaderScroll = {
    attach: function (context) {
      once("drupal-test-header-scroll", "html", context).forEach(function () {
        var header = document.querySelector(".header");
        if (!header) {
          return;
        }

        var onScroll = function () {
          if (window.scrollY > 20) {
            header.classList.add("header--scrolled");
          } else {
            header.classList.remove("header--scrolled");
          }
        };

        var toggle = header.querySelector(".header__toggle");
        var menu = header.querySelector(".header__menu");
        if (toggle && menu) {
          toggle.addEventListener("click", function () {
            var open = header.classList.toggle("header--open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
          });
        }

        window.addEventListener("scroll", onScroll, { passive: true });
        onScroll();
      });
    },
  };
})(Drupal, once);
