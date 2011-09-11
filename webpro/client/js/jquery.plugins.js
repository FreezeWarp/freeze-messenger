/* jQuery, jQueryUI, and Javascipt Plugins File
 * Below are several mini-libaries bundled into one file. If any author has issues with their software being included, the means used to attribute their work, or would otherwise like to contact me, email me at <josephtparsons@gmail.com>.
 * The copyright of each piece is listed directly above the section. It should be easy enough to distinguish between sections. */










// ######################################################################################################### //
/* Start jQuery Cookie Extension */

/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Documentation: https://github.com/carhartl/jquery-cookie/blob/master/README.rdoc
 * Source: https://github.com/carhartl/jquery-cookie/blob/master/jquery.cookie.js
 *
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









// ######################################################################################################### //
/* START jQuery Context Menu */

// jQuery Context Menu Plugin
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
//
// More info: http://abeautifulsite.net/2008/09/jquery-context-menu-plugin/
//
// Terms of Use
//
// This plugin is dual-licensed under the GNU General Public License
//   and the MIT License and is copyright A Beautiful Site, LLC.
//
//
//
// Version 1.01-FIM3 (August 19th, 2011)
//
// Joseph T. Parsons
// (http://www.josephtparsons.com/)
//
// Added: Alternate Menu Support (single left click icon; see Google Docs, Google Music, etc.)
// Removed: Ability to Disable Entries
// Removed: Some Annoying Hackish Code
// Changed: Core Menu Lives in Seperate Function (not in jQuery Namespace)

if(jQuery)(function() {
  $.extend($.fn, {

    contextMenu: function(o, callback) {
      // Defaults
      if (o.menu == undefined) return false;
      if (o.inSpeed == undefined) o.inSpeed = 150;
      if (o.outSpeed == undefined) o.outSpeed = 75;

      // 0 needs to be -1 for expected results (no fade)
      if (o.inSpeed == 0) o.inSpeed = -1;
      if (o.outSpeed == 0) o.outSpeed = -1;


      $(this).each(function() { // Loop each context menu
        var el = $(this);


        $(this).unbind('mousedown').unbind('mouseup').unbind('keydown'); // Disable action (cleanup)


        // Add contextMenu class
        $('#' + o.menu).addClass('contextMenu');


        if (o.altMenu) {
          $(this).find('.menuTrigger').remove(); // Remove Menu Trigger (cleanup)
          $(this).append('<span class="menuTrigger" tabindex="1"><span class="ui-icon ui-icon-grip-diagonal-se"></span></span>');

          $(this).find('.menuTrigger').click(function() {
            contextMenuSub(false, o, $(el).find('.menuTrigger'), $(el).find('.menuTrigger').offset(), callback, $(this).parent());
          });

          $(this).find('.menuTrigger').keyup(function(event) {
            if (event.keyCode == 13) $(this).click();
          });
        }
        else {
          $(this).mousedown(function(e) { // Simulate a true right clickasync
            e.preventDefault();
            e.stopPropagation();

            $(this).mouseup(function(e) {
              e.preventDefault();

              contextMenuSub(e, o, el, $(el).offset(), callback, $(this));
            });
          });

          $(this).keyup(function(e) {
            if (e.which === 93) {
              contextMenuSub(false, o, el, $(el).offset(), callback, $(this));
            }
          });

          $(el).add($('ul.contextMenu')).bind('contextmenu', function() { // Disable browser context menu (requires both selectors to work in IE/Safari + FF/Chrome)
            return false;
          });
        }

        // Disable text selection
        if ($.browser.mozilla) {
          $('#' + o.menu).each(function() {
            $(this).css({'MozUserSelect' : 'none'});
          });
        }
        else if ($.browser.msie) {
          $('#' + o.menu).each(function() {
            $(this).bind('selectstart.disableTextSelect', function() {
              return false;
            });
          });
        }
        else {
          $('#' + o.menu).each(function() {
            $(this).bind('mousedown.disableTextSelect', function() {
              return false;
            });
          });
        }
      });

      return $(this);
    },
  });
})(jQuery);


function contextMenuSub(e, o, el, offset, callback, srcElement) {
  if (e) {
    e.stopPropagation();

    $(this).unbind('mouseup');
  }
  else {
    e = {
      button: 2,
      pageX : offset.left + $(el).width(),
      pageY : offset.top + $(el).height(),
      altMenu : true
    }
  }


  if (e.button == 2) {
    $(".contextMenu").hide(); // Hide context menus that may be showing
    var menu = $('#' + o.menu); // Get this context menu


    // Detect mouse position
    var d = {}, x, y;

    if (self.innerHeight) {
      d.pageYOffset = self.pageYOffset;
      d.pageXOffset = self.pageXOffset;
      d.innerHeight = self.innerHeight;
      d.innerWidth = self.innerWidth;
    }
    else if (document.documentElement && document.documentElement.clientHeight) {
      d.pageYOffset = document.documentElement.scrollTop;
      d.pageXOffset = document.documentElement.scrollLeft;
      d.innerHeight = document.documentElement.clientHeight;
      d.innerWidth = document.documentElement.clientWidth;
    }
    else if (document.body) {
      d.pageYOffset = document.body.scrollTop;
      d.pageXOffset = document.body.scrollLeft;
      d.innerHeight = document.body.clientHeight;
      d.innerWidth = document.body.clientWidth;
    }

    x = (e.pageX ? e.pageX : e.clientX + d.scrollLeft);
    y = (e.pageY ? e.pageY : e.clientY + d.scrollTop);



    $(document).unbind('click'); // Remove any other click bindings on the document.

    window.restrictFocus = 'contextMenu'; // Prevent the keydown event from changing anything more than it should.

    $(menu).css({ top: y, left: x }).attr('data-visible', 'true').fadeIn(o.inSpeed); // Show the menu.



    $(menu).find('li').focus(function() { // When an element is granted focus below, give it the hover class.
      $(this).addClass('hover');
    });$(menu).find('li').blur(function() { // When an element loses focus below, remove hover class.
      $(this).removeClass('hover');
    });



    $(menu).find('a').mouseover(function() { // Mouse Navigation
      $(menu).find('li.hover').blur();
      $(this).parent().focus();
    }).mouseout(function() {
      $(menu).find('li.hover').blur();
    });



    $(document).keydown(function(e) { // Keyboard Navigation
      switch(e.keyCode) {
        case 38: // Up
        if ($(menu).find('li.hover').size() === 0) { $(menu).find('li:last').focus(); } // No item has focus.
        else {
          $(menu).find('li.hover').blur().prevAll('li').eq(0).focus(); // Add to the prev element (if it was the last, the focus will just be removed).

          if ($(menu).find('li.hover').size() === 0) { $(menu).find('li:last').focus(); } // Focus removed; add to the last.
        }

        return false;
        break;

        case 40: // Down
        case 9: // Tab
        if ($(menu).find('li.hover').size() === 0) { $(menu).find('li:first').focus(); } // No item has focus.
        else {
          $(menu).find('li.hover').blur().nextAll('li').eq(0).focus(); // Add to the prev element (if it was the last, the focus will just be removed).

          if ($(menu).find('li.hover').size() === 0) { $(menu).find('li:first').focus(); } // Focus removed; add to the first.
        }

        return false;
        break;

        case 13: // Enter
        $(menu).find('li.hover a').trigger('click');
        break;

        case 27: // Escape
        $(document).trigger('click');
        break
      }
    });



    // When items are selected
    $('#' + o.menu).find('a').unbind('click');

    $('#' + o.menu).find('li a').click(function() {
      $(document).unbind('click').unbind('keydown');
      $(".contextMenu").hide();

      // Callback
      if (callback) {
        callback($(this).attr('data-action'), $(srcElement), {x: x - offset.left, y: y - offset.top, docX: x, docY: y});
      }

      return false;
    });



    // Hide bindings
    setTimeout(function() { // Delay for Mozilla; TODO: Confirm still a problem
      $(document).click(function() {
        $(document).unbind('click').unbind('keydown');
        $(menu).removeAttr('data-visible').fadeOut(o.outSpeed);

        window.restrictFocus = false;

        return false;
      });
    }, 0);
  }

  return false;
}

/* END jQuery Context Menu */
// ######################################################################################################### //







// ######################################################################################################### //
/* START jQuery Tooltip #1 */

 /*
 * TipTip (Original)
 * Copyright 2010 Drew Wilson
 * www.drewwilson.com
 * code.drewwilson.com/entry/tiptip-jquery-plugin
 *
 * Version 1.3   -   Updated: Mar. 23, 2010
 *
 * This Plug-In will create a custom tooltip to replace the default
 * browser tooltip. It is extremely lightweight and very smart in
 * that it detects the edges of the browser window and will make sure
 * the tooltip stays within the current window size. As a result the
 * tooltip will adjust itself to be displayed above, below, to the left
 * or to the right depending on what is necessary to stay within the
 * browser window. It is completely customizable as well via CSS.
 *
 * This TipTip jQuery plug-in is dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

/* TipTip (Modified)
 * Version 1.3 Modified June 26th, 2011
 * Joseph T. Parsons
 *
 * Modified to support live() handler, eliminate code unneeded for FIM (e.g. click handlers). */

(function($) {
  $.fn.tipTip = function(options) {
    var defaults = {
      maxWidth: "200px",
      edgeOffset: 3,
      defaultPosition: "bottom",
      delay: 400,
      fadeIn: 200,
      fadeOut: 200,
      attribute: "title",
      content: false, // HTML or String to fill TipTIp with
      enter: function() {return false;},
      exit: function() {return false;}
    };
    var opts = $.extend(defaults, options);

    // Setup tip tip elements and render them to the DOM
    if ($("#tiptip_holder").length <= 0) {
      var tiptip_holder = $('<div id="tiptip_holder" style="max-width:'+ opts.maxWidth +';"></div>');
      var tiptip_content = $('<div id="tiptip_content"></div>');
      var tiptip_arrow = $('<div id="tiptip_arrow"></div>');
      $("body").append(tiptip_holder.html(tiptip_content).prepend(tiptip_arrow.html('<div id="tiptip_arrow_inner"></div>')));
    }
    else {
      var tiptip_holder = $("#tiptip_holder");
      var tiptip_content = $("#tiptip_content");
      var tiptip_arrow = $("#tiptip_arrow");
    }



    var timeout = false;


    $(this).die('mouseover');
    $(this).die('mouseout');

    $(this).live({
      mouseover : function() {
        if (opts.content) {
          var org_title = opts.content;
        }
        else {
          var org_title = $(this).attr(opts.attribute);
        }

        opts.enter.call(this);
        tiptip_content.html(org_title);
        tiptip_holder.hide().removeAttr("class").css("margin","0");
        tiptip_arrow.removeAttr("style");

        var top = parseInt($(this).offset()['top']);
        var left = parseInt($(this).offset()['left']);
        var org_width = parseInt($(this).outerWidth());
        var org_height = parseInt($(this).outerHeight());
        var tip_w = tiptip_holder.outerWidth();
        var tip_h = tiptip_holder.outerHeight();
        var w_compare = Math.round((org_width - tip_w) / 2);
        var h_compare = Math.round((org_height - tip_h) / 2);
        var marg_left = Math.round(left + w_compare);
        var marg_top = Math.round(top + org_height + opts.edgeOffset);
        var t_class = "";
        var arrow_top = "";
        var arrow_left = Math.round(tip_w - 12) / 2;

        if (opts.defaultPosition == "bottom") {
          t_class = "_bottom";
        }
        else if (opts.defaultPosition == "top") {
          t_class = "_top";
        }
        else if (opts.defaultPosition == "left") {
          t_class = "_left";
        }
        else if (opts.defaultPosition == "right") {
          t_class = "_right";
        }

        var right_compare = (w_compare + left) < parseInt($(window).scrollLeft());
        var left_compare = (tip_w + left) > parseInt($(window).width());

        if ((right_compare && w_compare < 0) || (t_class == "_right" && !left_compare) || (t_class == "_left" && left < (tip_w + opts.edgeOffset + 5))) {
          t_class = "_right";
          arrow_top = Math.round(tip_h - 13) / 2;
          arrow_left = -12;
          marg_left = Math.round(left + org_width + opts.edgeOffset);
          marg_top = Math.round(top + h_compare);
        }
        else if ((left_compare && w_compare < 0) || (t_class == "_left" && !right_compare)) {
          t_class = "_left";
          arrow_top = Math.round(tip_h - 13) / 2;
          arrow_left =  Math.round(tip_w);
          marg_left = Math.round(left - (tip_w + opts.edgeOffset + 5));
          marg_top = Math.round(top + h_compare);
        }

        var top_compare = (top + org_height + opts.edgeOffset + tip_h + 8) > parseInt($(window).height() + $(window).scrollTop());
        var bottom_compare = ((top + org_height) - (opts.edgeOffset + tip_h + 8)) < 0;

        if (top_compare || (t_class == "_bottom" && top_compare) || (t_class == "_top" && !bottom_compare)) {
          if (t_class == "_top" || t_class == "_bottom") {
            t_class = "_top";
          }
          else {
            t_class = t_class+"_top";
          }
          arrow_top = tip_h;
          marg_top = Math.round(top - (tip_h + 5 + opts.edgeOffset));
        }
        else if (bottom_compare | (t_class == "_top" && bottom_compare) || (t_class == "_bottom" && !top_compare)) {
          if (t_class == "_top" || t_class == "_bottom") {
            t_class = "_bottom";
          }
          else {
            t_class = t_class+"_bottom";
          }
          arrow_top = -12;
          marg_top = Math.round(top + org_height + opts.edgeOffset);
        }

        if (t_class == "_right_top" || t_class == "_left_top") {
          marg_top = marg_top + 5;
        }
        else if (t_class == "_right_bottom" || t_class == "_left_bottom") {
          marg_top = marg_top - 5;
        }

        if (t_class == "_left_top" || t_class == "_left_bottom") {
          marg_left = marg_left + 5;
        }

        tiptip_arrow.css({"margin-left": arrow_left+"px", "margin-top": arrow_top+"px"});
        tiptip_holder.css({"margin-left": marg_left+"px", "margin-top": marg_top+"px"}).attr("class","tip"+t_class);

        if (timeout) {
          clearTimeout(timeout);
        }
        timeout = setTimeout(function() {
          tiptip_holder.stop(true,true).fadeIn(opts.fadeIn);
          return false;
        }, opts.delay);

        return false;
      },
      mouseout: function() {
        opts.exit.call(this);
        if (timeout) {
          clearTimeout(timeout);
        }

        tiptip_holder.fadeOut(opts.fadeOut);
        return false;
      }
    });

    return false;
  }

  return false;
})(jQuery);

/* END jQuery Tooltip #1 */
// ######################################################################################################### //







// ######################################################################################################### //
/* START jQuery Tooltip #2 */

/* EZPZ Tooltip v1.0
 * Copyright (c) 2009 Mike Enriquez, http://theezpzway.com
 * Released under the MIT License */

/* EZPZ Tooltip v1.0 Modified June 23rd, 2011
 * Joseph T. Parsons
 *
 * Modified to support live() handler, eliminate code unneeded for FIM. */
(function($){
  $(this).die('mouseover');
  $(this).die('mousemove');
  $(this).die('mouseout');

  $.fn.ezpz_tooltip = function(options) {
    var settings = $.extend({}, $.fn.ezpz_tooltip.defaults, options);
    var content = $("#" + settings.contentId);

    $(this).live({
      mouseover : function() {
        content = $("#" + settings.contentId);
        settings.beforeShow(content, $(this));
      },
      mousemove : function(e) {
        var contentInfo = getElementDimensionsAndPosition(content),
          targetInfo = getElementDimensionsAndPosition($(this));
        contentInfo = keepInWindow($.fn.ezpz_tooltip.positions[settings.contentPosition](contentInfo, e.pageX, e.pageY, settings.offset, targetInfo));

        content.css('top', contentInfo['top']);
        content.css('left', contentInfo['left']);

        settings.showContent(content);
      },
      mouseout : function() {
        settings.hideContent(content);
        settings.afterHide();
      }
    });

    function getElementDimensionsAndPosition(element) {
      var height = $(element).outerHeight(true),
        width = $(element).outerWidth(true),
        top = $(element).offset().top,
        left = $(element).offset().left,
        info = new Array();

      // Set dimensions
      info['height'] = height;
      info['width'] = width;

      // Set position
      info['top'] = top;
      info['left'] = left;

      return info;
    };

    function keepInWindow(contentInfo){
      var windowWidth = $(window).width();
      var windowTop = $(window).scrollTop();
      var output = new Array();

      output = contentInfo;

      if (contentInfo['top'] < windowTop) { // Top edge is too high
        output['top'] = windowTop;
      }
      if ((contentInfo['left'] + contentInfo['width']) > windowWidth) { // Right edge is past the window
        output['left'] = windowWidth - contentInfo['width'];
      }
      if (contentInfo['left'] < 0) { // Left edge is too far left
        output['left'] = 0;
      }

      return output;
    };
  };

  $.fn.ezpz_tooltip.positionContent = function(contentInfo, mouseX, mouseY, offset, targetInfo) {
    contentInfo['top'] = mouseY - offset - contentInfo['height'];
    contentInfo['left'] = mouseX + offset;

    return contentInfo;
  };

  $.fn.ezpz_tooltip.positions = {
    aboveRightFollow: function(contentInfo, mouseX, mouseY, offset, targetInfo) {
      contentInfo['top'] = mouseY - offset - contentInfo['height'];
      contentInfo['left'] = mouseX + offset;

      return contentInfo;
    },

    belowRightFollow: function(contentInfo, mouseX, mouseY, offset, targetInfo) {
      contentInfo['top'] = mouseY + offset;
      contentInfo['left'] = mouseX + offset;

      return contentInfo;
    }
  };


  $.fn.ezpz_tooltip.defaults = {
    contentPosition: 'aboveRightFollow',
    stayOnContent: false,
    offset: 10,
    contentId: "",
    beforeShow: function(content) {return false;},
    showContent: function(content) {
      content.show();
      return false;
    },
    hideContent: function(content) {
      content.hide();
      return false;
    },
    afterHide: function() {return false;}
  };

})(jQuery);

/* END jQuery Tooltip #2 */
// ######################################################################################################### //








// ######################################################################################################### //
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
// ######################################################################################################### //









// ######################################################################################################### //
/* Start Youtube Plugin */

/**
 * =====================================================
 *  jQTubeUtil - jQuery YouTube Search Utility
 * =====================================================
 *  Version: 0.9.0 (11th September 2010)
 *  Author: Nirvana Tikku (ntikku@gmail.com)
 *
 *  Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 *  Documentation:
 *    http://www.tikku.com/jquery-jQTubeUtil-util
 * =====================================================
 *
 *  The jQTubeUtil Utility is a wrapper for the YouTube
 *  GDATA API and is built ontop of jQuery. The search
 *  utility provides the following functionality -
 *
 *  BASIC SEARCH:
 * #####################################################
 *
 *  jQTubeUtil.search("some keywords", function(response){})
 *
 *  jQTubeUtil.search({
 *      q: "some keywords",
 *      time: jQTubeUtil.getTimes()[pick one],
 *      orderby: jQTubeUtil.getOrders()[pick one],
 *      max-results: #
 *  },function(response){});
 *
 *  FEED SEARCH:
 * #####################################################
 *
 *  jQTubeUtil.mostViewed(function(response){});
 *
 *  jQTubeUtil.mostRecent(function(response){});
 *
 *  jQTubeUtil.mostPopular(function(response){});
 *
 *  jQTubeUtil.topRated(function(response){});
 *
 *  jQTubeUtil.topFavs(function(response){});
 *
 *  jQTubeUtil.related(videoID,function(response){});
 *
 *
 *   SUGGESTION SEARCH:
 * #####################################################
 *
 *  jQTubeUtil.suggest("keywords", function(response){});
 *
 *  SEARCH RESPONSE OBJECT:
 * #####################################################
 *
 *  Response = {
 *       version: String,
 *       searchURL: String,
 *       videos: Array, // of YouTubeVideo's (see below)
 *   <! if search/feed, then the following attrs are present !>
 *       startIndex: String,
 *       itemsPerPage: String,
 *       totalResults: String
 *   <!  end search/feed feed attrs present !>
 *  }
 *
 *  FRIENDLY VIDEO OBJECT:
 * #####################################################
 *
 *  YouTubeVideo = {
 *      videoId: String,
 *      title: String,
 *      updated: String || undefined,
 *      thumbs: Array || undefined,
 *      duration: Number || undefined, (seconds)
 *      favCount: Number || undefined,
 *      viewCount: String || undefined,
 *      category: String || undefined,
 *      categoryLabel: String || undefined,
 *      description: String || undefined,
 *      keywords: String || undefined (comma sep words),
 *      unavailAttributes: Array
 *  }
 *
 */

;jQTubeUtil = (function($){ /* singleton */

        var f = function(){};
        var p = f.prototype;

        // Constants, Private Scope
        var MaxResults = 10,
                StartPoint = 1,
                // URLs
                BaseURL = "http://gdata.youtube.com",
                FeedsURL = BaseURL + "/feeds/api",
                VideoURL = FeedsURL + "/videos/",
                SearchURL = FeedsURL + "/videos",
                StandardFeedsURL = FeedsURL + "/standardfeeds",
                MostViewed = StandardFeedsURL + "/most_viewed"
                MostPopular = StandardFeedsURL + "/most_popular",
                MostRecent = StandardFeedsURL + "/most_recent",
                TopRated = StandardFeedsURL + "/top_rated",
                TopFavs = StandardFeedsURL + "/top_favorites",
                RecentlyFeatured = StandardFeedsURL + "/recently_featured",
                SuggestURL = "http://suggestqueries.google.com/complete/search",
                // Settings
                Times = ["today","this_week","this_month","all_time"],
                OrderBy = ["relevance", "published", "viewCount", "rating"],
                Categories = ["Film","Autos","Music","Animals","Sports","Travel","Shortmov","Videoblog","Games","Comedy","People","News","Entertainment","Education","Howto","Nonprofit","Tech"];

        // Settings _required_ for search
        var SearchDefaults = {
                "q": "",
                "orderby": OrderBy[2],
                "time": Times[3],
                "max-results": MaxResults
        };

        // The Feed URL structure _requires_ these
        var CoreDefaults = {
                "key": "", //"", /** NEEDS TO BE SET **/
                "format": 5, // embeddable
                "alt": "json",
                "callback": "?"
        };

        // The Autocomplete utility _requires_ these
        var SuggestDefaults = {
                hl: "en",
                ds: "yt",
                client: "youtube",
                hjson: "t",
                cp: 1
        };

        /**
         * Initialize the jQTubeUtil utility
         */
        p.init = function(options){
                if(!options.key) throw "jQTubeUtil requires a key!";
                CoreDefaults.key = options.key;
                if(options.orderby)
                        SearchDefaults.orderby = options.orderby;
                if(options.time)
                        SearchDefaults.time = options.time;
                if(options.maxResults)
                        SearchDefaults["max-results"] = MaxResults = options.maxResults;
                if(options.lang)
                        SuggestDefaults.hl = options.lang;
        };

        /** public method to get available time filter options */
        p.getTimes = function(){return Times;};

        /** public method to get available order filter options */
        p.getOrders = function(){return OrderBy;};

        /** public method to get available category filter options */
        p.getCategories = function(){return Categories;};

        /**
                 * Autocomplete utility returns array of suggestions
         * @param input - string
         * @param callback - function
         */
        p.suggest = function(input, callback){
                var opts = {q: encodeURIComponent(input)};
                var url = _buildURL(SuggestURL,
                        $.extend({}, SuggestDefaults, opts)
               );
                $.ajax({
                        type: "GET",
                        dataType: "json",
                        url: url,
                        success: function(xhr){
                                var suggestions = [], res = {};
                                for(entry in xhr[1]){
                                        suggestions.push(xhr[1][entry][0]);
                                }
                                res.suggestions = suggestions;
                                res.searchURL = url;
                                if(typeof(callback) == "function"){
                                        callback(res);
                                        return;
                                }
                        }
                });
        };

        /**
         * This function is the public method
         * provided to the user to perform a
         * keyword based search
         * @param input
         * @param cb
         */
        p.search = function(input, cb, category){
                if (typeof(input) == "string")
                        input = { "q" : encodeURIComponent(input) };
                if (null != category)
                        category = {"category" : category};
                else
                        category = {};
                return _search($.extend({}, SearchDefaults, input,category), cb);
        };

        /** Get a particular video via VideoID */
        p.video = function(vid, cb){
                return _request(VideoURL+vid+"?alt=json", cb);
        };

        /** Get related videos for a VideoID; ex. http://gdata.youtube.com/feeds/api/videos/ZTUVgYoeN_b/related?v=2 */
        p.related = function(vid, cb){
            return _request(VideoURL+vid+"/related?alt=json", cb);
        };

        /** Most Viewed Feed */
        p.mostViewed = function(incoming, callback){
                return _getFeedRequest(MostViewed, getOptions(incoming, true), callback);
        };

        /** Most Recent Feed */
        p.mostRecent = function(incoming, callback){
                return _getFeedRequest(MostRecent, getOptions(incoming, false), callback);
        };

        /** Most Popular Feed */
        p.mostPopular = function(incoming, callback){
                return _getFeedRequest(MostPopular, getOptions(incoming, true), callback);
        };

        /** Top Rated Feed */
        p.topRated = function(incoming, callback){
                return _getFeedRequest(TopRated, getOptions(incoming, true), callback);
        };

        /** Top Favorited Feed */
        p.topFavs = function(incoming, callback){
                return _getFeedRequest(TopFavs, getOptions(incoming, true), callback);
        };

        /**
         * Get a feeds request by specifying the URL
         * the options and the callback
         */
        function _getFeedRequest(baseURL, options, callback){
                var reqUrlParams = {
                        "max-results": options.max || MaxResults,
                        "start-index": options.start || StartPoint
                };
                if(options.time) reqUrlParams.time = options.time;
                var url = _buildURL(baseURL, reqUrlParams);
                return _request(url, options.callback || callback);
        };

        /**
         * Method to get the options for a standard
         * feed that is then utilized in the URL
         * building process
         */
        function getOptions(arg, hasTime){
                switch(typeof(arg)){
                        case "function":
                                return {
                                        callback: arg,
                                        time: undefined
                                };
                        case "object":
                                var ret = {
                                        max: arg.max,
                                        start: arg['start-index']
                                };
                                if(hasTime) ret.time = arg.time;
                                return ret;
                        default: return {}; break;
                }
        };

        /**
         * This function builds the URL and makes
         * the search request
         * @param options
         * @param callback
         */
        function _search(options, callback){
                var URL = _buildURL(SearchURL, options);
                return _request(URL, callback);
        };

        /**
         * This method makes the actual JSON request
         * and builds the results that are returned to
         * the callback
         */
        function _request(url, callback){
                var res = {};
                $.ajax({
                        type: "GET",
                        dataType: "json",
                        url: url,
                        success: function(xhr){
                                if((typeof(xhr) == "undefined")
                                        ||(xhr == null)) return;
                                var videos = [];
                                if(xhr.feed){
                                        var feed = xhr.feed;
                                        var entries = xhr.feed.entry;
                                        for(entry in entries)
                                                videos.push(new YouTubeVideo(entries[entry]));
                                        res.startIndex = feed.openSearch$startIndex.$t;
                                        res.itemsPerPage = feed.openSearch$itemsPerPage.$t;
                                        res.totalResults = feed.openSearch$totalResults.$t;
                                } else {
                                        videos.push(new YouTubeVideo(xhr.entry));
                                }
                                res.version = xhr.version;
                                res.searchURL = url;
                                res.videos = videos;
                                if(typeof(callback) == "function") {
                                        callback(res); // pass the response obj
                                        return;
                                }

                        },
                        error: function(e){
                                throw Exception("couldn't fetch YouTube request : "+url+" : "+e);
                        }
                });
                return res;
        };

        /**
         * This method builds the url utilizing a JSON
         * object as the request param names and values
         */
        function _buildURL(root, options){
                var ret = "?", k, v, first=true;
                var opts = $.extend({}, options, CoreDefaults);
                for(o in opts){
                        k = o;  v = opts[o];
                        ret += (first?"":"&")+k+"="+v;
                        first=false;
                }
                return root + ret;
        };

        /**
         * Represents the object that transposes the
         * YouTube video entry from the JSON response
         * into a usable object
         */
        var YouTubeVideo = function(entry){
                var unavail = [];
                var id = entry.id.$t;
                var start = id.lastIndexOf('/')+1;
                var end = id.length;
                // set values
                this.videoId = id.substring(start, end);
                this.entry = entry; // give access to the entry itself
                this.title = entry.title.$t;
                try{ this.updated = entry.updated.$t; }catch(e){ unavail.push("updated"); }
                try{ this.thumbs = entry.media$group.media$thumbnail; }catch(e){ unavail.push("thumbs"); }
                try{ this.duration = entry.media$group.yt$duration.seconds; }catch(e){ unavail.push("duration"); }
                try{ this.favCount = entry.yt$statistics.favoriteCount; }catch(e){ unavail.push("favCount"); }
                try{ this.rating = entry.gd$rating; }catch(e){ alert(e); unavail.push("rating"); }
                try{ this.viewCount = entry.yt$statistics.viewCount; }catch(e){ unavail.push("viewCount"); }
                try{ this.category = entry.media$group.media$category[0].$t; }catch(e){ unavail.push("category"); }
                try{ this.categoryLabel = entry.media$group.media$category[0].label; }catch(e){ unavail.push("categoryLabel"); }
                try{ this.description = entry.media$group.media$description.$t; }catch(e){ unavail.push("description"); }
                try{ this.keywords = entry.media$group.media$keywords.$t; }catch(e){ unavail.push("keywords"); }
                this.unavailAttributes = unavail; // so that the user can tell if a value isnt available
        };

        return new f();

})(jQuery);

// ######################################################################################################### //









// ######################################################################################################### //
/*
 * Start Tabbed Dialog
 * Based on http://forum.jquery.com/topic/combining-ui-dialog-and-tabs
 * Modified to Work by Joseph T. Parsons
 * Browser Status: Chrome (+), Firefox (?), IE 8 (?), IE 9 (?), Opera (?), Safari (?)
 */
$.fn.tabbedDialog = function (dialogOptions, tabOptions) {
  this.tabs(tabOptions);
  this.dialog(dialogOptions);

  var tabul = this.find('ul:first');
  this.parent().addClass('ui-tabs').prepend(tabul).draggable('option', 'handle', tabul);
  tabul.append($('a.ui-dialog-titlebar-close'));
  this.prev().remove();
  tabul.addClass('ui-dialog-titlebar');

  var titleId = $.ui.dialog.getTitleId(this);

  this.attr("tabIndex", -1).attr({
    role: "dialog",
    "aria-labelledby": titleId
  });


  // Make Only The Content of the Tab Tabbable
  this.bind("keydown.ui-dialog", function(event) {
    if (event.keyCode !== $.ui.keyCode.TAB) {
      return;
    }


    var tabbables = $(":tabbable", this).add("ul.ui-tabs-nav.ui-dialog-titlebar > li > a"),
      first = tabbables.filter(":first"),
      last  = tabbables.filter(":last");


    if (event.target === last[0] && !event.shiftKey) {
      first.focus(1);
      return false;
    }
    else if (event.target === first[0] && event.shiftKey) {
      last.focus(1);
      return false;
    }
  });



  // Give the First Element in the Dialog Focus
  var hasFocus = this.find( ":tabbable" );
  if ( !hasFocus.length ) {
    hasFocus = uiDialog.find( ".ui-dialog-buttonpane :tabbable" );
    if ( !hasFocus.length ) {
      hasFocus = uiDialog;
    }
  }
  hasFocus.eq( 0 ).focus();
}
/* End Tabbed Dialog */
// ######################################################################################################### //








// ######################################################################################################### //
/* Start Generic Notify
 * Joseph Todd Parsons
 * http://www.gnu.org/licenses/gpl.html */

var notify = {
  webkitNotifyRequest : function() {
    window.webkitNotifications.requestPermission();
  },

  webkitNotify : function(icon, title, notifyData) {
    if (window.webkitNotifications.checkPermission() > 0) {
      notify.webkitNotifyRequest();
    }
    else {
      notification = window.webkitNotifications.createNotification(icon, title, notifyData);
      notification.show();
    }
  },

  notify : function(text,header,id,id2) {
    if ($('#' + id + ' > #' + id + id2).html()) {
      // Do nothing
    }
    else {
      if ($('#' + id).html()) {
        $('#' + id).append('<br />' + text);
      }
      else {
        $.jGrowl('<div id="' + id + '"><span id="' + id + id2 + '">' + text + '</span></div>', {
          sticky: true,
          glue: true,
          header: header
        });
      }
    }
  }
}

/* End Generic Notify */
// ######################################################################################################### //











// ######################################################################################################### //

/* Start Dia -- Simplified jQueryUI Dialogues
 * Joseph T. Parsons
 * http://www.gnu.org/licenses/gpl.html */
var dia = {
  error : function(message) {
    $('<div style="display: none;"><span class="ui-icon ui-icon-alert" style="float: left; margin: 0 7px 20px 0;"></span>' + message + '</div>').dialog({
      title : 'Error',
      modal : true,
      buttons: {
        Close: function() {
          $(this).dialog("close");

          return false;
        }
      }
    });
  },

  info : function(message, title) {
    $('<div style="display: none;">' + message + '</div>').dialog({
      title : title,
      modal : true,
      buttons: {
        Okay : function() {
          $(this).dialog("close");

          return false;
        }
      }
    });
  },

  confirm : function(options) {
    $('<div id="dialog-confirm"><span class="ui-icon ui-icon-alert" style="float: left; margin: 0px 7px 20px 0px;"></span>' + options.text + '</div>').dialog({
      resizable: false,
      height: 240,
      modal: true,
      hide: "puff",
      buttons: {
        Confirm: function() {
          if (typeof options['true'] !== 'undefined') {
            options['true']();
          }

          $(this).dialog("close");
          return true;
        },
        Cancel: function() {
          if (typeof options['false'] !== 'undefined') {
            options['false']();
          }

          $(this).dialog("close");
          return false;
        }
      }
    });
  },

  // Supported options: autoShow (true), id, content, width (600), oF, cF
  full : function(options) {
    var ajax,
      autoOpen,
      windowWidth = document.documentElement.clientWidth,
      dialog,
      dialogOptions,
      tabsOptions,
      overlay,
      throbber;

    if (options.uri) {
      options.content = '<img src="images/ajax-loader.gif" align="center" />';

      ajax = true;
    }
    else if (!options.content) {
      console.log('No content found for dialog; exiting.');

      return false;
    }

    if (typeof options.autoOpen !== 'undefined' && options.autoOpen === false) {
      autoOpen = false;
    }
    else {
      autoOpen = true;
    }

    if (options.width > windowWidth) {
      options.width = windowWidth;
    }
    else if (!options.width) {
      options.widthwidth = 600;
    }

    if (!options.position) {
      options.position = 'center';
    }

    dialogOptions = {
      width: options.width,
      title: options.title,
      hide: "puff",
      modal: true,
      buttons : options.buttons,
      position : options.position,
      autoOpen: autoOpen,
      open: function() {
        if (typeof options.oF !== 'undefined') {
          options.oF();
        }

        return false;
      },
      close: function() {
        $('#' + options.id).empty().remove(); // Housecleaning, needed if we want the next dialouge to work properly.
        if (typeof options.cF !== 'undefined') {
          options.cF();
        }

        return false;
      }
    };

    tabsOptions = {
      selected : options.selectTab
    };


    dialog = $('<div style="display: none;" id="' + options.id +  '">' + options.content + '</div>').appendTo('body');



    if (ajax) {
      overlay = $('<div class="ui-widget-overlay"></div>').appendTo('body').width($(document).width()).height($(document).height());
      throbber = $('<img src="images/ajax-loader.gif" />').appendTo('body').css('position','absolute').offset({ left : (($(window).width() - 220) / 2), top : (($(window).height() - 19) / 2)});

      $.ajax({
        url : options.uri,
        type : "GET",
        timeout : 5000,
        cache : true,
        success : function(content) {
          overlay.empty().remove();
          throbber.empty().remove();

          dialog.html(content);

          if (options.tabs) {
            dialog.tabbedDialog(dialogOptions, tabsOptions);
          }
          else {
            dialog.dialog(dialogOptions);
          }

          windowDraw();

          return false;
        },
        error : function() {
          overlay.empty().remove();
          throbber.empty().remove();

          dialog.dialog('close');

          dia.error('Could not request dialog URI.');

          return false;
        }
      });
    }
    else {
      if (options.tabs) {
        dialog.tabbedDialog(dialogOptions, tabsOptions);
      }
      else {
        dialog.dialog(dialogOptions);
      }

      windowDraw();
    }
  }
};

/* End Dia -- Simplified jQueryUI Dialogues */
// ######################################################################################################### //