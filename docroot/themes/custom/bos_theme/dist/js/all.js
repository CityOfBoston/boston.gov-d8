/**
 * @file
 * A JavaScript file for the theme.
 *
 * In order for this JavaScript to be loaded on pages, see the instructions in
 * the README.txt next to this file.
 */

// JavaScript should be made compatible with libraries other than jQuery by
// wrapping it with an "anonymous closure". See:
// - https://drupal.org/node/1446420
// - http://www.adequatelygood.com/2010/3/JavaScript-Module-Pattern-In-Depth
(function ($, Drupal, window, document) {
  'use strict';
   var livestreams = document.getElementsByClassName('live-stream'),
     n = livestreams.length;
   while(n--) {
     livestreams[n].innerHTML = "Watch it live";
   }
})(jQuery, Drupal, this, this.document);

/**
 * @file
 * Drawer behaviors.
 *
 * Adds functionality for drawers across the site.
 */

(function ($, Drupal, window, document) {

  'use strict';

  if ($('.drawer').length) {
    $('.drawer-trigger').click(function () {
      // If .draw-trigger is clicked and has the class .active, remove active and slide up (close) the closest .drawer.
      if ($(this).hasClass('active')) {
        $(this).toggleClass('active').next('.active').toggleClass('active').slideUp(400);
        $('#query').blur();
      }
      // Otherwise add  the class .active and slide down (open) the closest .drawer.
      else {
        $(this).toggleClass('active').next('.drawer').toggleClass('active').slideDown(400);
        // And remove .active class from all other drawers and drawer-treggers.
        $(this).parent('.drawer-wrapper').siblings('.drawer-wrapper').children('.drawer').removeClass('active').slideUp(400);
        $(this).parent('.drawer-wrapper').siblings('.drawer-wrapper').children('.drawer-trigger').removeClass('active');

        if ($(this).hasClass('search-trigger')) {
          $('#query').focus();
          $('input[name=search_block_form]').focus();
        }
      }
    });
    // Drawer close button removes active state from .drawer and drawer-trigger and toggles the drawer shut.
    $('.drawer-close-button').click(function () {
      $(this).closest('.drawer.active').toggleClass('active').slideUp(400).prev('.drawer-trigger.active').toggleClass('active');
      $('#query').blur();
    });
  }

  if ($('.drawer.mobile-only').length) {
    $(window).resize(function () {
      // Resets filterdrawers when window is resized.
      $('.active').removeClass('active');
      // If window is resized to more than 980px, display: none is removed from
      // style attribute so exposed filters to remain hidden from mobile states.
      if ($(window).width() > 980) {
        $('.drawer.mobile-only').css('display', '');
      }
    });
  }

  // Expand drawers so users can search within them easily.
  $(document).on("keydown", function (e) {
    // Check for CTRL+f input.
    if (e.keyCode == 70 && (e.ctrlKey || e.metaKey)) {
      // Loop through each label that controls a hidden checkbox.
      $('.dr-h').each(function() {
        // Only execute on drawers that are closed.
        if (!$(this).hasClass('expanded')) {
          // Simulate a click on the drawer label to open it.
          $(this).click();
          // Add a class so we know this drawer is now open.
          $(this).addClass('expanded');
        }
      });
    }
    // Check for ESC input.
    if (e.keyCode == 27) {
      // Loop through each label that controls a hidden checkbox.
      $('.dr-h').each(function() {
        // Only execute on drawers that are open.
        if ($(this).hasClass('expanded')) {
          // Simulate a click on the drawer label to close it.
          $(this).click();
          // Remove a class so we know this drawer is now closed.
          $(this).removeClass('expanded');
        }
      });
    }
  });
  // Check for clicks outside of browser search.
  $('.dr-h').click(function() {
    // Check if the clicked drawer is already open.
    if ($(this).hasClass('expanded')) {
      // Remove the class so we know this drawer was closed manually.
      $(this).removeClass('expanded');
    // If the drawer is closed already.
    } else {
      // Add a class so we know if was just opened.
      $(this).addClass('expanded');
    }
  });

  // Add tabindex to drawer-triggers
  if ($('.drawer-trigger').length) {
    $('.drawer-wrapper div.drawer-trigger').attr("tabindex","0"); //need to remove this line, but cannot find the page to test it out yet.
    $('div.drawer-trigger').attr("tabindex","0");
    $('.drawer-trigger').keydown(function (e) {
      if (e.keyCode == 13) { // enter key
        e.preventDefault();
        this.click();
      }
    });
  }

})(jQuery, Drupal, this, this.document);

(function ($, Drupal, window, document) {

  'use strict';

  if ($('.dr').length) {
    // Only hide if JS is working
    $('.dr__content').hide().attr('aria-hidden', true);

    // Add show/hide to all drawer headers
    $('.dr__header').click(function () {
      var el       = $(this);
      var isActive = el.hasClass('dr__header--active');
      var content  = el.siblings('.dr__content');

      el.toggleClass('dr__header--active');
      if (isActive) {
        content.slideUp().attr('aria-hidden', true);
      } else {
        content.slideDown().attr('aria-hidden', false);
      }
    });
  }

})(jQuery, Drupal, this, this.document);

/**
 * @file
 * Featured Item behaviors.
 *
 * Adds height control for image column in feauted items.
 * Currently used for header items: featured events and featured posts.
 */

(function ($, Drupal, window, document) {

  'use strict';

  $(window).on('load resize', function () {
    if ($(window).width() > 980) {
      var colHeight = $('.featured-item-details').outerHeight();
      $('.featured-item-thumb').css('height', colHeight + 'px');
    }
  });

})(jQuery, Drupal, this, this.document);

/**
 * @file
 * Polls functionality.
 *
 * Provides functionality for polls and renders circle graphs.
 */

(function ($, Drupal, window, document) {

  'use strict';
  window.circle_count = 0;

  // If a vote button is clickd, check the associated radio button and
  // trigger the change event on it.
  $(document).on('click', '.vote-button', function () {
    var text = $(this).prev('.choice_text').text();
    $('label:contains("' + text + '")').prev('input[type=radio]').prop("checked", true).change();
  });

  // Causes forms to auto submit when radio button is selected.
  $(document).on('change', 'input[type=radio]', function () {
    var form = $(this).closest('form');
    $('input[type=submit]', form).mousedown();
  });

  if ($('.node-advpoll').length || $('.paragraphs-item-feedback').length) {
    Drupal.behaviors.renderRawPoll = {
      attach: function (context, settings) {
        // Iterate through each poll stub on the page and replace it with
        // an actual form.
        $('.poll-stub').once('poll-stub').each(function () {
          var nid = $(this).data('poll-id');
          // Grab a different endpoint depending on whether or not the user has
          // already voted. This is to account for caching.
          var element_settings = {
            url: window.location.protocol + '//' + window.location.hostname + settings.basePath + settings.pathPrefix + 'ajax/poll/' + nid,
            event: 'click',
            progress: {
              type: 'throbber'
            }
          };
          $.ajax(element_settings.url)
            .done(function (result) {
              result = JSON.parse(result);
              $(result.selector).html(result.html);
              Drupal.attachBehaviors(context, settings);
            });
        });
      }
    };

    /* global circle_count:true Circles */

    Drupal.behaviors.pollConvertForm = {
      attach: function (context, settings) {
        $.fn.createBosCircle = function () {
          $(this).each(function () {
            var circle_id = 'circle-container-' + circle_count++;
            $(this).attr('id', circle_id);
            var percent = $(this).data('percent');
            var text = $(this).data('text');
            Circles.create({
              id: circle_id,
              radius: 66,
              value: percent,
              maxValue: 100,
              width: 12,
              text: percent + '%',
              colors: ['#ececec', '#091f2f'],
              duration: 200,
              wrpClass: 'circles-wrp',
              textClass: 'circles-text',
              valueStrokeClass: 'circles-valueStroke',
              maxValueStrokeClass: 'circles-maxValueStroke',
              styleWrapper: true,
              styleText: false
            });
            $('#' + circle_id).find('.circles-wrp').after('<div class="choice_text">' + text + '</div>');
          });
        };

        $('div.circles_container').once('circle-generate').createBosCircle();

        if (Drupal.settings.bos_content_type_advpoll !== null) {
          var pollOptions = Drupal.settings.bos_content_type_advpoll;
          pollOptions.forEach(function (myOptions) {
            // Make sure we check against any polls on this page and see if we have their cookie.
            var regex = new RegExp("(advpoll" + myOptions.poll_options.poll_id + "=vote)", "g");
            // Creates the voting button under each circle that is in a poll that the user can vote on
            // and triggers the radio button with the same text when clicked.
            $('#advpoll-form-' + myOptions.poll_options.poll_id + ' .circles_container').each(function () {
              if (!document.cookie.match(regex)) {
                // Use the vote_button_text from the poll node to fill in the button.
                $(this).once('vote-button-add').append('<button class="vote-button button-sm" type="button">' + myOptions.poll_options.vote_button_text + '</button>');
              }
            });
          });
        }
      }
    };
  }
})(jQuery, Drupal, this, this.document);

(function ($, Drupal, window, document) {

  'use strict';

  Drupal.behaviors.photoComponent = {
    attach: function (context, settings) {
      $('.paragraphs-item-photo').each(function () {
        if ($(this).find('.photo-details').length > 0) {
          $(this).addClass('with-details');
        }
        else {
          $(this).addClass('without-details');
        }
      });
    }
  };

})(jQuery, Drupal, this, this.document);

(function ($, Drupal, window, document) {

  'use strict';

  var fadeSpeed = 100;
  $('.translate-trigger').on ('click', function (e) {
    e.preventDefault();
    $('.popover').fadeToggle(fadeSpeed);
  });
  $(document).click(function (e) {
    e.stopPropagation();
    if ($(e.target).is('.popover, .translate-trigger'))  return false;
    else $('.popover').fadeOut(fadeSpeed);
  });

})(jQuery, Drupal, this, this.document);

/**
 * @file
 * Tabbed Pages.
 *
 * Moves breadcrumbs to article element on pages that use tab functionality.
 * How To and Tabbed Content Node Types.
 */

(function ($, Drupal, window, document) {

  'use strict';
  if ($('.node-type-how-to').length || $('.node-type-tabbed-content').length) {
    $('#breadcrumb').prependTo('article');
  }

})(jQuery, Drupal, this, this.document);

/*!
 * modernizr v3.3.1
 * Build http://modernizr.com/download?-flexbox-setclasses-dontmin
 *
 * Copyright (c)
 *  Faruk Ates
 *  Paul Irish
 *  Alex Sexton
 *  Ryan Seddon
 *  Patrick Kettner
 *  Stu Cox
 *  Richard Herrera

 * MIT License
 */

/*
 * Modernizr tests which native CSS3 and HTML5 features are available in the
 * current UA and makes the results available to you in two ways: as properties on
 * a global `Modernizr` object, and as classes on the `<html>` element. This
 * information allows you to progressively enhance your pages with a granular level
 * of control over the experience.
 */

;(function(window, document, undefined){
  var classes = [];


  var tests = [];


  /**
   *
   * ModernizrProto is the constructor for Modernizr
   *
   * @class
   * @access public
   */

  var ModernizrProto = {
    // The current version, dummy
    _version: '3.3.1',

    // Any settings that don't work as separate modules
    // can go in here as configuration.
    _config: {
      'classPrefix': '',
      'enableClasses': true,
      'enableJSClass': true,
      'usePrefixes': true
    },

    // Queue of tests
    _q: [],

    // Stub these for people who are listening
    on: function(test, cb) {
      // I don't really think people should do this, but we can
      // safe guard it a bit.
      // -- NOTE:: this gets WAY overridden in src/addTest for actual async tests.
      // This is in case people listen to synchronous tests. I would leave it out,
      // but the code to *disallow* sync tests in the real version of this
      // function is actually larger than this.
      var self = this;
      setTimeout(function() {
        cb(self[test]);
      }, 0);
    },

    addTest: function(name, fn, options) {
      tests.push({name: name, fn: fn, options: options});
    },

    addAsyncTest: function(fn) {
      tests.push({name: null, fn: fn});
    }
  };



  // Fake some of Object.create so we can force non test results to be non "own" properties.
  var Modernizr = function() {};
  Modernizr.prototype = ModernizrProto;

  // Leak modernizr globally when you `require` it rather than force it here.
  // Overwrite name so constructor name is nicer :D
  Modernizr = new Modernizr();



  /**
   * is returns a boolean if the typeof an obj is exactly type.
   *
   * @access private
   * @function is
   * @param {*} obj - A thing we want to check the type of
   * @param {string} type - A string to compare the typeof against
   * @returns {boolean}
   */

  function is(obj, type) {
    return typeof obj === type;
  }
  ;

  /**
   * Run through all tests and detect their support in the current UA.
   *
   * @access private
   */

  function testRunner() {
    var featureNames;
    var feature;
    var aliasIdx;
    var result;
    var nameIdx;
    var featureName;
    var featureNameSplit;

    for (var featureIdx in tests) {
      if (tests.hasOwnProperty(featureIdx)) {
        featureNames = [];
        feature = tests[featureIdx];
        // run the test, throw the return value into the Modernizr,
        // then based on that boolean, define an appropriate className
        // and push it into an array of classes we'll join later.
        //
        // If there is no name, it's an 'async' test that is run,
        // but not directly added to the object. That should
        // be done with a post-run addTest call.
        if (feature.name) {
          featureNames.push(feature.name.toLowerCase());

          if (feature.options && feature.options.aliases && feature.options.aliases.length) {
            // Add all the aliases into the names list
            for (aliasIdx = 0; aliasIdx < feature.options.aliases.length; aliasIdx++) {
              featureNames.push(feature.options.aliases[aliasIdx].toLowerCase());
            }
          }
        }

        // Run the test, or use the raw value if it's not a function
        result = is(feature.fn, 'function') ? feature.fn() : feature.fn;


        // Set each of the names on the Modernizr object
        for (nameIdx = 0; nameIdx < featureNames.length; nameIdx++) {
          featureName = featureNames[nameIdx];
          // Support dot properties as sub tests. We don't do checking to make sure
          // that the implied parent tests have been added. You must call them in
          // order (either in the test, or make the parent test a dependency).
          //
          // Cap it to TWO to make the logic simple and because who needs that kind of subtesting
          // hashtag famous last words
          featureNameSplit = featureName.split('.');

          if (featureNameSplit.length === 1) {
            Modernizr[featureNameSplit[0]] = result;
          } else {
            // cast to a Boolean, if not one already
            /* jshint -W053 */
            if (Modernizr[featureNameSplit[0]] && !(Modernizr[featureNameSplit[0]] instanceof Boolean)) {
              Modernizr[featureNameSplit[0]] = new Boolean(Modernizr[featureNameSplit[0]]);
            }

            Modernizr[featureNameSplit[0]][featureNameSplit[1]] = result;
          }

          classes.push((result ? '' : 'no-') + featureNameSplit.join('-'));
        }
      }
    }
  }
  ;

  /**
   * docElement is a convenience wrapper to grab the root element of the document
   *
   * @access private
   * @returns {HTMLElement|SVGElement} The root element of the document
   */

  var docElement = document.documentElement;


  /**
   * A convenience helper to check if the document we are running in is an SVG document
   *
   * @access private
   * @returns {boolean}
   */

  var isSVG = docElement.nodeName.toLowerCase() === 'svg';


  /**
   * setClasses takes an array of class names and adds them to the root element
   *
   * @access private
   * @function setClasses
   * @param {string[]} classes - Array of class names
   */

  // Pass in an and array of class names, e.g.:
  //  ['no-webp', 'borderradius', ...]
  function setClasses(classes) {
    var className = docElement.className;
    var classPrefix = Modernizr._config.classPrefix || '';

    if (isSVG) {
      className = className.baseVal;
    }

    // Change `no-js` to `js` (independently of the `enableClasses` option)
    // Handle classPrefix on this too
    if (Modernizr._config.enableJSClass) {
      var reJS = new RegExp('(^|\\s)' + classPrefix + 'no-js(\\s|$)');
      className = className.replace(reJS, '$1' + classPrefix + 'js$2');
    }

    if (Modernizr._config.enableClasses) {
      // Add the new classes
      className += ' ' + classPrefix + classes.join(' ' + classPrefix);
      isSVG ? docElement.className.baseVal = className : docElement.className = className;
    }

  }

  ;


  /**
   * contains checks to see if a string contains another string
   *
   * @access private
   * @function contains
   * @param {string} str - The string we want to check for substrings
   * @param {string} substr - The substring we want to search the first string for
   * @returns {boolean}
   */

  function contains(str, substr) {
    return !!~('' + str).indexOf(substr);
  }

  ;

  /**
   * createElement is a convenience wrapper around document.createElement. Since we
   * use createElement all over the place, this allows for (slightly) smaller code
   * as well as abstracting away issues with creating elements in contexts other than
   * HTML documents (e.g. SVG documents).
   *
   * @access private
   * @function createElement
   * @returns {HTMLElement|SVGElement} An HTML or SVG element
   */

  function createElement() {
    if (typeof document.createElement !== 'function') {
      // This is the case in IE7, where the type of createElement is "object".
      // For this reason, we cannot call apply() as Object is not a Function.
      return document.createElement(arguments[0]);
    } else if (isSVG) {
      return document.createElementNS.call(document, 'http://www.w3.org/2000/svg', arguments[0]);
    } else {
      return document.createElement.apply(document, arguments);
    }
  }

  ;

  /**
   * cssToDOM takes a kebab-case string and converts it to camelCase
   * e.g. box-sizing -> boxSizing
   *
   * @access private
   * @function cssToDOM
   * @param {string} name - String name of kebab-case prop we want to convert
   * @returns {string} The camelCase version of the supplied name
   */

  function cssToDOM(name) {
    return name.replace(/([a-z])-([a-z])/g, function(str, m1, m2) {
      return m1 + m2.toUpperCase();
    }).replace(/^-/, '');
  }
  ;

  /**
   * If the browsers follow the spec, then they would expose vendor-specific style as:
   *   elem.style.WebkitBorderRadius
   * instead of something like the following, which would be technically incorrect:
   *   elem.style.webkitBorderRadius

   * Webkit ghosts their properties in lowercase but Opera & Moz do not.
   * Microsoft uses a lowercase `ms` instead of the correct `Ms` in IE8+
   *   erik.eae.net/archives/2008/03/10/21.48.10/

   * More here: github.com/Modernizr/Modernizr/issues/issue/21
   *
   * @access private
   * @returns {string} The string representing the vendor-specific style properties
   */

  var omPrefixes = 'Moz O ms Webkit';


  /**
   * List of JavaScript DOM values used for tests
   *
   * @memberof Modernizr
   * @name Modernizr._domPrefixes
   * @optionName Modernizr._domPrefixes
   * @optionProp domPrefixes
   * @access public
   * @example
   *
   * Modernizr._domPrefixes is exactly the same as [_prefixes](#modernizr-_prefixes), but rather
   * than kebab-case properties, all properties are their Capitalized variant
   *
   * ```js
   * Modernizr._domPrefixes === [ "Moz", "O", "ms", "Webkit" ];
   * ```
   */

  var domPrefixes = (ModernizrProto._config.usePrefixes ? omPrefixes.toLowerCase().split(' ') : []);
  ModernizrProto._domPrefixes = domPrefixes;


  var cssomPrefixes = (ModernizrProto._config.usePrefixes ? omPrefixes.split(' ') : []);
  ModernizrProto._cssomPrefixes = cssomPrefixes;


  /**
   * fnBind is a super small [bind](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Function/bind) polyfill.
   *
   * @access private
   * @function fnBind
   * @param {function} fn - a function you want to change `this` reference to
   * @param {object} that - the `this` you want to call the function with
   * @returns {function} The wrapped version of the supplied function
   */

  function fnBind(fn, that) {
    return function() {
      return fn.apply(that, arguments);
    };
  }

  ;

  /**
   * testDOMProps is a generic DOM property test; if a browser supports
   *   a certain property, it won't return undefined for it.
   *
   * @access private
   * @function testDOMProps
   * @param {array.<string>} props - An array of properties to test for
   * @param {object} obj - An object or Element you want to use to test the parameters again
   * @param {boolean|object} elem - An Element to bind the property lookup again. Use `false` to prevent the check
   */
  function testDOMProps(props, obj, elem) {
    var item;

    for (var i in props) {
      if (props[i] in obj) {

        // return the property name as a string
        if (elem === false) {
          return props[i];
        }

        item = obj[props[i]];

        // let's bind a function
        if (is(item, 'function')) {
          // bind to obj unless overriden
          return fnBind(item, elem || obj);
        }

        // return the unbound function or obj or value
        return item;
      }
    }
    return false;
  }

  ;

  /**
   * Create our "modernizr" element that we do most feature tests on.
   *
   * @access private
   */

  var modElem = {
    elem: createElement('modernizr')
  };

  // Clean up this element
  Modernizr._q.push(function() {
    delete modElem.elem;
  });



  var mStyle = {
    style: modElem.elem.style
  };

  // kill ref for gc, must happen before mod.elem is removed, so we unshift on to
  // the front of the queue.
  Modernizr._q.unshift(function() {
    delete mStyle.style;
  });



  /**
   * domToCSS takes a camelCase string and converts it to kebab-case
   * e.g. boxSizing -> box-sizing
   *
   * @access private
   * @function domToCSS
   * @param {string} name - String name of camelCase prop we want to convert
   * @returns {string} The kebab-case version of the supplied name
   */

  function domToCSS(name) {
    return name.replace(/([A-Z])/g, function(str, m1) {
      return '-' + m1.toLowerCase();
    }).replace(/^ms-/, '-ms-');
  }
  ;

  /**
   * getBody returns the body of a document, or an element that can stand in for
   * the body if a real body does not exist
   *
   * @access private
   * @function getBody
   * @returns {HTMLElement|SVGElement} Returns the real body of a document, or an
   * artificially created element that stands in for the body
   */

  function getBody() {
    // After page load injecting a fake body doesn't work so check if body exists
    var body = document.body;

    if (!body) {
      // Can't use the real body create a fake one.
      body = createElement(isSVG ? 'svg' : 'body');
      body.fake = true;
    }

    return body;
  }

  ;

  /**
   * injectElementWithStyles injects an element with style element and some CSS rules
   *
   * @access private
   * @function injectElementWithStyles
   * @param {string} rule - String representing a css rule
   * @param {function} callback - A function that is used to test the injected element
   * @param {number} [nodes] - An integer representing the number of additional nodes you want injected
   * @param {string[]} [testnames] - An array of strings that are used as ids for the additional nodes
   * @returns {boolean}
   */

  function injectElementWithStyles(rule, callback, nodes, testnames) {
    var mod = 'modernizr';
    var style;
    var ret;
    var node;
    var docOverflow;
    var div = createElement('div');
    var body = getBody();

    if (parseInt(nodes, 10)) {
      // In order not to give false positives we create a node for each test
      // This also allows the method to scale for unspecified uses
      while (nodes--) {
        node = createElement('div');
        node.id = testnames ? testnames[nodes] : mod + (nodes + 1);
        div.appendChild(node);
      }
    }

    style = createElement('style');
    style.type = 'text/css';
    style.id = 's' + mod;

    // IE6 will false positive on some tests due to the style element inside the test div somehow interfering offsetHeight, so insert it into body or fakebody.
    // Opera will act all quirky when injecting elements in documentElement when page is served as xml, needs fakebody too. #270
    (!body.fake ? div : body).appendChild(style);
    body.appendChild(div);

    if (style.styleSheet) {
      style.styleSheet.cssText = rule;
    } else {
      style.appendChild(document.createTextNode(rule));
    }
    div.id = mod;

    if (body.fake) {
      //avoid crashing IE8, if background image is used
      body.style.background = '';
      //Safari 5.13/5.1.4 OSX stops loading if ::-webkit-scrollbar is used and scrollbars are visible
      body.style.overflow = 'hidden';
      docOverflow = docElement.style.overflow;
      docElement.style.overflow = 'hidden';
      docElement.appendChild(body);
    }

    ret = callback(div, rule);
    // If this is done after page load we don't want to remove the body so check if body exists
    if (body.fake) {
      body.parentNode.removeChild(body);
      docElement.style.overflow = docOverflow;
      // Trigger layout so kinetic scrolling isn't disabled in iOS6+
      docElement.offsetHeight;
    } else {
      div.parentNode.removeChild(div);
    }

    return !!ret;

  }

  ;

  /**
   * nativeTestProps allows for us to use native feature detection functionality if available.
   * some prefixed form, or false, in the case of an unsupported rule
   *
   * @access private
   * @function nativeTestProps
   * @param {array} props - An array of property names
   * @param {string} value - A string representing the value we want to check via @supports
   * @returns {boolean|undefined} A boolean when @supports exists, undefined otherwise
   */

  // Accepts a list of property names and a single value
  // Returns `undefined` if native detection not available
  function nativeTestProps(props, value) {
    var i = props.length;
    // Start with the JS API: http://www.w3.org/TR/css3-conditional/#the-css-interface
    if ('CSS' in window && 'supports' in window.CSS) {
      // Try every prefixed variant of the property
      while (i--) {
        if (window.CSS.supports(domToCSS(props[i]), value)) {
          return true;
        }
      }
      return false;
    }
    // Otherwise fall back to at-rule (for Opera 12.x)
    else if ('CSSSupportsRule' in window) {
      // Build a condition string for every prefixed variant
      var conditionText = [];
      while (i--) {
        conditionText.push('(' + domToCSS(props[i]) + ':' + value + ')');
      }
      conditionText = conditionText.join(' or ');
      return injectElementWithStyles('@supports (' + conditionText + ') { #modernizr { position: absolute; } }', function(node) {
        return getComputedStyle(node, null).position == 'absolute';
      });
    }
    return undefined;
  }
  ;

  // testProps is a generic CSS / DOM property test.

  // In testing support for a given CSS property, it's legit to test:
  //    `elem.style[styleName] !== undefined`
  // If the property is supported it will return an empty string,
  // if unsupported it will return undefined.

  // We'll take advantage of this quick test and skip setting a style
  // on our modernizr element, but instead just testing undefined vs
  // empty string.

  // Property names can be provided in either camelCase or kebab-case.

  function testProps(props, prefixed, value, skipValueTest) {
    skipValueTest = is(skipValueTest, 'undefined') ? false : skipValueTest;

    // Try native detect first
    if (!is(value, 'undefined')) {
      var result = nativeTestProps(props, value);
      if (!is(result, 'undefined')) {
        return result;
      }
    }

    // Otherwise do it properly
    var afterInit, i, propsLength, prop, before;

    // If we don't have a style element, that means we're running async or after
    // the core tests, so we'll need to create our own elements to use

    // inside of an SVG element, in certain browsers, the `style` element is only
    // defined for valid tags. Therefore, if `modernizr` does not have one, we
    // fall back to a less used element and hope for the best.
    var elems = ['modernizr', 'tspan'];
    while (!mStyle.style) {
      afterInit = true;
      mStyle.modElem = createElement(elems.shift());
      mStyle.style = mStyle.modElem.style;
    }

    // Delete the objects if we created them.
    function cleanElems() {
      if (afterInit) {
        delete mStyle.style;
        delete mStyle.modElem;
      }
    }

    propsLength = props.length;
    for (i = 0; i < propsLength; i++) {
      prop = props[i];
      before = mStyle.style[prop];

      if (contains(prop, '-')) {
        prop = cssToDOM(prop);
      }

      if (mStyle.style[prop] !== undefined) {

        // If value to test has been passed in, do a set-and-check test.
        // 0 (integer) is a valid property value, so check that `value` isn't
        // undefined, rather than just checking it's truthy.
        if (!skipValueTest && !is(value, 'undefined')) {

          // Needs a try catch block because of old IE. This is slow, but will
          // be avoided in most cases because `skipValueTest` will be used.
          try {
            mStyle.style[prop] = value;
          } catch (e) {}

          // If the property value has changed, we assume the value used is
          // supported. If `value` is empty string, it'll fail here (because
          // it hasn't changed), which matches how browsers have implemented
          // CSS.supports()
          if (mStyle.style[prop] != before) {
            cleanElems();
            return prefixed == 'pfx' ? prop : true;
          }
        }
        // Otherwise just return true, or the property name if this is a
        // `prefixed()` call
        else {
          cleanElems();
          return prefixed == 'pfx' ? prop : true;
        }
      }
    }
    cleanElems();
    return false;
  }

  ;

  /**
   * testPropsAll tests a list of DOM properties we want to check against.
   * We specify literally ALL possible (known and/or likely) properties on
   * the element including the non-vendor prefixed one, for forward-
   * compatibility.
   *
   * @access private
   * @function testPropsAll
   * @param {string} prop - A string of the property to test for
   * @param {string|object} [prefixed] - An object to check the prefixed properties on. Use a string to skip
   * @param {HTMLElement|SVGElement} [elem] - An element used to test the property and value against
   * @param {string} [value] - A string of a css value
   * @param {boolean} [skipValueTest] - An boolean representing if you want to test if value sticks when set
   */
  function testPropsAll(prop, prefixed, elem, value, skipValueTest) {

    var ucProp = prop.charAt(0).toUpperCase() + prop.slice(1),
      props = (prop + ' ' + cssomPrefixes.join(ucProp + ' ') + ucProp).split(' ');

    // did they call .prefixed('boxSizing') or are we just testing a prop?
    if (is(prefixed, 'string') || is(prefixed, 'undefined')) {
      return testProps(props, prefixed, value, skipValueTest);

      // otherwise, they called .prefixed('requestAnimationFrame', window[, elem])
    } else {
      props = (prop + ' ' + (domPrefixes).join(ucProp + ' ') + ucProp).split(' ');
      return testDOMProps(props, prefixed, elem);
    }
  }

  // Modernizr.testAllProps() investigates whether a given style property,
  // or any of its vendor-prefixed variants, is recognized
  //
  // Note that the property names must be provided in the camelCase variant.
  // Modernizr.testAllProps('boxSizing')
  ModernizrProto.testAllProps = testPropsAll;



  /**
   * testAllProps determines whether a given CSS property is supported in the browser
   *
   * @memberof Modernizr
   * @name Modernizr.testAllProps
   * @optionName Modernizr.testAllProps()
   * @optionProp testAllProps
   * @access public
   * @function testAllProps
   * @param {string} prop - String naming the property to test (either camelCase or kebab-case)
   * @param {string} [value] - String of the value to test
   * @param {boolean} [skipValueTest=false] - Whether to skip testing that the value is supported when using non-native detection
   * @example
   *
   * testAllProps determines whether a given CSS property, in some prefixed form,
   * is supported by the browser.
   *
   * ```js
   * testAllProps('boxSizing')  // true
   * ```
   *
   * It can optionally be given a CSS value in string form to test if a property
   * value is valid
   *
   * ```js
   * testAllProps('display', 'block') // true
   * testAllProps('display', 'penguin') // false
   * ```
   *
   * A boolean can be passed as a third parameter to skip the value check when
   * native detection (@supports) isn't available.
   *
   * ```js
   * testAllProps('shapeOutside', 'content-box', true);
   * ```
   */

  function testAllProps(prop, value, skipValueTest) {
    return testPropsAll(prop, undefined, undefined, value, skipValueTest);
  }
  ModernizrProto.testAllProps = testAllProps;

  /*!
   {
   "name": "Flexbox",
   "property": "flexbox",
   "caniuse": "flexbox",
   "tags": ["css"],
   "notes": [{
   "name": "The _new_ flexbox",
   "href": "http://dev.w3.org/csswg/css3-flexbox"
   }],
   "warnings": [
   "A `true` result for this detect does not imply that the `flex-wrap` property is supported; see the `flexwrap` detect."
   ]
   }
   !*/
  /* DOC
   Detects support for the Flexible Box Layout model, a.k.a. Flexbox, which allows easy manipulation of layout order and sizing within a container.
   */

  Modernizr.addTest('flexbox', testAllProps('flexBasis', '1px', true));


  // Run each test
  testRunner();

  // Remove the "no-js" class if it exists
  setClasses(classes);

  delete ModernizrProto.addTest;
  delete ModernizrProto.addAsyncTest;

  // Run the things that are supposed to run after the tests
  for (var i = 0; i < Modernizr._q.length; i++) {
    Modernizr._q[i]();
  }

  // Leak Modernizr namespace
  window.Modernizr = Modernizr;


  ;

})(window, document);

(function ($, window, document) {
  if (Modernizr !== undefined) {
    if (Modernizr.flexbox !== true) {
      $('.grid-card').addClass('no-flex');
    }
  }
})(jQuery, this, this.document);

/* MediaMatch v.2.0.2 - Testing css media queries in Javascript. Authors & copyright (c) 2013: WebLinc, David Knight. */

window.matchMedia||(window.matchMedia=function(c){var a=c.document,w=a.documentElement,l=[],t=0,x="",h={},G=/\s*(only|not)?\s*(screen|print|[a-z\-]+)\s*(and)?\s*/i,H=/^\s*\(\s*(-[a-z]+-)?(min-|max-)?([a-z\-]+)\s*(:?\s*([0-9]+(\.[0-9]+)?|portrait|landscape)(px|em|dppx|dpcm|rem|%|in|cm|mm|ex|pt|pc|\/([0-9]+(\.[0-9]+)?))?)?\s*\)\s*$/,y=0,A=function(b){var z=-1!==b.indexOf(",")&&b.split(",")||[b],e=z.length-1,j=e,g=null,d=null,c="",a=0,l=!1,m="",f="",g=null,d=0,f=null,k="",p="",q="",n="",r="",k=!1;if(""===
b)return!0;do{g=z[j-e];l=!1;if(d=g.match(G))c=d[0],a=d.index;if(!d||-1===g.substring(0,a).indexOf("(")&&(a||!d[3]&&c!==d.input))k=!1;else{f=g;l="not"===d[1];a||(m=d[2],f=g.substring(c.length));k=m===x||"all"===m||""===m;g=-1!==f.indexOf(" and ")&&f.split(" and ")||[f];d=g.length-1;if(k&&0<=d&&""!==f){do{f=g[d].match(H);if(!f||!h[f[3]]){k=!1;break}k=f[2];n=p=f[5];q=f[7];r=h[f[3]];q&&(n="px"===q?Number(p):"em"===q||"rem"===q?16*p:f[8]?(p/f[8]).toFixed(2):"dppx"===q?96*p:"dpcm"===q?0.3937*p:Number(p));
k="min-"===k&&n?r>=n:"max-"===k&&n?r<=n:n?r===n:!!r;if(!k)break}while(d--)}if(k)break}}while(e--);return l?!k:k},B=function(){var b=c.innerWidth||w.clientWidth,a=c.innerHeight||w.clientHeight,e=c.screen.width,j=c.screen.height,g=c.screen.colorDepth,d=c.devicePixelRatio;h.width=b;h.height=a;h["aspect-ratio"]=(b/a).toFixed(2);h["device-width"]=e;h["device-height"]=j;h["device-aspect-ratio"]=(e/j).toFixed(2);h.color=g;h["color-index"]=Math.pow(2,g);h.orientation=a>=b?"portrait":"landscape";h.resolution=
d&&96*d||c.screen.deviceXDPI||96;h["device-pixel-ratio"]=d||1},C=function(){clearTimeout(y);y=setTimeout(function(){var b=null,a=t-1,e=a,j=!1;if(0<=a){B();do if(b=l[e-a])if((j=A(b.mql.media))&&!b.mql.matches||!j&&b.mql.matches)if(b.mql.matches=j,b.listeners)for(var j=0,g=b.listeners.length;j<g;j++)b.listeners[j]&&b.listeners[j].call(c,b.mql);while(a--)}},10)},D=a.getElementsByTagName("head")[0],a=a.createElement("style"),E=null,u="screen print speech projection handheld tv braille embossed tty".split(" "),
m=0,I=u.length,s="#mediamatchjs { position: relative; z-index: 0; }",v="",F=c.addEventListener||(v="on")&&c.attachEvent;a.type="text/css";a.id="mediamatchjs";D.appendChild(a);for(E=c.getComputedStyle&&c.getComputedStyle(a)||a.currentStyle;m<I;m++)s+="@media "+u[m]+" { #mediamatchjs { position: relative; z-index: "+m+" } }";a.styleSheet?a.styleSheet.cssText=s:a.textContent=s;x=u[1*E.zIndex||0];D.removeChild(a);B();F(v+"resize",C);F(v+"orientationchange",C);return function(a){var c=t,e={matches:!1,
media:a,addListener:function(a){l[c].listeners||(l[c].listeners=[]);a&&l[c].listeners.push(a)},removeListener:function(a){var b=l[c],d=0,e=0;if(b)for(e=b.listeners.length;d<e;d++)b.listeners[d]===a&&b.listeners.splice(d,1)}};if(""===a)return e.matches=!0,e;e.matches=A(a);t=l.push({mql:e,listeners:null});return e}}(window));
/*!
 * enquire.js v2.1.2 - Awesome Media Queries in JavaScript
 * Copyright (c) 2014 Nick Williams - http://wicky.nillia.ms/enquire.js
 * License: MIT (http://www.opensource.org/licenses/mit-license.php)
 */

!function(a,b,c){var d=window.matchMedia;"undefined"!=typeof module&&module.exports?module.exports=c(d):"function"==typeof define&&define.amd?define(function(){return b[a]=c(d)}):b[a]=c(d)}("enquire",this,function(a){"use strict";function b(a,b){var c,d=0,e=a.length;for(d;e>d&&(c=b(a[d],d),c!==!1);d++);}function c(a){return"[object Array]"===Object.prototype.toString.apply(a)}function d(a){return"function"==typeof a}function e(a){this.options=a,!a.deferSetup&&this.setup()}function f(b,c){this.query=b,this.isUnconditional=c,this.handlers=[],this.mql=a(b);var d=this;this.listener=function(a){d.mql=a,d.assess()},this.mql.addListener(this.listener)}function g(){if(!a)throw new Error("matchMedia not present, legacy browsers require a polyfill");this.queries={},this.browserIsIncapable=!a("only all").matches}return e.prototype={setup:function(){this.options.setup&&this.options.setup(),this.initialised=!0},on:function(){!this.initialised&&this.setup(),this.options.match&&this.options.match()},off:function(){this.options.unmatch&&this.options.unmatch()},destroy:function(){this.options.destroy?this.options.destroy():this.off()},equals:function(a){return this.options===a||this.options.match===a}},f.prototype={addHandler:function(a){var b=new e(a);this.handlers.push(b),this.matches()&&b.on()},removeHandler:function(a){var c=this.handlers;b(c,function(b,d){return b.equals(a)?(b.destroy(),!c.splice(d,1)):void 0})},matches:function(){return this.mql.matches||this.isUnconditional},clear:function(){b(this.handlers,function(a){a.destroy()}),this.mql.removeListener(this.listener),this.handlers.length=0},assess:function(){var a=this.matches()?"on":"off";b(this.handlers,function(b){b[a]()})}},g.prototype={register:function(a,e,g){var h=this.queries,i=g&&this.browserIsIncapable;return h[a]||(h[a]=new f(a,i)),d(e)&&(e={match:e}),c(e)||(e=[e]),b(e,function(b){d(b)&&(b={match:b}),h[a].addHandler(b)}),this},unregister:function(a,b){var c=this.queries[a];return c&&(b?c.removeHandler(b):(c.clear(),delete this.queries[a])),this}},new g});

/**
 * @file
 * Logic for transposing a vertical-heading table into a horizontal-heading
 * table.
 */
(function ($, Drupal, window, document) {
  if (window.enquire !== undefined) {
    // Transform a vertical header table into a horizontal header table so that
    // it can utilize horizontal header responsive styling.
    $.fn.transposeHorizontal = function () {
      var $this = $(this),
          table = $('<table><thead><tr></tr></thead><tbody></tbody></table>'),
          newRows = [];
      // Add vertical row th elements to the thead element of the horizontal
      // table.
      $('th', this).each(function () {
        $('thead > tr', table).append('<th>' + this.innerHTML + '</th>');
      });
      $this.find('tbody > tr').each(function() {
        var i = 0;
        $(this).find('td').each(function () {
          if (newRows[i] === undefined) {
            newRows[i] = $("<tr></tr>");
          }
          newRows[i++].append($(this));
        });
      });
      $.each(newRows, function() {
        $('tbody', table).append(this);
      });

      // After we've built up the table, add classes and then replace the
      // current table's HTML with the one we've built up.
      $this.addClass('responsive-table responsive-table--vertical transposed');
      $this.html(table.html());
    };
    // Transform a horizontal header table back into a vertical header table so
    // that it can be viewed properly at desktop screen sizes.
    $.fn.transposeVertical = function () {
      var $this = $(this),
        table = $('<table><tbody></tbody></table>'),
        newRows = [];
      $this.find('tbody > tr').each(function() {
        var i = 0;
        $(this).find('td').each(function() {
          if (newRows[i] === undefined) {
            newRows[i] = $("<tr></tr>");
          }
          newRows[i++].append($(this));
        });
      });
      // Add th elements at the beginning of each row.
      $this.find('th').each(function(index, value) {
        newRows[index].prepend(this);
      });
      $.each(newRows, function() {
        $('tbody', table).append(this);
      });

      // After we've built up the table, add classes and then replace the
      // current table's HTML with the one we've built up.
      $this.removeClass('transposed');
      $this.addClass('responsive-table responsive-table--vertical');
      $this.html(table.html());
    };

  }
})(jQuery, Drupal, this, this.document);

/**
 * circles - v0.0.6 - 2015-05-27
 *
 * Copyright (c) 2015 lugolabs
 * Licensed
 */
!function(){"use strict";var a=window.requestAnimationFrame||window.webkitRequestAnimationFrame||window.mozRequestAnimationFrame||window.oRequestAnimationFrame||window.msRequestAnimationFrame||function(a){setTimeout(a,1e3/60)},b=window.Circles=function(a){var b=a.id;if(this._el=document.getElementById(b),null!==this._el){this._radius=a.radius||10,this._duration=void 0===a.duration?500:a.duration,this._value=0,this._maxValue=a.maxValue||100,this._text=void 0===a.text?function(a){return this.htmlifyNumber(a)}:a.text,this._strokeWidth=a.width||10,this._colors=a.colors||["#EEE","#F00"],this._svg=null,this._movingPath=null,this._wrapContainer=null,this._textContainer=null,this._wrpClass=a.wrpClass||"circles-wrp",this._textClass=a.textClass||"circles-text",this._valClass=a.valueStrokeClass||"circles-valueStroke",this._maxValClass=a.maxValueStrokeClass||"circles-maxValueStroke",this._styleWrapper=a.styleWrapper===!1?!1:!0,this._styleText=a.styleText===!1?!1:!0;var c=Math.PI/180*270;this._start=-Math.PI/180*90,this._startPrecise=this._precise(this._start),this._circ=c-this._start,this._generate().update(a.value||0)}};b.prototype={VERSION:"0.0.6",_generate:function(){return this._svgSize=2*this._radius,this._radiusAdjusted=this._radius-this._strokeWidth/2,this._generateSvg()._generateText()._generateWrapper(),this._el.innerHTML="",this._el.appendChild(this._wrapContainer),this},_setPercentage:function(a){this._movingPath.setAttribute("d",this._calculatePath(a,!0)),this._textContainer.innerHTML=this._getText(this.getValueFromPercent(a))},_generateWrapper:function(){return this._wrapContainer=document.createElement("div"),this._wrapContainer.className=this._wrpClass,this._styleWrapper&&(this._wrapContainer.style.position="relative",this._wrapContainer.style.display="inline-block"),this._wrapContainer.appendChild(this._svg),this._wrapContainer.appendChild(this._textContainer),this},_generateText:function(){if(this._textContainer=document.createElement("div"),this._textContainer.className=this._textClass,this._styleText){var a={position:"absolute",top:0,left:0,textAlign:"center",width:"100%",fontSize:.7*this._radius+"px",height:this._svgSize+"px",lineHeight:this._svgSize+"px"};for(var b in a)this._textContainer.style[b]=a[b]}return this._textContainer.innerHTML=this._getText(0),this},_getText:function(a){return this._text?(void 0===a&&(a=this._value),a=parseFloat(a.toFixed(2)),"function"==typeof this._text?this._text.call(this,a):this._text):""},_generateSvg:function(){return this._svg=document.createElementNS("http://www.w3.org/2000/svg","svg"),this._svg.setAttribute("xmlns","http://www.w3.org/2000/svg"),this._svg.setAttribute("width",this._svgSize),this._svg.setAttribute("height",this._svgSize),this._generatePath(100,!1,this._colors[0],this._maxValClass)._generatePath(1,!0,this._colors[1],this._valClass),this._movingPath=this._svg.getElementsByTagName("path")[1],this},_generatePath:function(a,b,c,d){var e=document.createElementNS("http://www.w3.org/2000/svg","path");return e.setAttribute("fill","transparent"),e.setAttribute("stroke",c),e.setAttribute("stroke-width",this._strokeWidth),e.setAttribute("d",this._calculatePath(a,b)),e.setAttribute("class",d),this._svg.appendChild(e),this},_calculatePath:function(a,b){var c=this._start+a/100*this._circ,d=this._precise(c);return this._arc(d,b)},_arc:function(a,b){var c=a-.001,d=a-this._startPrecise<Math.PI?0:1;return["M",this._radius+this._radiusAdjusted*Math.cos(this._startPrecise),this._radius+this._radiusAdjusted*Math.sin(this._startPrecise),"A",this._radiusAdjusted,this._radiusAdjusted,0,d,1,this._radius+this._radiusAdjusted*Math.cos(c),this._radius+this._radiusAdjusted*Math.sin(c),b?"":"Z"].join(" ")},_precise:function(a){return Math.round(1e3*a)/1e3},htmlifyNumber:function(a,b,c){b=b||"circles-integer",c=c||"circles-decimals";var d=(a+"").split("."),e='<span class="'+b+'">'+d[0]+"</span>";return d.length>1&&(e+='.<span class="'+c+'">'+d[1].substring(0,2)+"</span>"),e},updateRadius:function(a){return this._radius=a,this._generate().update(!0)},updateWidth:function(a){return this._strokeWidth=a,this._generate().update(!0)},updateColors:function(a){this._colors=a;var b=this._svg.getElementsByTagName("path");return b[0].setAttribute("stroke",a[0]),b[1].setAttribute("stroke",a[1]),this},getPercent:function(){return 100*this._value/this._maxValue},getValueFromPercent:function(a){return this._maxValue*a/100},getValue:function(){return this._value},getMaxValue:function(){return this._maxValue},update:function(b,c){if(b===!0)return this._setPercentage(this.getPercent()),this;if(this._value==b||isNaN(b))return this;void 0===c&&(c=this._duration);var d,e,f,g,h=this,i=h.getPercent(),j=1;return this._value=Math.min(this._maxValue,Math.max(0,b)),c?(d=h.getPercent(),e=d>i,j+=d%1,f=Math.floor(Math.abs(d-i)/j),g=c/f,function k(b){if(e?i+=j:i-=j,e&&i>=d||!e&&d>=i)return void a(function(){h._setPercentage(d)});a(function(){h._setPercentage(i)});var c=Date.now(),f=c-b;f>=g?k(c):setTimeout(function(){k(Date.now())},g-f)}(Date.now()),this):(this._setPercentage(this.getPercent()),this)}},b.create=function(a){return new b(a)}}();
/*
* EASYDROPDOWN - A Drop-down Builder for Styleable Inputs and Menus
* Version: 2.1.4
* License: Creative Commons Attribution 3.0 Unported - CC BY 3.0
* http://creativecommons.org/licenses/by/3.0/
* This software may be used freely on commercial and non-commercial projects with attribution to the author/copyright holder.
* Author: Patrick Kunka
* Copyright 2013 Patrick Kunka, All Rights Reserved
*/

(function(d){function e(){this.isField=!0;this.keyboardMode=this.hasLabel=this.cutOff=this.disabled=this.inFocus=this.down=!1;this.nativeTouch=!0;this.wrapperClass="dropdown";this.onChange=null}e.prototype={constructor:e,instances:{},init:function(a,c){var b=this;d.extend(b,c);b.$select=d(a);b.id=a.id;b.options=[];b.$options=b.$select.find("option");b.isTouch="ontouchend"in document;b.$select.removeClass(b.wrapperClass+" dropdown");b.$select.is(":disabled")&&(b.disabled=!0);b.$options.length&&(b.$options.each(function(a){var c=
d(this);c.is(":selected")&&(b.selected={index:a,title:c.text()},b.focusIndex=a);c.hasClass("label")&&0==a?(b.hasLabel=!0,b.label=c.text(),c.attr("value","")):b.options.push({domNode:c[0],title:c.text(),value:c.val(),selected:c.is(":selected")})}),b.selected||(b.selected={index:0,title:b.$options.eq(0).text()},b.focusIndex=0),b.render())},render:function(){var a=this;a.$container=a.$select.wrap('<div class="'+a.wrapperClass+(a.isTouch&&a.nativeTouch?" touch":"")+(a.disabled?" disabled":"")+'"><span class="old"/></div>').parent().parent();
a.$active=d('<span class="selected">'+a.selected.title+"</span>").appendTo(a.$container);a.$carat=d('<span class="carat"/>').appendTo(a.$container);a.$scrollWrapper=d("<div><ul/></div>").appendTo(a.$container);a.$dropDown=a.$scrollWrapper.find("ul");a.$form=a.$container.closest("form");d.each(a.options,function(){a.$dropDown.append("<li"+(this.selected?' class="active"':"")+">"+this.title+"</li>")});a.$items=a.$dropDown.find("li");a.cutOff&&a.$items.length>a.cutOff&&a.$container.addClass("scrollable");
a.getMaxHeight();a.isTouch&&a.nativeTouch?a.bindTouchHandlers():a.bindHandlers()},getMaxHeight:function(){for(i=this.maxHeight=0;i<this.$items.length;i++){var a=this.$items.eq(i);this.maxHeight+=a.outerHeight();if(this.cutOff==i+1)break}},bindTouchHandlers:function(){var a=this;a.$container.on("click.easyDropDown",function(){a.$select.focus()});a.$select.on({change:function(){var c=d(this).find("option:selected"),b=c.text(),c=c.val();a.$active.text(b);"function"===typeof a.onChange&&a.onChange.call(a.$select[0],
{title:b,value:c})},focus:function(){a.$container.addClass("focus")},blur:function(){a.$container.removeClass("focus")}})},bindHandlers:function(){var a=this;a.query="";a.$container.on({"click.easyDropDown":function(){a.down||a.disabled?a.close():a.open()},"mousemove.easyDropDown":function(){a.keyboardMode&&(a.keyboardMode=!1)}});d("body").on("click.easyDropDown."+a.id,function(c){c=d(c.target);var b=a.wrapperClass.split(" ").join(".");!c.closest("."+b).length&&a.down&&a.close()});a.$items.on({"click.easyDropDown":function(){var c=
d(this).index();a.select(c);a.$select.focus()},"mouseover.easyDropDown":function(){if(!a.keyboardMode){var c=d(this);c.addClass("focus").siblings().removeClass("focus");a.focusIndex=c.index()}},"mouseout.easyDropDown":function(){a.keyboardMode||d(this).removeClass("focus")}});a.$select.on({"focus.easyDropDown":function(){a.$container.addClass("focus");a.inFocus=!0},"blur.easyDropDown":function(){a.$container.removeClass("focus");a.inFocus=!1},"keydown.easyDropDown":function(c){if(a.inFocus){a.keyboardMode=
!0;var b=c.keyCode;if(38==b||40==b||32==b)c.preventDefault(),38==b?(a.focusIndex--,a.focusIndex=0>a.focusIndex?a.$items.length-1:a.focusIndex):40==b&&(a.focusIndex++,a.focusIndex=a.focusIndex>a.$items.length-1?0:a.focusIndex),a.down||a.open(),a.$items.removeClass("focus").eq(a.focusIndex).addClass("focus"),a.cutOff&&a.scrollToView(),a.query="";if(a.down)if(9==b||27==b)a.close();else{if(13==b)return c.preventDefault(),a.select(a.focusIndex),a.close(),!1;if(8==b)return c.preventDefault(),a.query=a.query.slice(0,
-1),a.search(),clearTimeout(a.resetQuery),!1;38!=b&&40!=b&&(c=String.fromCharCode(b),a.query+=c,a.search(),clearTimeout(a.resetQuery))}}},"keyup.easyDropDown":function(){a.resetQuery=setTimeout(function(){a.query=""},1200)}});a.$dropDown.on("scroll.easyDropDown",function(c){a.$dropDown[0].scrollTop>=a.$dropDown[0].scrollHeight-a.maxHeight?a.$container.addClass("bottom"):a.$container.removeClass("bottom")});if(a.$form.length)a.$form.on("reset.easyDropDown",function(){a.$active.text(a.hasLabel?a.label:
a.options[0].title)})},unbindHandlers:function(){this.$container.add(this.$select).add(this.$items).add(this.$form).add(this.$dropDown).off(".easyDropDown");d("body").off("."+this.id)},open:function(){var a=window.scrollY||document.documentElement.scrollTop,c=window.scrollX||document.documentElement.scrollLeft,b=this.notInViewport(a);this.closeAll();this.getMaxHeight();this.$select.focus();window.scrollTo(c,a+b);this.$container.addClass("open");this.$scrollWrapper.css("height",this.maxHeight+"px");
this.down=!0},close:function(){this.$container.removeClass("open");this.$scrollWrapper.css("height","0px");this.focusIndex=this.selected.index;this.query="";this.down=!1},closeAll:function(){var a=Object.getPrototypeOf(this).instances,c;for(c in a)a[c].close()},select:function(a){"string"===typeof a&&(a=this.$select.find("option[value="+a+"]").index()-1);var c=this.options[a],b=this.hasLabel?a+1:a;this.$items.removeClass("active").eq(a).addClass("active");this.$active.text(c.title);this.$select.find("option").removeAttr("selected").eq(b).prop("selected",
!0).parent().trigger("change");this.selected={index:a,title:c.title};this.focusIndex=i;"function"===typeof this.onChange&&this.onChange.call(this.$select[0],{title:c.title,value:c.value})},search:function(){var a=this,c=function(b){a.focusIndex=b;a.$items.removeClass("focus").eq(a.focusIndex).addClass("focus");a.scrollToView()};for(i=0;i<a.options.length;i++){var b=a.options[i].title.toUpperCase();if(0==b.indexOf(a.query)){c(i);return}}for(i=0;i<a.options.length;i++)if(b=a.options[i].title.toUpperCase(),
-1<b.indexOf(a.query)){c(i);break}},scrollToView:function(){if(this.focusIndex>=this.cutOff){var a=this.$items.eq(this.focusIndex).outerHeight()*(this.focusIndex+1)-this.maxHeight;this.$dropDown.scrollTop(a)}},notInViewport:function(a){var c=a+(window.innerHeight||document.documentElement.clientHeight),b=this.$dropDown.offset().top+this.maxHeight;return b>=a&&b<=c?0:b-c+5},destroy:function(){this.unbindHandlers();this.$select.unwrap().siblings().remove();this.$select.unwrap();delete Object.getPrototypeOf(this).instances[this.$select[0].id]},
disable:function(){this.disabled=!0;this.$container.addClass("disabled");this.$select.attr("disabled",!0);this.down||this.close()},enable:function(){this.disabled=!1;this.$container.removeClass("disabled");this.$select.attr("disabled",!1)}};var f=function(a,c){a.id=a.id?a.id:"EasyDropDown"+("00000"+(16777216*Math.random()<<0).toString(16)).substr(-6).toUpperCase();var b=new e;b.instances[a.id]||(b.instances[a.id]=b,b.init(a,c))};d.fn.easyDropDown=function(){var a=arguments,c=[],b;b=this.each(function(){if(a&&
"string"===typeof a[0]){var b=e.prototype.instances[this.id][a[0]](a[1],a[2]);b&&c.push(b)}else f(this,a[0])});return c.length?1<c.length?c:c[0]:b};d(function(){"function"!==typeof Object.getPrototypeOf&&(Object.getPrototypeOf="object"===typeof"test".__proto__?function(a){return a.__proto__}:function(a){return a.constructor.prototype});d("select.dropdown").each(function(){var a=d(this).attr("data-settings");settings=a?d.parseJSON(a):{};f(this,settings)})})})(jQuery);

/**
 * @file
 * Add to calendar plugin
 * for events page.
 */
function $d(e){return document.getElementById(e)}var addeventatc=function(){var v,m,l,t,e=!1,s=1,r=!1,p=!0,u=!1,g=!1,f=!1,E=1,h="",w=!0,x=!0,b=!0,k=!0,y=!0,_=!0,T=!0,N="Apple",z="Google <em>(online)</em>",I="Office 365 <em>(online)</em>",C="Outlook",$="Outlook.com <em>(online)</em>",A="Yahoo <em>(online)</em>",L="Facebook Event",S=null,H=null,a=null,R=null,M=null,O=null;return{initialize:function(){if(!e){e=!0;try{addeventasync()}catch(e){}addeventatc.trycss(),addeventatc.generate()}},generate:function(){for(var c=document.getElementsByTagName("*"),i=0;i<c.length;i+=1)addeventatc.hasclass(c[i],"addeventatc")&&function(){var a="addeventatc"+s;c[i].id=a,addeventatc.hasclass(c[i],"keeptitle")||(c[i].title=""),c[i].style.visibility="visible",c[i].setAttribute("aria-haspopup","true"),c[i].setAttribute("aria-expanded","false"),c[i].setAttribute("tabindex","0"),c[i].setAttribute("translate","no"),c[i].setAttribute("data-loaded","true"),r?(c[i].onclick=function(){return!1},c[i].onmouseover=function(){clearTimeout(l),addeventatc.toogle(this,{type:"mouseover",id:a})},c[i].onmouseout=function(){l=setTimeout(function(){addeventatc.mouseout(this,{type:"mouseout",id:a})},100)}):(c[i].onclick=function(){return addeventatc.toogle(this,{type:"click",id:a}),!1},c[i].onmouseover=function(){},c[i].onmouseout=function(){}),c[i].onkeydown=function(e){var t=e.which||e.keyCode;"13"!=t&&"32"!=t&&"27"!=t&&"38"!=t&&"40"!=t||e.preventDefault(),"13"!=t&&"32"!=t||(addeventatc.keyboardclick(this,{type:"click",id:a}),addeventatc.toogle(this,{type:"click",id:a,keynav:!0})),"27"==t&&addeventatc.hideandreset(),"9"==t&&addeventatc.hideandreset(),"38"==t&&addeventatc.keyboard(this,{type:"keyboard",id:a,key:"up"}),"40"==t&&addeventatc.keyboard(this,{type:"keyboard",id:a,key:"down"}),0},c[i].onblur=function(){};var e,t=c[i];"none"!=c[i].getAttribute("data-styling")&&"inline-buttons"!=c[i].getAttribute("data-render")||(p=!1),p&&((e=document.createElement("span")).className="addeventatc_icon",t.appendChild(e)),s++,u=!0;for(var n=c[i].getElementsByTagName("*"),o=0;o<n.length;o+=1)addeventatc.hasclass(n[o],"atc_node")||(""!=n[o].className?n[o].className+=" atc_node notranslate":n[o].className+="atc_node");if("inline-buttons"==c[i].getAttribute("data-render")){c[i].onclick=function(){},addeventatc.toogle(c[i],{type:"render",id:a}),c[i].setAttribute("aria-expanded","true"),c[i].removeAttribute("tabindex"),c[i].onkeydown=function(){},c[i].blur=function(){};var d=document.getElementById(a+"-drop");if(d){d.setAttribute("aria-hidden","false");for(n=d.getElementsByTagName("SPAN"),o=0;o<n.length;o+=1){n[o];n[o].tabIndex="0",n[o].onkeydown=function(e){var t=e.which||e.keyCode;"13"!=t&&"32"!=t||e.target.click()}}}}}();p?addeventatc.applycss():(addeventatc.removeelement($d("ate_css")),addeventatc.removeelement($d("ate_tmp_css")),addeventatc.helpercss()),u&&!g&&(g=!0,addeventatc.track({typ:"exposure",cal:""}))},toogle:function(e,t){var a,n=!1,o="",d=!1,c=e.id,i=$d(c);if(i){a=i.getAttribute("data-direct");var l=i.getAttribute("data-intel"),s=i.getAttribute("data-intel-apple");"ios"==addeventatc.agent()&&"click"==t.type&&"true"!==s&&"false"!==l&&(a="appleical",i.setAttribute("data-intel-apple","true"));try{""!=$d(c).querySelector(".recurring").innerHTML&&(d=!0)}catch(e){}if("outlook"==a||"google"==a||"yahoo"==a||"hotmail"==a||"outlookcom"==a||"appleical"==a||"apple"==a||"facebook"==a)"click"==t.type&&(addeventatc.click({button:c,service:a,id:t.id}),null!=S&&addeventatc.trigger("button_click",{id:c}));else if("mouseover"==t.type&&m!=i&&(f=!1),"click"==t.type||"render"==t.type||"mouseover"==t.type&&!f){"mouseover"==t.type&&(f=!0,null!=H&&addeventatc.trigger("button_mouseover",{id:c})),n=addeventatc.getburl({id:c,facebook:!0}),""==h&&(h="appleical,google,office365,outlook,outlookcom,yahoo,facebook");for(var r,p=(h=(h+=",").replace(/ /gi,"")).split(","),u=0;u<p.length;u+=1)(w&&"ical"==p[u]||w&&"appleical"==p[u])&&(o+='<span class="ateappleical" id="'+c+'-appleical" role="menuitem" tabindex="-1">'+N+"</span>"),x&&"google"==p[u]&&(o+='<span class="ategoogle" id="'+c+'-google" role="menuitem" tabindex="-1">'+z+"</span>"),b&&"office365"==p[u]&&(o+='<span class="ateoffice365" id="'+c+'-office365" role="menuitem" tabindex="-1">'+I+"</span>"),k&&"outlook"==p[u]&&(o+='<span class="ateoutlook" id="'+c+'-outlook" role="menuitem" tabindex="-1">'+C+"</span>"),(y&&"hotmail"==p[u]||y&&"outlookcom"==p[u])&&(o+='<span class="ateoutlookcom" id="'+c+'-outlookcom" role="menuitem" tabindex="-1">'+$+"</span>"),_&&"yahoo"==p[u]&&!d&&(o+='<span class="ateyahoo" id="'+c+'-yahoo" role="menuitem" tabindex="-1">'+A+"</span>"),n&&"facebook"==p[u]&&T&&"facebook"==p[u]&&(o+='<span class="atefacebook" id="'+c+'-facebook" role="menuitem" tabindex="-1">'+L+"</span>");addeventatc.getlicense(v)||(o+='<em class="copyx"><em class="brx"></em><em class="frs"><a href="https://www.addevent.com" title="" tabindex="-1" id="'+c+'-home">AddEvent.com</a></em></em>'),$d(c+"-drop")||((r=document.createElement("span")).id=c+"-drop",r.className="addeventatc_dropdown",r.setAttribute("aria-hidden","true"),r.setAttribute("aria-labelledby",c),r.innerHTML=o,i.appendChild(r)),$d(c+"-appleical")&&($d(c+"-appleical").onclick=function(){addeventatc.click({button:c,service:"appleical",id:t.id})}),$d(c+"-google")&&($d(c+"-google").onclick=function(){addeventatc.click({button:c,service:"google",id:t.id})}),$d(c+"-office365")&&($d(c+"-office365").onclick=function(){addeventatc.click({button:c,service:"office365",id:t.id})}),$d(c+"-outlook")&&($d(c+"-outlook").onclick=function(){addeventatc.click({button:c,service:"outlook",id:t.id})}),$d(c+"-outlookcom")&&($d(c+"-outlookcom").onclick=function(){addeventatc.click({button:c,service:"outlookcom",id:t.id})}),$d(c+"-yahoo")&&($d(c+"-yahoo").onclick=function(){addeventatc.click({button:c,service:"yahoo",id:t.id})}),$d(c+"-facebook")&&($d(c+"-facebook").onclick=function(){addeventatc.click({button:c,service:"facebook",id:t.id})}),$d(c+"-home")&&($d(c+"-home").onclick=function(){addeventatc.click({button:c,service:"home",id:t.id})}),addeventatc.show(c,t)}return m=i,!1}},click:function(e){var t,a,n,o,d=!0,c=window.location.href,i=$d(e.button);if(i){"home"==e.service?a="https://www.addevent.com":(t=addeventatc.getburl({id:e.button,facebook:!1}),a="https://www.addevent.com/create/?service="+e.service+t+"&reference="+c,"outlook"!=e.service&&"appleical"!=e.service||(d=!1,addeventatc.usewebcal()&&(a="webcal://www.addevent.com/create/?uwc=on&service="+e.service+t+"&reference="+c)),null!==(n=i.getAttribute("data-id"))&&(a=addeventatc.usewebcal()?"webcal://www.addevent.com/event/"+n+"+"+e.service+"/?uwc=on":"https://www.addevent.com/event/"+n+"+"+e.service)),$d("atecllink")||((o=document.createElement("a")).id="atecllink",o.rel="external",o.setAttribute("data-role","none"),o.innerHTML="{addeventatc-ghost-link}",o.style.display="none",document.body.appendChild(o));var l=$d("atecllink");if(l.target=d?"_blank":"_self",l.href=a,addeventatc.eclick("atecllink"),addeventatc.track({typ:"click",cal:e.service}),null!=O){addeventatc.trigger("button_dropdown_click",{id:e.button,service:e.service});try{(event||window.event).stopPropagation()}catch(e){}}}},mouseout:function(e,t){f=!1,addeventatc.hideandreset(),null!=a&&addeventatc.trigger("button_mouseout",{id:t.id})},show:function(e,t){var a,n,o,d,c,i,l,s,r,p,u,v,m,g,f,h,w,x,b,k,y,_,T=$d(e),N=$d(e+"-drop");T&&N&&("block"==addeventatc.getstyle(N,"display")?addeventatc.hideandreset():(addeventatc.hideandreset(!0),N.style.display="block",T.style.outline="0",E=addeventatc.topzindex(),T.style.zIndex=E+1,T.className=T.className.replace(/\s+/g," "),T.setAttribute("aria-expanded","true"),N.setAttribute("aria-hidden","false"),t.keynav&&addeventatc.keyboard(this,{type:"keyboard",id:e,key:"down"}),o="auto",d=null!=(a=T.getAttribute("data-dropdown-x"))?a:"auto",null!=(n=T.getAttribute("data-dropdown-y"))&&(o=n),N.style.left="0px",N.style.top="0px",N.style.display="block",c=parseInt(T.offsetHeight),i=parseInt(T.offsetWidth),l=parseInt(N.offsetHeight),s=parseInt(N.offsetWidth),r=addeventatc.viewport(),p=parseInt(r.w),u=parseInt(r.h),v=parseInt(r.x),m=parseInt(r.y),g=addeventatc.elementposition(N),f=parseInt(g.x),parseInt(g.y),(h=addeventatc.elementposition(T)).x,w=f+s,x=p+v,k=b=0,y="",_=h.y-(l/2-c),"down"==o&&"left"==d?(k=b="-2px",y="topdown"):"up"==o&&"left"==d?(b="0px",k=-(l-c-2)+"px"):"down"==o&&"right"==d?(b=-(s-i-2)+"px",k="-2px",y="topdown"):"up"==o&&"right"==d?(b=-(s-i-2)+"px",k=-(l-c-2)+"px"):"auto"==o&&"left"==d?(b="0px",_<m?(k="-2px",y="topdown"):k=m+u<_+l?-(l-c-2)+"px":-(l/2-c)+"px"):"auto"==o&&"right"==d?(b=-(s-i-2)+"px",_<m?(k="-2px",y="topdown"):k=m+u<_+l?-(l-c-2)+"px":-(l/2-c)+"px"):"down"==o&&"auto"==d?(b=x<w?-(s-i-2)+"px":"-2px",k="-2px",y="topdown"):"up"==o&&"auto"==d?(b=x<w?-(s-i-2)+"px":"-2px",k=-(l-c-2)+"px"):(_<m?(k="-2px",y="topdown"):k=m+u<_+l?-(l-c-2)+"px":-(l/2-c)+"px",b=x<w?-(s-i-2)+"px":"-2px"),N.style.left=b,N.style.top=k,""!=y&&(N.className=N.className+" "+y),setTimeout(function(){N.className=N.className+" addeventatc-selected"},1),"click"==t.type&&null!=S&&addeventatc.trigger("button_click",{id:e}),null!=R&&addeventatc.trigger("button_dropdown_show",{id:e})))},hide:function(e){var t=!1;("string"==typeof e&&""!==e||e instanceof String&&""!==e)&&(-1<e.indexOf("addeventatc")||-1<e.indexOf("atc_node"))&&(t=!0),t||addeventatc.hideandreset()},hideandreset:function(e){for(var t="",a=document.getElementsByTagName("*"),n=0;n<a.length;n+=1)if(addeventatc.hasclass(a[n],"addeventatc")){a[n].className=a[n].className.replace(/addeventatc-selected/gi,""),a[n].className=a[n].className.replace(/\s+$/,""),a[n].style.outline="";var o=$d(a[n].id+"-drop");if(o){var d=addeventatc.getstyle(o,"display");"block"==d&&(t=a[n].id),o.style.display="none","block"!==(d=addeventatc.getstyle(o,"display"))&&(a[n].setAttribute("aria-expanded","false"),o.setAttribute("aria-hidden","true"),o.className=o.className.replace(/addeventatc-selected/gi,""),o.className=o.className.replace(/topdown/gi,""),o.removeAttribute("style"));for(var c=o.getElementsByTagName("SPAN"),i=0;i<c.length;i+=1){var l=new RegExp("(\\s|^)drop_markup(\\s|$)");c[i].className=c[i].className.replace(l," "),c[i].className=c[i].className.replace(/\s+$/,""),c[i].tabIndex="-1"}}}e||null!=M&&addeventatc.trigger("button_dropdown_hide",{id:t})},getburl:function(e){var t=$d(e.id),a="",n=!1;if(t){for(var o=t.getElementsByTagName("*"),d=0;d<o.length;d+=1)(addeventatc.hasclass(o[d],"_start")||addeventatc.hasclass(o[d],"start"))&&(a+="&dstart="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_end")||addeventatc.hasclass(o[d],"end"))&&(a+="&dend="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_zonecode")||addeventatc.hasclass(o[d],"zonecode"))&&(a+="&dzone="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_timezone")||addeventatc.hasclass(o[d],"timezone"))&&(a+="&dtime="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_summary")||addeventatc.hasclass(o[d],"summary")||addeventatc.hasclass(o[d],"title"))&&(a+="&dsum="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_description")||addeventatc.hasclass(o[d],"description"))&&(a+="&ddesc="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_location")||addeventatc.hasclass(o[d],"location"))&&(a+="&dloca="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_organizer")||addeventatc.hasclass(o[d],"organizer"))&&(a+="&dorga="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_organizer_email")||addeventatc.hasclass(o[d],"organizer_email"))&&(a+="&dorgaem="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_attendees")||addeventatc.hasclass(o[d],"attendees"))&&(a+="&datte="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_all_day_event")||addeventatc.hasclass(o[d],"all_day_event"))&&(a+="&dallday="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_date_format")||addeventatc.hasclass(o[d],"date_format"))&&(a+="&dateformat="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_alarm_reminder")||addeventatc.hasclass(o[d],"alarm_reminder"))&&(a+="&alarm="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_recurring")||addeventatc.hasclass(o[d],"recurring"))&&(a+="&drule="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_facebook_event")||addeventatc.hasclass(o[d],"facebook_event"))&&(a+="&fbevent="+encodeURIComponent(o[d].innerHTML),n=!0),(addeventatc.hasclass(o[d],"_client")||addeventatc.hasclass(o[d],"client"))&&(a+="&client="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_calname")||addeventatc.hasclass(o[d],"calname"))&&(a+="&calname="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_uid")||addeventatc.hasclass(o[d],"uid"))&&(a+="&uid="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_sequence")||addeventatc.hasclass(o[d],"sequence"))&&(a+="&seq="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_status")||addeventatc.hasclass(o[d],"status"))&&(a+="&status="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_method")||addeventatc.hasclass(o[d],"method"))&&(a+="&method="+encodeURIComponent(o[d].innerHTML)),(addeventatc.hasclass(o[d],"_transp")||addeventatc.hasclass(o[d],"transp"))&&(a+="&transp="+encodeURIComponent(o[d].innerHTML));"true"==t.getAttribute("data-google-api")&&(a+="&gapi=true"),"true"==t.getAttribute("data-outlook-api")&&(a+="&oapi=true")}return e.facebook&&(a=n),a},trycss:function(){if(!$d("ate_tmp_css")){try{var e="",e=".addeventatc {visibility:hidden;}";e+=".addeventatc .data {display:none!important;}",e+=".addeventatc .start, .addeventatc .end, .addeventatc .timezone, .addeventatc .title, .addeventatc .description, .addeventatc .location, .addeventatc .organizer, .addeventatc .organizer_email, .addeventatc .facebook_event, .addeventatc .all_day_event, .addeventatc .date_format, .addeventatc .alarm_reminder, .addeventatc .recurring, .addeventatc .attendees, .addeventatc .client, .addeventatc .calname, .addeventatc .uid, .addeventatc .sequence, .addeventatc .status, .addeventatc .method, .addeventatc .transp {display:none!important;}",p&&(e+=".addeventatc {background-image:url(https://www.addevent.com/gfx/icon-calendar-t5.png), url(https://www.addevent.com/gfx/icon-calendar-t1.svg), url(https://www.addevent.com/gfx/icon-apple-t5.svg), url(https://www.addevent.com/gfx/icon-facebook-t5.svg), url(https://www.addevent.com/gfx/icon-google-t5.svg), url(https://www.addevent.com/gfx/icon-office365-t5.svg), url(https://www.addevent.com/gfx/icon-outlook-t5.svg),  url(https://www.addevent.com/gfx/icon-outlookcom-t5.svg), url(https://www.addevent.com/gfx/icon-yahoo-t5.svg);background-position:-9999px -9999px;background-repeat:no-repeat;}");var t=document.createElement("style");t.type="text/css",t.id="ate_tmp_css",t.styleSheet?t.styleSheet.cssText=e:t.appendChild(document.createTextNode(e)),document.getElementsByTagName("head")[0].appendChild(t)}catch(e){}addeventatc.track({typ:"jsinit",cal:""})}},applycss:function(){var e,t;$d("ate_css")&&!$d("ate_css_plv")&&($d("ate_css").id=$d("ate_css").id.replace(/ate_css/gi,"ate_css_plv")),$d("ate_css")||(e="",e+='@import url("https://fonts.googleapis.com/css?family=Open+Sans:400,400i,600");',e+='.addeventatc \t\t\t\t\t\t\t{display:inline-block;position:relative;z-index:99998;font-family:"Open Sans",Roboto,"Helvetica Neue",Helvetica,Optima,Segoe,"Segoe UI",Candara,Calibri,Arial,sans-serif;color:#000!important;font-weight:600;line-height:100%;background:#fff;font-size:15px;text-decoration:none;border:1px solid transparent;padding:13px 12px 12px 43px;-webkit-border-radius:3px;border-radius:3px;cursor:pointer;-webkit-font-smoothing:antialiased!important;outline-color:rgba(0,78,255,0.5);text-shadow:1px 1px 1px rgba(0,0,0,0.004);-webkit-user-select:none;-webkit-tap-highlight-color:rgba(0,0,0,0);box-shadow:0 0 0 0.5px rgba(50,50,93,.17), 0 2px 5px 0 rgba(50,50,93,.1), 0 1px 1.5px 0 rgba(0,0,0,.07), 0 1px 2px 0 rgba(0,0,0,.08), 0 0 0 0 transparent!important;background-image:url(https://www.addevent.com/gfx/icon-calendar-t5.png), url(https://www.addevent.com/gfx/icon-calendar-t1.svg), url(https://www.addevent.com/gfx/icon-apple-t5.svg), url(https://www.addevent.com/gfx/icon-facebook-t5.svg), url(https://www.addevent.com/gfx/icon-google-t5.svg), url(https://www.addevent.com/gfx/icon-office365-t5.svg), url(https://www.addevent.com/gfx/icon-outlook-t5.svg), url(https://www.addevent.com/gfx/icon-outlookcom-t5.svg), url(https://www.addevent.com/gfx/icon-yahoo-t5.svg);background-position:-9999px -9999px;background-repeat:no-repeat;}',e+=".addeventatc:hover \t\t\t\t\t\t{background-color:#fafafa;color:#000;font-size:15px;text-decoration:none;}",e+=".addeventatc:active \t\t\t\t\t{border-width:2px 1px 0px 1px;}",e+=".addeventatc-selected \t\t\t\t\t{background-color:#f9f9f9;}",e+=".addeventatc .addeventatc_icon \t\t\t{width:18px;height:18px;position:absolute;z-index:1;left:12px;top:10px;background:url(https://www.addevent.com/gfx/icon-calendar-t1.svg) no-repeat;background-size:18px 18px;}",e+=".addeventatc .start, .addeventatc .end, .addeventatc .timezone, .addeventatc .title, .addeventatc .description, .addeventatc .location, .addeventatc .organizer, .addeventatc .organizer_email, .addeventatc .facebook_event, .addeventatc .all_day_event, .addeventatc .date_format, .addeventatc .alarm_reminder, .addeventatc .recurring, .addeventatc .attendees, .addeventatc .calname, .addeventatc .uid, .addeventatc .sequence, .addeventatc .status, .addeventatc .method, .addeventatc .client, .addeventatc .transp {display:none!important;}",e+=".addeventatc br \t\t\t\t\t\t{display:none;}",addeventatc.getlicense(v)?e+='.addeventatc_dropdown \t\t\t\t{width:230px;position:absolute;padding:6px 0px 6px 0px;font-family:"Open Sans",Roboto,"Helvetica Neue",Helvetica,Optima,Segoe,"Segoe UI",Candara,Calibri,Arial,sans-serif;color:#000!important;font-weight:600;line-height:100%;background:#fff;font-size:15px;text-decoration:none;text-align:left;margin-left:-1px;display:none;-moz-border-radius:3px;-webkit-border-radius:3px;-webkit-box-shadow:rgba(0,0,0,0.4) 0px 10px 26px;-moz-box-shadow:rgba(0,0,0,0.4) 0px 10px 26px;box-shadow:rgba(0,0,0,0.4) 0px 10px 26px;transform:scale(.98,.98) translateY(5px);opacity:0.5;z-index:-1;transition:transform .15s ease;-webkit-user-select:none;-webkit-tap-highlight-color:rgba(0,0,0,0);}':e+='.addeventatc_dropdown \t\t\t\t{width:230px;position:absolute;padding:6px 0px 0px 0px;font-family:"Open Sans",Roboto,"Helvetica Neue",Helvetica,Optima,Segoe,"Segoe UI",Candara,Calibri,Arial,sans-serif;color:#000!important;font-weight:600;line-height:100%;background:#fff;font-size:15px;text-decoration:none;text-align:left;margin-left:-1px;display:none;-moz-border-radius:3px;-webkit-border-radius:3px;-webkit-box-shadow:rgba(0,0,0,0.4) 0px 10px 26px;-moz-box-shadow:rgba(0,0,0,0.4) 0px 10px 26px;box-shadow:rgba(0,0,0,0.4) 0px 10px 26px;transform:scale(.98,.98) translateY(5px);opacity:0.5;z-index:-1;transition:transform .15s ease;-webkit-user-select:none;-webkit-tap-highlight-color:rgba(0,0,0,0);}',e+=".addeventatc_dropdown.topdown \t\t\t{transform:scale(.98,.98) translateY(-5px)!important;}",e+=".addeventatc_dropdown span \t\t\t\t{display:block;line-height:100%;background:#fff;text-decoration:none;cursor:pointer;font-size:15px;color:#333;font-weight:600;padding:14px 10px 14px 55px;margin:-2px 0px;}",e+=".addeventatc_dropdown span:hover \t\t{background-color:#f4f4f4;color:#000;text-decoration:none;font-size:15px;}",e+=".addeventatc_dropdown em \t\t\t\t{color:#999!important;font-size:12px!important;font-weight:400;}",e+=".addeventatc_dropdown .frs a \t\t\t{background:#fff;color:#cacaca!important;cursor:pointer;font-size:9px!important;font-style:normal!important;font-weight:400!important;line-height:110%!important;padding-left:10px;position:absolute;right:10px;text-align:right;text-decoration:none;top:5px;z-index:101;}",e+=".addeventatc_dropdown .frs a:hover \t\t{color:#999!important;}",e+=".addeventatc_dropdown .ateappleical \t{background:url(https://www.addevent.com/gfx/icon-apple-t5.svg) 18px 40% no-repeat;background-size:22px 100%;}",e+=".addeventatc_dropdown .ategoogle \t\t{background:url(https://www.addevent.com/gfx/icon-google-t5.svg) 18px 50% no-repeat;background-size:22px 100%;}",e+=".addeventatc_dropdown .ateoffice365 \t{background:url(https://www.addevent.com/gfx/icon-office365-t5.svg) 19px 50% no-repeat;background-size:18px 100%;}",e+=".addeventatc_dropdown .ateoutlook \t\t{background:url(https://www.addevent.com/gfx/icon-outlook-t5.svg) 18px 50% no-repeat;background-size:22px 100%;}",e+=".addeventatc_dropdown .ateoutlookcom \t{background:url(https://www.addevent.com/gfx/icon-outlookcom-t5.svg) 18px 50% no-repeat;background-size:22px 100%;}",e+=".addeventatc_dropdown .ateyahoo \t\t{background:url(https://www.addevent.com/gfx/icon-yahoo-t5.svg) 18px 50% no-repeat;background-size:22px 100%;}",e+=".addeventatc_dropdown .atefacebook \t\t{background:url(https://www.addevent.com/gfx/icon-facebook-t5.svg) 18px 50% no-repeat;background-size:22px 100%;}",e+=".addeventatc_dropdown .copyx \t\t\t{height:21px;display:block;position:relative;cursor:default;}",e+=".addeventatc_dropdown .brx \t\t\t\t{height:1px;overflow:hidden;background:#e8e8e8;position:absolute;z-index:100;left:10px;right:10px;top:9px;}",e+=".addeventatc_dropdown.addeventatc-selected {opacity:1;transform:scale(1,1) translateY(0px);z-index:99999999;}",e+=".addeventatc_dropdown.topdown.addeventatc-selected {transform:scale(1,1) translateY(0px)!important;}",e+=".addeventatc_dropdown .drop_markup {background-color:#f4f4f4;}",(t=document.createElement("style")).type="text/css",t.id="ate_css",t.styleSheet?t.styleSheet.cssText=e:t.appendChild(document.createTextNode(e)),document.getElementsByTagName("head")[0].appendChild(t),addeventatc.removeelement($d("ate_tmp_css")))},helpercss:function(){var e,t;$d("ate_helper_css")||(e="",e+=".addeventatc_dropdown .drop_markup {background-color:#f4f4f4;}",e+=".addeventatc_dropdown .frs a {margin:0!important;padding:0!important;font-style:normal!important;font-weight:normal!important;line-height:110%!important;background-color:#fff!important;text-decoration:none;font-size:9px!important;color:#cacaca!important;display:inline-block;}",e+=".addeventatc_dropdown .frs a:hover {color:#999!important;}",e+=".addeventatc .start, .addeventatc .end, .addeventatc .timezone, .addeventatc .title, .addeventatc .description, .addeventatc .location, .addeventatc .organizer, .addeventatc .organizer_email, .addeventatc .facebook_event, .addeventatc .all_day_event, .addeventatc .date_format, .addeventatc .alarm_reminder, .addeventatc .recurring, .addeventatc .attendees, .addeventatc .client, .addeventatc .calname, .addeventatc .uid, .addeventatc .sequence, .addeventatc .status, .addeventatc .method, .addeventatc .transp {display:none!important;}",(t=document.createElement("style")).type="text/css",t.id="ate_helper_css",t.styleSheet?t.styleSheet.cssText=e:t.appendChild(document.createTextNode(e)),document.getElementsByTagName("head")[0].appendChild(t))},removeelement:function(e){try{return!!(hdx=e)&&hdx.parentNode.removeChild(hdx)}catch(e){}},topzindex:function(){for(var e,t=1,a=document.getElementsByTagName("*"),n=0;n<a.length;n+=1){(addeventatc.hasclass(a[n],"addeventatc")||addeventatc.hasclass(a[n],"addeventstc"))&&(e=addeventatc.getstyle(a[n],"z-index"),!isNaN(parseFloat(e))&&isFinite(e)&&t<(e=parseInt(e))&&(t=e))}return t},viewport:function(){var e=0,t=0,a=0,n=0;return"number"==typeof window.innerWidth?(e=window.innerWidth,t=window.innerHeight):document.documentElement&&(document.documentElement.clientWidth||document.documentElement.clientHeight)?(e=document.documentElement.clientWidth,t=document.documentElement.clientHeight):document.body&&(document.body.clientWidth||document.body.clientHeight)&&(e=document.body.clientWidth,t=document.body.clientHeight),a=document.all?(n=document.documentElement.scrollLeft?document.documentElement.scrollLeft:document.body.scrollLeft,document.documentElement.scrollTop?document.documentElement.scrollTop:document.body.scrollTop):(n=window.pageXOffset,window.pageYOffset),{w:e,h:t,x:n,y:a}},elementposition:function(e){var t=0,a=0;if(e.offsetParent)for(t=e.offsetLeft,a=e.offsetTop;e=e.offsetParent;)t+=e.offsetLeft,a+=e.offsetTop;return{x:t,y:a}},getstyle:function(e,t){var a;return e.currentStyle?a=e.currentStyle[t]:window.getComputedStyle&&(a=document.defaultView.getComputedStyle(e,null).getPropertyValue(t)),a},getlicense:function(e){var t,a,n,o=location.origin,d=!1;return void 0===location.origin&&(o=location.protocol+"//"+location.host),e&&(t=e.substring(0,1),a=e.substring(9,10),n=e.substring(17,18),"a"==t&&"z"==a&&"m"==n&&(d=!0)),(-1==o.indexOf("addevent.com")&&"aao8iuet5zp9iqw5sm9z"==e||-1==o.indexOf("addevent.to")&&"aao8iuet5zp9iqw5sm9z"==e||-1==o.indexOf("addevent.com")&&"aao8iuet5zp9iqw5sm9z"==e)&&(d=!0),d},refresh:function(){for(var e=document.getElementsByTagName("*"),t=[],a=0;a<e.length;a+=1)if(addeventatc.hasclass(e[a],"addeventatc")){e[a].className=e[a].className.replace(/addeventatc-selected/gi,""),e[a].id="";for(var n=e[a].getElementsByTagName("*"),o=0;o<n.length;o+=1)(addeventatc.hasclass(n[o],"addeventatc_icon")||addeventatc.hasclass(n[o],"addeventatc_dropdown"))&&t.push(n[o])}for(var d=0;d<t.length;d+=1)addeventatc.removeelement(t[d]);addeventatc.removeelement($d("ate_css")),g=!(s=1),addeventatc.generate()},hasclass:function(e,t){return new RegExp("(\\s|^)"+t+"(\\s|$)").test(e.className)},eclick:function(e){var t,a=document.getElementById(e);a.click?a.click():document.createEvent&&((t=document.createEvent("MouseEvents")).initEvent("click",!0,!0),a.dispatchEvent(t))},track:function(e){new Image,(new Date).getTime(),encodeURIComponent(window.location.origin)},getguid:function(){for(var e,t,a,n="addevent_track_cookie=",o="",d=document.cookie.split(";"),c=0;c<d.length;c++){for(var i=d[c];" "==i.charAt(0);)i=i.substring(1,i.length);0==i.indexOf(n)&&(o=i.substring(n.length,i.length))}return""==o&&(e=(addeventatc.s4()+addeventatc.s4()+"-"+addeventatc.s4()+"-4"+addeventatc.s4().substr(0,3)+"-"+addeventatc.s4()+"-"+addeventatc.s4()+addeventatc.s4()+addeventatc.s4()).toLowerCase(),(t=new Date).setTime(t.getTime()+31536e6),a="expires="+t.toUTCString(),document.cookie="addevent_track_cookie="+e+"; "+a,o=e),o},s4:function(){return(65536*(1+Math.random())|0).toString(16).substring(1)},documentclick:function(e){e=(e=e||window.event).target||e.srcElement,ate_touch_capable?(clearTimeout(t),t=setTimeout(function(){addeventatc.hide(e.className)},200)):addeventatc.hide(e.className)},trigger:function(e,t){if("button_click"==e)try{S(t)}catch(e){}if("button_mouseover"==e)try{H(t)}catch(e){}if("button_mouseout"==e)try{a(t)}catch(e){}if("button_dropdown_show"==e)try{R(t)}catch(e){}if("button_dropdown_hide"==e)try{M(t)}catch(e){}if("button_dropdown_click"==e)try{O(t)}catch(e){}},register:function(e,t){"button-click"==e&&(S=t),"button-mouseover"==e&&(H=t),"button-mouseout"==e&&(a=t),"button-dropdown-show"==e&&(R=t),"button-dropdown-hide"==e&&(M=t),"button-dropdown-click"==e&&(O=t)},settings:function(e){null!=e.license&&(v=e.license),null!=e.css&&(e.css?p=!0:(p=!1,addeventatc.removeelement($d("ate_css")))),null!=e.mouse&&(r=e.mouse),/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)&&(r=!1),null!=e.outlook&&null!=e.outlook.show&&(k=e.outlook.show),null!=e.google&&null!=e.google.show&&(x=e.google.show),null!=e.office365&&null!=e.office365.show&&(b=e.office365.show),null!=e.yahoo&&null!=e.yahoo.show&&(_=e.yahoo.show),null!=e.hotmail&&null!=e.hotmail.show&&(y=e.hotmail.show),null!=e.outlookcom&&null!=e.outlookcom.show&&(y=e.outlookcom.show),null!=e.ical&&null!=e.ical.show&&(w=e.ical.show),null!=e.appleical&&null!=e.appleical.show&&(w=e.appleical.show),null!=e.facebook&&null!=e.facebook.show&&(T=e.facebook.show),null!=e.outlook&&null!=e.outlook.text&&(C=e.outlook.text),null!=e.google&&null!=e.google.text&&(z=e.google.text),null!=e.office365&&null!=e.office365.text&&(I=e.office365.text),null!=e.yahoo&&null!=e.yahoo.text&&(A=e.yahoo.text),null!=e.hotmail&&null!=e.hotmail.text&&($=e.hotmail.text),null!=e.outlookcom&&null!=e.outlookcom.text&&($=e.outlookcom.text),null!=e.ical&&null!=e.ical.text&&(N=e.ical.text),null!=e.appleical&&null!=e.appleical.text&&(N=e.appleical.text),null!=e.facebook&&null!=e.facebook.text&&(L=e.facebook.text),null!=e.dropdown&&null!=e.dropdown.order&&(h=e.dropdown.order)},keyboard:function(e,t){var a=document.getElementById(t.id+"-drop");if(a&&"block"==addeventatc.getstyle(a,"display")){for(var n=a.getElementsByTagName("SPAN"),o=null,d=0,c=0,i=0;i<n.length;i+=1)d++,addeventatc.hasclass(n[i],"drop_markup")&&(o=n[i],c=d);null===o?c=1:"down"==t.key?d<=c?c=1:c++:1==c?c=d:c--;for(var l,i=d=0;i<n.length;i+=1){++d==c?(n[i].className+=" drop_markup",n[i].tabIndex="0",n[i].focus()):(l=new RegExp("(\\s|^)drop_markup(\\s|$)"),n[i].className=n[i].className.replace(l," "),n[i].className=n[i].className.replace(/\s+$/,""),n[i].tabIndex="-1")}}},keyboardclick:function(e,t){var a=document.getElementById(t.id+"-drop");if(a){for(var n=a.getElementsByTagName("SPAN"),o=null,d=0;d<n.length;d+=1)addeventatc.hasclass(n[d],"drop_markup")&&(o=n[d]);if(null!==o){o.click();for(d=0;d<n.length;d+=1){var c=new RegExp("(\\s|^)drop_markup(\\s|$)");n[d].className=n[d].className.replace(c," "),n[d].className=n[d].className.replace(/\s+$/,"")}}}},usewebcal:function(){var e=!1,t=!1,a=window.navigator.userAgent.toLowerCase();navigator.userAgent.match(/CriOS|FxiOS|OPiOS|mercury|gsa/i)&&(t=!0);var n=/iphone|ipod|ipad/.test(a);(-1<a.indexOf("fban")||-1<a.indexOf("fbav")&&n)&&(t=!0);var o=/(iPhone|iPod|iPad).*AppleWebKit(?!.*Safari)/i.test(navigator.userAgent);return(n&&o||n&&t)&&(alert('If the event fails to load please \n"Open the page in Safari".'),e=!0),e},agent:function(){var e=navigator.userAgent||navigator.vendor||window.opera;return/windows phone/i.test(e)?"windows_phone":/android/i.test(e)?"android":/iPad|iPhone|iPod/.test(e)&&!window.MSStream?"ios":"unknown"},isloaded:function(){return!!e},notloadedcnt:function(){for(var e=document.getElementsByClassName("addeventatc"),t=0,a=0;a<e.length;a+=1)"true"==e[a].getAttribute("data-loaded")&&t++;e.length>t&&addeventatc.refresh()}}}();!function(e,t){"use strict";e=e||"docReady",t=t||window;var a=[],n=!1,o=!1;function d(){if(!n){n=!0;for(var e=0;e<a.length;e++)a[e].fn.call(window,a[e].ctx);a=[]}}function c(){"complete"===document.readyState&&d()}t[e]=function(e,t){if("function"!=typeof e)throw new TypeError("callback for docReady(fn) must be a function");n?setTimeout(function(){e(t)},1):(a.push({fn:e,ctx:t}),"complete"===document.readyState||!document.attachEvent&&"interactive"===document.readyState?setTimeout(d,1):o||(document.addEventListener?(document.addEventListener("DOMContentLoaded",d,!1),window.addEventListener("load",d,!1)):(document.attachEvent("onreadystatechange",c),window.attachEvent("onload",d)),o=!0))}}("addeventReady",window);var ate_touch_capable="ontouchstart"in window||window.DocumentTouch&&document instanceof window.DocumentTouch||0<navigator.maxTouchPoints||0<window.navigator.msMaxTouchPoints;window.addEventListener?(document.addEventListener("click",addeventatc.documentclick,!1),ate_touch_capable&&document.addEventListener("touchend",addeventatc.documentclick,!1)):window.attachEvent?(document.attachEvent("onclick",addeventatc.documentclick),ate_touch_capable&&document.attachEvent("ontouchend",addeventatc.documentclick)):document.onclick=function(){addeventatc.documentclick(event)},addeventReady(function(){addeventatc.initialize()});var flbckcnt=0,flbckint=setInterval(function(){15<=++flbckcnt||addeventatc.isloaded()?clearInterval(flbckint):addeventatc.initialize()},300),nlbckcnt=0,nlbckint=setInterval(function(){15<=++nlbckcnt?clearInterval(nlbckint):addeventatc.notloadedcnt()},300);

/**
 * @file
 * Logic for Accesibility
 * page.
 */
(function ($, Drupal, window, document) {
  // ADA SKIP NAV
  $(document).ready(function () {
    $(document).on('focus', '#skipLink', function () {
      var burger = document.getElementById('brg-tr');
      burger.checked = false;
      $("div.sw-skipnav-outerbar").animate({
        marginTop: "0px"
      }, 500);
    });

    $(document).on('blur', '#skipLink', function () {
      $("div.sw-skipnav-outerbar").animate({
        marginTop: "-40px"
      }, 500);
    });

    // Add tabindex to all Drawer hidden input checbox using label as trigger
    $("label.dr-h").each(function(index, element){
      $(".dr-h").attr("tabindex","0");

      $(this).keydown(function (e) {
        if (e.keyCode == 13) { //enter key
          e.preventDefault();
          this.click();
        }
      });
    })

    // Add tabindex to all label used as checkbox triggers
    $(".form-checkboxes label").each(function(index, element){
      $(this).attr("tabindex","0");

      $(this).keydown(function (e) {
        if (e.keyCode == 13) { //enter key
          e.preventDefault();
          this.click();
        }
      });
    })

    // Change the backround color focus of event posts when accessed via keyboard
    $(document).on('focusin', '.post-featured-item-wrapper', function () {
      $(this).css("background-color", "#1871bd");
      $(this).find('.di .di-tt').attr("tabindex","-1");
    });
    $(document).on('focusout', '.post-featured-item-wrapper', function () {
      $(this).css("background-color", "#ffffff");
    });
    $(document).on('mouseover', '.post-featured-item-wrapper', function () {
      $(this).css("background-color", "#1871bd");
    });
    $(document).on('mouseout', '.post-featured-item-wrapper', function () {
      $(this).css("background-color", "#ffffff");
    });

    // Always focus on modal on "mailto:" link click
    $('a[href^="mailto:"]').on('click', function(e){
      $('button.md-cb').focus();
    });

    $(document).on('keydown', function(e) {
      var target = e.target;
      var shiftPressed = e.shiftKey;
      // If TAB key pressed
      if (e.keyCode == 9) {
        // If inside a Modal dialog (determined by attribute role="dialog")
        if ($(target).parents('[role=dialog]').length) {
          // Find first or last input element in the dialog parent (depending on whether Shift was pressed).
          // Input elements must be visible, and can be Input/Select/Button/Textarea.
          var borderElem = shiftPressed ?
            $(target).closest('[role=dialog]').find('input:visible,select:visible,button:visible,textarea:visible').first()
            :
            $(target).closest('[role=dialog]').find('input:visible,select:visible,button:visible,textarea:visible').last();
          if ($(borderElem).length) {
            if ($(target).is($(borderElem))) {
              return false;
            } else {
              return true;
            }
          }
        }
      }
      return true;
    });

    iFrameResize({
      log : false, // disable console logging
      scrolling: true, // enable scrolling
      sizeHeight: true, // Enable resize of iframe to content height
      autoResize: true // Enable changes to window or DOM cause iFrame to resize to the new content size
    });

  });
})(jQuery, Drupal, this, this.document);
