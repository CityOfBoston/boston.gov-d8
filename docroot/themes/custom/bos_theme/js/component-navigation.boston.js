/**
 * @file
 * Tab Functionality.
 *
 * Provides tab functionality for the site.
 * Primarily used on How To pages and Tabbed Content Pages.
 */

(function ($, Drupal, window, document) {

  'use strict';
  if ($('ul.content-section-tabs').length) {
    $(function () {
      // Get hash from URL.
      var hash = window.location.hash;
      if (hash) {
        var elementID = hash.replace('#', '');
        $('div.tab-content[id="' + elementID + '"]').addClass('is-active');
        $('ul.tabs li[data-tab="' + elementID + '"]').addClass('is-active');
      }
      else {
        $('ul.content-section-tabs li').first().addClass('is-active').find('a').addClass('is-active');
        $('.tab-content').first().addClass('is-active');
      }

      if (location.hash) {
        setTimeout(function () {

          window.scrollTo(0, 0);
        }, 1);
      }

    });

    $('ul.tabs li').click(function () {
      var tab_id = $(this).attr('data-tab');

      $('ul.tabs li').removeClass('is-active').find('a').removeClass('is-active');
      $('.tab-content').removeClass('is-active');

      $(this).addClass('is-active').find('a').addClass('is-active');
      $("#" + tab_id).addClass('is-active');
    });
  }

  if ($('ul.content-section-tabs').length) {
    $('ul.content-section-tabs li a').on('click', function (e) {
      e.preventDefault();
    });
  }

})(jQuery, Drupal, this, this.document);

(function ($, Drupal, window, document) {

  'use strict';
  if ($('.topic-nav').length) {
    var list = $('.topic-nav ul');

    // Creates a menu item on any element it is called on.
    $.fn.createNavItem = function () {
      $(this).each(function () {
        var thisTrim = $(this).attr('name');

        if (thisTrim.length > 0) {
          var tagID = thisTrim;
          $(list).append('<li><a class="scroll-link-js" href="#' + tagID + '">' + $(this).data('text') + '</a></li>');
        }
      });
    };

    if ($(".subnav-anchor").length) {
      // Calling createNavItem on short titles.
      $(".subnav-anchor").once('createNavItem').createNavItem();

      // Creates scroll effect on anchor links.
      $('.scroll-link-js').click(function () {
        var navOffset = 75;
        if (!$(this).parents("nav").hasClass("sticky")) {
          navOffset += 115;
        }
        $('html, body').animate({
          scrollTop: $('[name="' + $.attr(this, 'href').substr(1) + '"]').offset().top - navOffset
        }, 1);
      });

      // Fade in/out topic nav when .sub-nav-button link is clicked
      $('.sub-nav-button, .sub-nav-trigger').on('click', function () {
        $(this).toggleClass('open');
        $('.topic-nav').fadeToggle(300);
      });

      // If the menu was faded out on small screens, ensures it fades back in when resized to larger screens.
      $(window).resize(function () {
        if ($(window).width() > 980) {
          $('.topic-nav').fadeIn(300);
        }
      });

      var TopMargin = $('.topic-nav').css('margin-top');
      var MarginOffest = parseInt(TopMargin, 10);
      var stickyNavTop = $('.topic-nav').offset().top - MarginOffest;
      var stickyNav = function () {
        var scrollTop = $(window).scrollTop();
        if (scrollTop > stickyNavTop) {
          $('.topic-nav').addClass('sticky');
          $('.intro-content').addClass('nav-fill-margin');
        }
        else {
          $('.topic-nav').removeClass('sticky');
          $('.intro-content').removeClass('nav-fill-margin');
        }
      };

      var getScrollTop = function () {
        if (typeof pageYOffset!= 'undefined') {
          return pageYOffset;
        }
        else {
          var B= document.body; // IE 'quirks'
          var D= document.documentElement; // IE with doctype
          D= (D.clientHeight)? D: B;
          return D.scrollTop;
        }
      };

      var navLinks = document.querySelectorAll('.scroll-link-js');
      var navLinksArray = Array.prototype.slice.call(navLinks);
      var scrollItems = navLinksArray.map(function (value, index) {
        return navLinks[index].getAttribute('href');
      });

      var removeActiveState = function () {
        var activeLinks = document.querySelectorAll('.scroll-link-js.is-active');

        if (activeLinks.length) {
          for (var el in activeLinks) {
            if (activeLinks[el] && activeLinks[el].classList) {
              activeLinks[el].classList.remove('is-active');
            }
          }
        }
      };

      var activeScrollLink = function () {
        var fromTop = getScrollTop();

        fromTop = fromTop + 100;

        var currentItems = scrollItems.filter(function (item) {
          var name    = item.replace('#', '');
          var items   = document.querySelectorAll('[name=' + name + ']')[0];
          var itemTop = items ? items.getBoundingClientRect().top + fromTop - 100 : 0;

          if (fromTop >= itemTop)
            return item;
        });

        if (currentItems.length) {
          var currentHref = currentItems[currentItems.length - 1];
          var currentItem = document.querySelectorAll('.scroll-link-js[href="' + currentHref + '"]')[0];

          if (!currentItem.classList.contains('is-active')) {
            removeActiveState();
            currentItem.classList.add('is-active');
          }
          else if (!currentItem) {
            removeActiveState();
          }
        } else {
          removeActiveState();
        }
      };

      $(window).scroll(function () {
        activeScrollLink();
        stickyNav();
      });
    }

    if (!$('.topic-nav li').length) {
      $('.topic-nav').remove();
      $('.section-nav-button-container').remove();
      $('.sub-nav-trigger').remove();
    }
  }

})(jQuery, Drupal, this, this.document);
