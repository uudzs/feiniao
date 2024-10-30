(function ($) {
  'use strict';

  // WOW JS
  new WOW().init();

  $('.slider_list').owlCarousel({
    loop: true,
    autoplay: true,
    autoplayTimeout: 5000,
    dots: false,
    nav: false,
    navText: ["<i class='bi bi-arrow-left''></i>", "<i class='bi bi-arrow-right''></i>"],
    responsive: {
      0: {
        items: 1
      },
      768: {
        items: 1
      },
      992: {
        items: 1
      },
      1000: {
        items: 1
      },
      1920: {
        items: 1
      }
    }
  })

  // profile log out btn
  $('.profile').click(function () {
    $('.dashbord-sub-manu').toggle(300);
  });

  // // Window Loading JS
  $(window).on('load', function () {
  });

})(jQuery);
