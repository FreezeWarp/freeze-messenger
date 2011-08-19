/* START jQuery Cookie Function */

/**
 * Create a cookie with the given name and value and other optional parameters.
 *
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Set the value of a cookie.
 * @example $.cookie('the_cookie', 'the_value', { expires: 7, path: '/', domain: 'jquery.com', secure: true });
 * @desc Create a cookie with all available options.
 * @example $.cookie('the_cookie', 'the_value');
 * @desc Create a session cookie.
 * @example $.cookie('the_cookie', null);
 * @desc Delete a cookie by passing null as value. Keep in mind that you have to use the same path and domain
 *       used when the cookie was set.
 *
 * @param String name The name of the cookie.
 * @param String value The value of the cookie.
 * @param Object options An object literal containing key/value pairs to provide optional cookie attributes.
 * @option Number|Date expires Either an integer specifying the expiration date from now on in days or a Date object.
 *                             If a negative value is specified (e.g. a date in the past), the cookie will be deleted.
 *                             If set to null or omitted, the cookie will be a session cookie and will not be retained
 *                             when the the browser exits.
 * @option String path The value of the path atribute of the cookie (default: path of page that created the cookie).
 * @option String domain The value of the domain attribute of the cookie (default: domain of page that created the cookie).
 * @option Boolean secure If true, the secure attribute of the cookie will be set and the cookie transmission will
 *                        require a secure protocol (like HTTPS).
 * @type undefined
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */

/**
 * Get the value of a cookie with the given name.
 *
 * @example $.cookie('the_cookie');
 * @desc Get the value of a cookie.
 *
 * @param String name The name of the cookie.
 * @return The value of the cookie.
 * @type String
 *
 * @name $.cookie
 * @cat Plugins/Cookie
 * @author Klaus Hartl/klaus.hartl@stilbuero.de
 */
jQuery.cookie = function(name, value, options) {
  if (typeof value != 'undefined') { // name and value given, set cookie
    options = options || {};
    if (value === null) {
      value = '';
      options.expires = -1;
    }
    var expires = '';
    if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
      var date;
      if (typeof options.expires == 'number') {
        date = new Date();
        date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
      } else {
        date = options.expires;
      }
      expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
    }
    // CAUTION: Needed to parenthesize options.path and options.domain
    // in the following expressions, otherwise they evaluate to undefined
    // in the packed version for some reason...
    var path = options.path ? '; path=' + (options.path) : '';
    var domain = options.domain ? '; domain=' + (options.domain) : '';
    var secure = options.secure ? '; secure' : '';
    document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
  } else { // only name given, get cookie
    var cookieValue = null;
    if (document.cookie && document.cookie != '') {
      var cookies = document.cookie.split(';');
      for (var i = 0; i < cookies.length; i++) {
        var cookie = jQuery.trim(cookies[i]);
        // Does this cookie string begin with the name we want?
        if (cookie.substring(0, name.length + 1) == (name + '=')) {
          cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
          break;
        }
      }
    }
    return cookieValue;
  }
};

/* END jQuery Cookie Functon */







/* START jQuery Non-Obfusicating Alert Box (Generic Implementation) */

/**
 * jGrowl 1.2.5
 *
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Written by Stan Lemon <stosh1985@gmail.com>
 * Last updated: 2009.12.15
 *
 * jGrowl is a jQuery plugin implementing unobtrusive userland notifications.  These
 * notifications function similarly to the Growl Framework available for
 * Mac OS X (http://growl.info).
 *
 * To Do:
 * - Move library settings to containers and allow them to be changed per container
 */

/* EZPZ Tooltip v1.0 Modified June 23rd, 2011
 * Joseph T. Parsons
 *
 * Eliminate code unneeded for FIM. */
(function($) {
  /** jGrowl Wrapper - Establish a base jGrowl Container for compatibility with older releases. **/
  $.jGrowl = function(m, o) {
    // To maintain compatibility with older version that only supported one instance we'll create the base container.
    if ($('#jGrowl').size() == 0)
      $('<div id="jGrowl"></div>').addClass((o && o.position) ? o.position : $.jGrowl.defaults.position).appendTo('body');

    // Create a notification on the container.
    $('#jGrowl').jGrowl(m,o);
  };


  /** Raise jGrowl Notification on a jGrowl Container **/
  $.fn.jGrowl = function(m, o) {
    if ($.isFunction(this.each)) {
      var args = arguments;

      return this.each(function() {
        var self = this;

        /** Create a jGrowl Instance on the Container if it does not exist **/
        if ($(this).data('jGrowl.instance') == undefined) {
          $(this).data('jGrowl.instance', $.extend(new $.fn.jGrowl(), { notifications: [], element: null, interval: null }));
          $(this).data('jGrowl.instance').startup(this);
        }

        /** Optionally call jGrowl instance methods, or just raise a normal notification **/
        if ($.isFunction($(this).data('jGrowl.instance')[m])) {
          $(this).data('jGrowl.instance')[m].apply($(this).data('jGrowl.instance'), $.makeArray(args).slice(1));
        } else {
          $(this).data('jGrowl.instance').create(m, o);
        }
      });
    };
  };

  $.extend($.fn.jGrowl.prototype, {

    /** Default JGrowl Settings **/
    defaults: {
      pool:       0,
      header:     '',
      group:      '',
      sticky:     false,
      position:         'top-right',
      glue:       'after',
      theme:      'default',
      themeState:     'highlight',
      corners:    '10px',
      check:      250,
      life:       3000,
      closeDuration:  'normal',
      openDuration:   'normal',
      easing:     'swing',
      closer:     true,
      closeTemplate: '&times;',
      closerTemplate: '<div>[ close all ]</div>',
      log:        function(e,m,o) {},
      beforeOpen:     function(e,m,o) {},
      afterOpen:        function(e,m,o) {},
      open:       function(e,m,o) {},
      beforeClose:    function(e,m,o) {},
      close:      function(e,m,o) {},
      animateOpen:    {
        opacity:  'show'
      },
      animateClose:   {
        opacity:  'hide'
      }
    },

    notifications: [],

    /** jGrowl Container Node **/
    element:  null,

    /** Interval Function **/
    interval:   null,

    /** Create a Notification **/
    create:   function(message, o) {
      var o = $.extend({}, this.defaults, o);

      this.notifications.push({ message: message, options: o });

      o.log.apply(this.element, [this.element,message,o]);
    },

    render:     function(notification) {
      var self = this;
      var message = notification.message;
      var o = notification.options;

      var notification = $(
        '<div class="jGrowl-notification ' + o.themeState + ' ui-corner-all' +
        ((o.group != undefined && o.group != '') ? ' ' + o.group : '') + '">' +
        '<div class="jGrowl-close">' + o.closeTemplate + '</div>' +
        '<div class="jGrowl-header">' + o.header + '</div>' +
        '<div class="jGrowl-message">' + message + '</div></div>'
      ).data("jGrowl", o).addClass(o.theme).children('div.jGrowl-close').bind("click.jGrowl", function() {
        $(this).parent().trigger('jGrowl.close');
      }).parent();


      /** Notification Actions **/
      $(notification).bind("mouseover.jGrowl", function() {
        $('div.jGrowl-notification', self.element).data("jGrowl.pause", true);
      }).bind("mouseout.jGrowl", function() {
        $('div.jGrowl-notification', self.element).data("jGrowl.pause", false);
      }).bind('jGrowl.beforeOpen', function() {
        if (o.beforeOpen.apply(notification, [notification,message,o,self.element]) != false) {
          $(this).trigger('jGrowl.open');
        }
      }).bind('jGrowl.open', function() {
        if (o.open.apply(notification, [notification,message,o,self.element]) != false) {
          if (o.glue == 'after') {
            $('div.jGrowl-notification:last', self.element).after(notification);
          } else {
            $('div.jGrowl-notification:first', self.element).before(notification);
          }

          $(this).animate(o.animateOpen, o.openDuration, o.easing, function() {
            // Fixes some anti-aliasing issues with IE filters.
            if ($.browser.msie && (parseInt($(this).css('opacity'), 10) === 1 || parseInt($(this).css('opacity'), 10) === 0))
              this.style.removeAttribute('filter');

            $(this).data("jGrowl").created = new Date();

            $(this).trigger('jGrowl.afterOpen');
          });
        }
      }).bind('jGrowl.afterOpen', function() {
        o.afterOpen.apply(notification, [notification,message,o,self.element]);
      }).bind('jGrowl.beforeClose', function() {
        if (o.beforeClose.apply(notification, [notification,message,o,self.element]) != false)
          $(this).trigger('jGrowl.close');
      }).bind('jGrowl.close', function() {
        // Pause the notification, lest during the course of animation another close event gets called.
        $(this).data('jGrowl.pause', true);
        $(this).animate(o.animateClose, o.closeDuration, o.easing, function() {
          $(this).remove();
          var close = o.close.apply(notification, [notification,message,o,self.element]);

          if ($.isFunction(close))
            close.apply(notification, [notification,message,o,self.element]);
        });
      }).trigger('jGrowl.beforeOpen');

      /** Optional Corners Plugin **/
      if (o.corners != '' && $.fn.corner != undefined) $(notification).corner(o.corners);

      /** Add a Global Closer if more than one notification exists **/
      if ($('div.jGrowl-notification:parent', self.element).size() > 1 &&
         $('div.jGrowl-closer', self.element).size() == 0 && this.defaults.closer != false) {
        $(this.defaults.closerTemplate).addClass('jGrowl-closer ui-state-highlight ui-corner-all').addClass(this.defaults.theme)
          .appendTo(self.element).animate(this.defaults.animateOpen, this.defaults.speed, this.defaults.easing)
          .bind("click.jGrowl", function() {
            $(this).siblings().trigger("jGrowl.beforeClose");

            if ($.isFunction(self.defaults.closer)) {
              self.defaults.closer.apply($(this).parent()[0], [$(this).parent()[0]]);
            }
          });
      };
    },

    /** Update the jGrowl Container, removing old jGrowl notifications **/
    update:  function() {
      $(this.element).find('div.jGrowl-notification:parent').each(function() {
        if ($(this).data("jGrowl") != undefined && $(this).data("jGrowl").created != undefined &&
           ($(this).data("jGrowl").created.getTime() + parseInt($(this).data("jGrowl").life))  < (new Date()).getTime() &&
           $(this).data("jGrowl").sticky != true &&
           ($(this).data("jGrowl.pause") == undefined || $(this).data("jGrowl.pause") != true)) {

          // Pause the notification, lest during the course of animation another close event gets called.
          $(this).trigger('jGrowl.beforeClose');
        }
      });

      if (this.notifications.length > 0 &&
         (this.defaults.pool == 0 || $(this.element).find('div.jGrowl-notification:parent').size() < this.defaults.pool))
        this.render(this.notifications.shift());

      if ($(this.element).find('div.jGrowl-notification:parent').size() < 2) {
        $(this.element).find('div.jGrowl-closer').animate(this.defaults.animateClose, this.defaults.speed, this.defaults.easing, function() {
          $(this).remove();
        });
      }
    },

    /** Setup the jGrowl Notification Container **/
    startup:  function(e) {
      this.element = $(e).addClass('jGrowl').append('<div class="jGrowl-notification"></div>');
      this.interval = setInterval(function() {
        $(e).data('jGrowl.instance').update();
      }, parseInt(this.defaults.check));

      if ($.browser.msie && parseInt($.browser.version) < 7 && !window["XMLHttpRequest"]) {
        $(this.element).addClass('ie6');
      }
    },

    /** Shutdown jGrowl, removing it and clearing the interval **/
    shutdown:   function() {
      $(this.element).removeClass('jGrowl').find('div.jGrowl-notification').remove();
      clearInterval(this.interval);
    },

    close:  function() {
      $(this.element).find('div.jGrowl-notification').each(function(){
        $(this).trigger('jGrowl.beforeClose');
      });
    }
  });

  /** Reference the Defaults Object for compatibility with older versions of jGrowl **/
  $.jGrowl.defaults = $.fn.jGrowl.prototype.defaults;

})(jQuery);

/* END jQuery Non-Obfusicating Alert Box (Generic Implementation) */