/**
 * @file
 * In-page menu functionality.
 *
 * Creates a scrolling, collapsible in-page menu system.
 */

(function ($, Drupal, window, document) {

  'use strict';
  if ($('.topic-nav').length) {

    // Creates a menu item on any element it is called on.
    if ($(".subnav-anchor").length) {

      var navMenu = $('.topic-nav');
      var navMenuHeight = navMenu.outerHeight(true);
      var fixedMenu = $("#main-menu");
      var navTop = navMenu.first("ul").offset().top;

      // Returns the current position of the lower edge of the #main-menu block.
      var menusBottom = function () {
        return fixedMenu.position().top + fixedMenu.height();
      };

      // Collapses and expands the components menu.
      var stickyNav = function () {
        var stickyNavTop = navTop - menusBottom();
        var scrollTop = $("html, body").scrollTop();
        if ($(document).width() >= 980 && scrollTop > stickyNavTop) {
          if (!navMenu.hasClass('sticky')) {
            navMenu
              .addClass('sticky')
              .next().css("margin-top", navMenuHeight);
            navMenuHeight = navMenu.height();
            $('.intro-content').addClass('nav-fill-margin');
          }
          stickyNavTop = navTop - menusBottom();
          navMenu.css("top", menusBottom());
        }
        else if (scrollTop <= stickyNavTop && navMenu.hasClass('sticky')) {
          navMenu
            .removeClass('sticky').css("top", "initial")
            .next().css("margin-top", 0);
          navMenuHeight = navMenu.height();
          $('.intro-content').removeClass('nav-fill-margin');
        }
      };

      // Scroll to clicked anchor, allowing for page furniture.
      var scrollToAnchor = function (obj) {
        var navOffset = menusBottom() - navMenu.outerHeight(true);
        var loc = ($('[name="' + $.attr(obj, 'href').substr(1) + '"]').offset().top - navOffset - 180);
        $("html, body").animate({scrollTop: loc}, 1000, "swing");
      };

      $('.scroll-link-js').click(function (e) {
        e.preventDefault();
        $('.intro-content').addClass('nav-fill-margin');
        scrollToAnchor(this);
      });

      // Fade in/out topic nav when .sub-nav-button link is clicked.
      $('.sub-nav-button, .sub-nav-trigger').on('click', function () {
        $(this).toggleClass('open');
        navMenu.fadeToggle(300);
      });

      // If the menu was faded out on small screens, ensures it fades back in when resized to larger screens.
      $(window).resize(function () {
        if ($(window).width() > 980) {
          navMenu.fadeIn(300);
        }
      });

      var navLinks = document.querySelectorAll('.scroll-link-js');
      var navLinksArray = Array.prototype.slice.call(navLinks);
      var scrollItems = navLinksArray.map(function (value, index) {
        return navLinks[index].getAttribute('href');
      });

      var getScrollTop = function () {
        if (typeof pageYOffset !== 'undefined') {
          return pageYOffset;
        }
        else {
          var B = document.body; // IE 'quirks'.
          var D = document.documentElement; // IE with doctype.
          D = (D.clientHeight) ? D : B;
          return D.scrollTop;
        }
      };

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

        var currentItems = scrollItems.filter(function (item) {
          var name = item.replace('#', '').trim();
          if (name !== "") {
            var items = document.querySelectorAll('[name=' + name + ']')[0];
            var itemTop = items ? items.getBoundingClientRect().top + fromTop : 0;

            if (fromTop >= (itemTop - 300)) {
              return item;
            }
          }
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
        }
        else {
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
