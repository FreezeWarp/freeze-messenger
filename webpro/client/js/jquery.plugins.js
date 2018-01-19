/* jQuery, jQueryUI, and Javascipt Plugins File
 * Whenever a write a function that could be used with other projects, I will include it here instead of a fim-*.js file.
 * Below are several mini-libaries bundled into one file. If any author has issues with their software being included, the means used to attribute their work, or would otherwise like to contact me, email me at <josephtparsons@gmail.com>.
 * The copyright of each piece is listed directly above the section. It should be easy enough to distinguish between sections. *



/*! jQuery UI - v1.11.4+CommonJS - 2015-08-28
* http://jqueryui.com
* Includes: widget.js
* Copyright 2015 jQuery Foundation and other contributors; Licensed MIT */

(function( factory ) {
    if ( typeof define === "function" && define.amd ) {

        // AMD. Register as an anonymous module.
        define([ "jquery" ], factory );

    } else if ( typeof exports === "object" ) {

        // Node/CommonJS
        factory( require( "jquery" ) );

    } else {

        // Browser globals
        factory( jQuery );
    }
}(function( $ ) {
    /*!
     * jQuery UI Widget 1.11.4
     * http://jqueryui.com
     *
     * Copyright jQuery Foundation and other contributors
     * Released under the MIT license.
     * http://jquery.org/license
     *
     * http://api.jqueryui.com/jQuery.widget/
     */


    var widget_uuid = 0,
        widget_slice = Array.prototype.slice;

    $.cleanData = (function( orig ) {
        return function( elems ) {
            var events, elem, i;
            for ( i = 0; (elem = elems[i]) != null; i++ ) {
                try {

                    // Only trigger remove when necessary to save time
                    events = $._data( elem, "events" );
                    if ( events && events.remove ) {
                        $( elem ).triggerHandler( "remove" );
                    }

                    // http://bugs.jquery.com/ticket/8235
                } catch ( e ) {}
            }
            orig( elems );
        };
    })( $.cleanData );

    $.widget = function( name, base, prototype ) {
        var fullName, existingConstructor, constructor, basePrototype,
            // proxiedPrototype allows the provided prototype to remain unmodified
            // so that it can be used as a mixin for multiple widgets (#8876)
            proxiedPrototype = {},
            namespace = name.split( "." )[ 0 ];

        name = name.split( "." )[ 1 ];
        fullName = namespace + "-" + name;

        if ( !prototype ) {
            prototype = base;
            base = $.Widget;
        }

        // create selector for plugin
        $.expr[ ":" ][ fullName.toLowerCase() ] = function( elem ) {
            return !!$.data( elem, fullName );
        };

        $[ namespace ] = $[ namespace ] || {};
        existingConstructor = $[ namespace ][ name ];
        constructor = $[ namespace ][ name ] = function( options, element ) {
            // allow instantiation without "new" keyword
            if ( !this._createWidget ) {
                return new constructor( options, element );
            }

            // allow instantiation without initializing for simple inheritance
            // must use "new" keyword (the code above always passes args)
            if ( arguments.length ) {
                this._createWidget( options, element );
            }
        };
        // extend with the existing constructor to carry over any static properties
        $.extend( constructor, existingConstructor, {
            version: prototype.version,
            // copy the object used to create the prototype in case we need to
            // redefine the widget later
            _proto: $.extend( {}, prototype ),
            // track widgets that inherit from this widget in case this widget is
            // redefined after a widget inherits from it
            _childConstructors: []
        });

        basePrototype = new base();
        // we need to make the options hash a property directly on the new instance
        // otherwise we'll modify the options hash on the prototype that we're
        // inheriting from
        basePrototype.options = $.widget.extend( {}, basePrototype.options );
        $.each( prototype, function( prop, value ) {
            if ( !$.isFunction( value ) ) {
                proxiedPrototype[ prop ] = value;
                return;
            }
            proxiedPrototype[ prop ] = (function() {
                var _super = function() {
                        return base.prototype[ prop ].apply( this, arguments );
                    },
                    _superApply = function( args ) {
                        return base.prototype[ prop ].apply( this, args );
                    };
                return function() {
                    var __super = this._super,
                        __superApply = this._superApply,
                        returnValue;

                    this._super = _super;
                    this._superApply = _superApply;

                    returnValue = value.apply( this, arguments );

                    this._super = __super;
                    this._superApply = __superApply;

                    return returnValue;
                };
            })();
        });
        constructor.prototype = $.widget.extend( basePrototype, {
            // TODO: remove support for widgetEventPrefix
            // always use the name + a colon as the prefix, e.g., draggable:start
            // don't prefix for widgets that aren't DOM-based
            widgetEventPrefix: existingConstructor ? (basePrototype.widgetEventPrefix || name) : name
        }, proxiedPrototype, {
            constructor: constructor,
            namespace: namespace,
            widgetName: name,
            widgetFullName: fullName
        });

        // If this widget is being redefined then we need to find all widgets that
        // are inheriting from it and redefine all of them so that they inherit from
        // the new version of this widget. We're essentially trying to replace one
        // level in the prototype chain.
        if ( existingConstructor ) {
            $.each( existingConstructor._childConstructors, function( i, child ) {
                var childPrototype = child.prototype;

                // redefine the child widget using the same prototype that was
                // originally used, but inherit from the new version of the base
                $.widget( childPrototype.namespace + "." + childPrototype.widgetName, constructor, child._proto );
            });
            // remove the list of existing child constructors from the old constructor
            // so the old child constructors can be garbage collected
            delete existingConstructor._childConstructors;
        } else {
            base._childConstructors.push( constructor );
        }

        $.widget.bridge( name, constructor );

        return constructor;
    };

    $.widget.extend = function( target ) {
        var input = widget_slice.call( arguments, 1 ),
            inputIndex = 0,
            inputLength = input.length,
            key,
            value;
        for ( ; inputIndex < inputLength; inputIndex++ ) {
            for ( key in input[ inputIndex ] ) {
                value = input[ inputIndex ][ key ];
                if ( input[ inputIndex ].hasOwnProperty( key ) && value !== undefined ) {
                    // Clone objects
                    if ( $.isPlainObject( value ) ) {
                        target[ key ] = $.isPlainObject( target[ key ] ) ?
                            $.widget.extend( {}, target[ key ], value ) :
                            // Don't extend strings, arrays, etc. with objects
                            $.widget.extend( {}, value );
                        // Copy everything else by reference
                    } else {
                        target[ key ] = value;
                    }
                }
            }
        }
        return target;
    };

    $.widget.bridge = function( name, object ) {
        var fullName = object.prototype.widgetFullName || name;
        $.fn[ name ] = function( options ) {
            var isMethodCall = typeof options === "string",
                args = widget_slice.call( arguments, 1 ),
                returnValue = this;

            if ( isMethodCall ) {
                this.each(function() {
                    var methodValue,
                        instance = $.data( this, fullName );
                    if ( options === "instance" ) {
                        returnValue = instance;
                        return false;
                    }
                    if ( !instance ) {
                        return $.error( "cannot call methods on " + name + " prior to initialization; " +
                            "attempted to call method '" + options + "'" );
                    }
                    if ( !$.isFunction( instance[options] ) || options.charAt( 0 ) === "_" ) {
                        return $.error( "no such method '" + options + "' for " + name + " widget instance" );
                    }
                    methodValue = instance[ options ].apply( instance, args );
                    if ( methodValue !== instance && methodValue !== undefined ) {
                        returnValue = methodValue && methodValue.jquery ?
                            returnValue.pushStack( methodValue.get() ) :
                            methodValue;
                        return false;
                    }
                });
            } else {

                // Allow multiple hashes to be passed on init
                if ( args.length ) {
                    options = $.widget.extend.apply( null, [ options ].concat(args) );
                }

                this.each(function() {
                    var instance = $.data( this, fullName );
                    if ( instance ) {
                        instance.option( options || {} );
                        if ( instance._init ) {
                            instance._init();
                        }
                    } else {
                        $.data( this, fullName, new object( options, this ) );
                    }
                });
            }

            return returnValue;
        };
    };

    $.Widget = function( /* options, element */ ) {};
    $.Widget._childConstructors = [];

    $.Widget.prototype = {
        widgetName: "widget",
        widgetEventPrefix: "",
        defaultElement: "<div>",
        options: {
            disabled: false,

            // callbacks
            create: null
        },
        _createWidget: function( options, element ) {
            element = $( element || this.defaultElement || this )[ 0 ];
            this.element = $( element );
            this.uuid = widget_uuid++;
            this.eventNamespace = "." + this.widgetName + this.uuid;

            this.bindings = $();
            this.hoverable = $();
            this.focusable = $();

            if ( element !== this ) {
                $.data( element, this.widgetFullName, this );
                this._on( true, this.element, {
                    remove: function( event ) {
                        if ( event.target === element ) {
                            this.destroy();
                        }
                    }
                });
                this.document = $( element.style ?
                    // element within the document
                    element.ownerDocument :
                    // element is window or document
                    element.document || element );
                this.window = $( this.document[0].defaultView || this.document[0].parentWindow );
            }

            this.options = $.widget.extend( {},
                this.options,
                this._getCreateOptions(),
                options );

            this._create();
            this._trigger( "create", null, this._getCreateEventData() );
            this._init();
        },
        _getCreateOptions: $.noop,
        _getCreateEventData: $.noop,
        _create: $.noop,
        _init: $.noop,

        destroy: function() {
            this._destroy();
            // we can probably remove the unbind calls in 2.0
            // all event bindings should go through this._on()
            this.element
                .unbind( this.eventNamespace )
                .removeData( this.widgetFullName )
                // support: jquery <1.6.3
                // http://bugs.jquery.com/ticket/9413
                .removeData( $.camelCase( this.widgetFullName ) );
            this.widget()
                .unbind( this.eventNamespace )
                .removeAttr( "aria-disabled" )
                .removeClass(
                    this.widgetFullName + "-disabled " +
                    "ui-state-disabled" );

            // clean up events and states
            this.bindings.unbind( this.eventNamespace );
            this.hoverable.removeClass( "ui-state-hover" );
            this.focusable.removeClass( "ui-state-focus" );
        },
        _destroy: $.noop,

        widget: function() {
            return this.element;
        },

        option: function( key, value ) {
            var options = key,
                parts,
                curOption,
                i;

            if ( arguments.length === 0 ) {
                // don't return a reference to the internal hash
                return $.widget.extend( {}, this.options );
            }

            if ( typeof key === "string" ) {
                // handle nested keys, e.g., "foo.bar" => { foo: { bar: ___ } }
                options = {};
                parts = key.split( "." );
                key = parts.shift();
                if ( parts.length ) {
                    curOption = options[ key ] = $.widget.extend( {}, this.options[ key ] );
                    for ( i = 0; i < parts.length - 1; i++ ) {
                        curOption[ parts[ i ] ] = curOption[ parts[ i ] ] || {};
                        curOption = curOption[ parts[ i ] ];
                    }
                    key = parts.pop();
                    if ( arguments.length === 1 ) {
                        return curOption[ key ] === undefined ? null : curOption[ key ];
                    }
                    curOption[ key ] = value;
                } else {
                    if ( arguments.length === 1 ) {
                        return this.options[ key ] === undefined ? null : this.options[ key ];
                    }
                    options[ key ] = value;
                }
            }

            this._setOptions( options );

            return this;
        },
        _setOptions: function( options ) {
            var key;

            for ( key in options ) {
                this._setOption( key, options[ key ] );
            }

            return this;
        },
        _setOption: function( key, value ) {
            this.options[ key ] = value;

            if ( key === "disabled" ) {
                this.widget()
                    .toggleClass( this.widgetFullName + "-disabled", !!value );

                // If the widget is becoming disabled, then nothing is interactive
                if ( value ) {
                    this.hoverable.removeClass( "ui-state-hover" );
                    this.focusable.removeClass( "ui-state-focus" );
                }
            }

            return this;
        },

        enable: function() {
            return this._setOptions({ disabled: false });
        },
        disable: function() {
            return this._setOptions({ disabled: true });
        },

        _on: function( suppressDisabledCheck, element, handlers ) {
            var delegateElement,
                instance = this;

            // no suppressDisabledCheck flag, shuffle arguments
            if ( typeof suppressDisabledCheck !== "boolean" ) {
                handlers = element;
                element = suppressDisabledCheck;
                suppressDisabledCheck = false;
            }

            // no element argument, shuffle and use this.element
            if ( !handlers ) {
                handlers = element;
                element = this.element;
                delegateElement = this.widget();
            } else {
                element = delegateElement = $( element );
                this.bindings = this.bindings.add( element );
            }

            $.each( handlers, function( event, handler ) {
                function handlerProxy() {
                    // allow widgets to customize the disabled handling
                    // - disabled as an array instead of boolean
                    // - disabled class as method for disabling individual parts
                    if ( !suppressDisabledCheck &&
                        ( instance.options.disabled === true ||
                            $( this ).hasClass( "ui-state-disabled" ) ) ) {
                        return;
                    }
                    return ( typeof handler === "string" ? instance[ handler ] : handler )
                        .apply( instance, arguments );
                }

                // copy the guid so direct unbinding works
                if ( typeof handler !== "string" ) {
                    handlerProxy.guid = handler.guid =
                        handler.guid || handlerProxy.guid || $.guid++;
                }

                var match = event.match( /^([\w:-]*)\s*(.*)$/ ),
                    eventName = match[1] + instance.eventNamespace,
                    selector = match[2];
                if ( selector ) {
                    delegateElement.delegate( selector, eventName, handlerProxy );
                } else {
                    element.bind( eventName, handlerProxy );
                }
            });
        },

        _off: function( element, eventName ) {
            eventName = (eventName || "").split( " " ).join( this.eventNamespace + " " ) +
                this.eventNamespace;
            element.unbind( eventName ).undelegate( eventName );

            // Clear the stack to avoid memory leaks (#10056)
            this.bindings = $( this.bindings.not( element ).get() );
            this.focusable = $( this.focusable.not( element ).get() );
            this.hoverable = $( this.hoverable.not( element ).get() );
        },

        _delay: function( handler, delay ) {
            function handlerProxy() {
                return ( typeof handler === "string" ? instance[ handler ] : handler )
                    .apply( instance, arguments );
            }
            var instance = this;
            return setTimeout( handlerProxy, delay || 0 );
        },

        _hoverable: function( element ) {
            this.hoverable = this.hoverable.add( element );
            this._on( element, {
                mouseenter: function( event ) {
                    $( event.currentTarget ).addClass( "ui-state-hover" );
                },
                mouseleave: function( event ) {
                    $( event.currentTarget ).removeClass( "ui-state-hover" );
                }
            });
        },

        _focusable: function( element ) {
            this.focusable = this.focusable.add( element );
            this._on( element, {
                focusin: function( event ) {
                    $( event.currentTarget ).addClass( "ui-state-focus" );
                },
                focusout: function( event ) {
                    $( event.currentTarget ).removeClass( "ui-state-focus" );
                }
            });
        },

        _trigger: function( type, event, data ) {
            var prop, orig,
                callback = this.options[ type ];

            data = data || {};
            event = $.Event( event );
            event.type = ( type === this.widgetEventPrefix ?
                type :
                this.widgetEventPrefix + type ).toLowerCase();
            // the original event may come from any element
            // so we need to reset the target on the new event
            event.target = this.element[ 0 ];

            // copy original event properties over to the new event
            orig = event.originalEvent;
            if ( orig ) {
                for ( prop in orig ) {
                    if ( !( prop in event ) ) {
                        event[ prop ] = orig[ prop ];
                    }
                }
            }

            this.element.trigger( event, data );
            return !( $.isFunction( callback ) &&
                callback.apply( this.element[0], [ event ].concat( data ) ) === false ||
                event.isDefaultPrevented() );
        }
    };

    $.each( { show: "fadeIn", hide: "fadeOut" }, function( method, defaultEffect ) {
        $.Widget.prototype[ "_" + method ] = function( element, options, callback ) {
            if ( typeof options === "string" ) {
                options = { effect: options };
            }
            var hasOptions,
                effectName = !options ?
                    method :
                    options === true || typeof options === "number" ?
                        defaultEffect :
                        options.effect || defaultEffect;
            options = options || {};
            if ( typeof options === "number" ) {
                options = { duration: options };
            }
            hasOptions = !$.isEmptyObject( options );
            options.complete = callback;
            if ( options.delay ) {
                element.delay( options.delay );
            }
            if ( hasOptions && $.effects && $.effects.effect[ effectName ] ) {
                element[ method ]( options );
            } else if ( effectName !== method && element[ effectName ] ) {
                element[ effectName ]( options.duration, options.easing, callback );
            } else {
                element.queue(function( next ) {
                    $( this )[ method ]();
                    if ( callback ) {
                        callback.call( element[ 0 ] );
                    }
                    next();
                });
            }
        };
    });

    var widget = $.widget;



}));







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
jQuery.cookie = function (name, value, options) {
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
            }
            else {
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
    }
    else { // only name given, get cookie
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

/* END jQuery Cookie Extension */
// ######################################################################################################### //









// ######################################################################################################### //
/* Start jQuery Get Cookie Extension */

/**
 * jQuery getCookie Wrapper
 *
 * @param name - The name of the cookie to obtain.
 * @param ifNull - A value to return if the cookie is not set.
 *
 * @author Joseph T. Parsons
 *
 */
jQuery.getCookie = function (name, ifNull) {
    var cookie = $.cookie(name);

    if (cookie === null || cookie === undefined) return ifNull;
    else return cookie;
};

/* END jQuery Get Cookie Extension */
// ######################################################################################################### //











// ######################################################################################################### //
/* Start jQuery toArray Extension */

/**
 * jQuery toArray Wrapper
 * Converts an object to an array.
 *
 * @param obj - Object to convert.
 *
 * @return array - Converted object.
 *
 * @author Joseph T. Parsons
 *
 */
jQuery.toArray = function(obj) {
    return $.map(obj, function (value, key) { return value; });
};

/* END jQuery toArray Extension */
// ######################################################################################################### //










// ######################################################################################################### //
/* START jQuery Context Menu */

/**
 * jQuery contextMenu v2.5.0 - Plugin for simple contextMenu handling
 *
 * Version: v2.5.0
 *
 * Authors: Björn Brala (SWIS.nl), Rodney Rehm, Addy Osmani (patches for FF)
 * Web: http://swisnl.github.io/jQuery-contextMenu/
 *
 * Copyright (c) 2011-2017 SWIS BV and contributors
 *
 * Licensed under
 *   MIT License http://www.opensource.org/licenses/mit-license
 *
 * Date: 2017-08-30T12:41:32.950Z
 */

// jscs:disable
/* jshint ignore:start */
(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node / CommonJS
        factory(require('jquery'));
    } else {
        // Browser globals.
        factory(jQuery);
    }
})(function ($) {

    'use strict';

    // TODO: -
    // ARIA stuff: menuitem, menuitemcheckbox und menuitemradio
    // create <menu> structure if $.support[htmlCommand || htmlMenuitem] and !opt.disableNative

    // determine html5 compatibility
    $.support.htmlMenuitem = ('HTMLMenuItemElement' in window);
    $.support.htmlCommand = ('HTMLCommandElement' in window);
    $.support.eventSelectstart = ('onselectstart' in document.documentElement);
    /* // should the need arise, test for css user-select
     $.support.cssUserSelect = (function(){
     var t = false,
     e = document.createElement('div');

     $.each('Moz|Webkit|Khtml|O|ms|Icab|'.split('|'), function(i, prefix) {
     var propCC = prefix + (prefix ? 'U' : 'u') + 'serSelect',
     prop = (prefix ? ('-' + prefix.toLowerCase() + '-') : '') + 'user-select';

     e.style.cssText = prop + ': text;';
     if (e.style[propCC] == 'text') {
     t = true;
     return false;
     }

     return true;
     });

     return t;
     })();
     */


    if (!$.ui || !$.widget) {
        // duck punch $.cleanData like jQueryUI does to get that remove event
        $.cleanData = (function (orig) {
            return function (elems) {
                var events, elem, i;
                for (i = 0; elems[i] != null; i++) {
                    elem = elems[i];
                    try {
                        // Only trigger remove when necessary to save time
                        events = $._data(elem, 'events');
                        if (events && events.remove) {
                            $(elem).triggerHandler('remove');
                        }

                        // Http://bugs.jquery.com/ticket/8235
                    } catch (e) {
                    }
                }
                orig(elems);
            };
        })($.cleanData);
    }
    /* jshint ignore:end */
    // jscs:enable

    var // currently active contextMenu trigger
        $currentTrigger = null,
        // is contextMenu initialized with at least one menu?
        initialized = false,
        // window handle
        $win = $(window),
        // number of registered menus
        counter = 0,
        // mapping selector to namespace
        namespaces = {},
        // mapping namespace to options
        menus = {},
        // custom command type handlers
        types = {},
        // default values
        defaults = {
            // selector of contextMenu trigger
            selector: null,
            // where to append the menu to
            appendTo: null,
            // method to trigger context menu ["right", "left", "hover"]
            trigger: 'right',
            // hide menu when mouse leaves trigger / menu elements
            autoHide: false,
            // ms to wait before showing a hover-triggered context menu
            delay: 200,
            // flag denoting if a second trigger should simply move (true) or rebuild (false) an open menu
            // as long as the trigger happened on one of the trigger-element's child nodes
            reposition: true,

            //ability to select submenu
            selectableSubMenu: false,

            // Default classname configuration to be able avoid conflicts in frameworks
            classNames: {
                hover: 'context-menu-hover', // Item hover
                disabled: 'context-menu-disabled', // Item disabled
                visible: 'context-menu-visible', // Item visible
                notSelectable: 'context-menu-not-selectable', // Item not selectable

                icon: 'context-menu-icon',
                iconEdit: 'context-menu-icon-edit',
                iconCut: 'context-menu-icon-cut',
                iconCopy: 'context-menu-icon-copy',
                iconPaste: 'context-menu-icon-paste',
                iconDelete: 'context-menu-icon-delete',
                iconAdd: 'context-menu-icon-add',
                iconQuit: 'context-menu-icon-quit',
                iconLoadingClass: 'context-menu-icon-loading'
            },

            // determine position to show menu at
            determinePosition: function ($menu) {
                // position to the lower middle of the trigger element
                if ($.ui && $.ui.position) {
                    // .position() is provided as a jQuery UI utility
                    // (...and it won't work on hidden elements)
                    $menu.css('display', 'block').position({
                        my: 'center top',
                        at: 'center bottom',
                        of: this,
                        offset: '0 5',
                        collision: 'fit'
                    }).css('display', 'none');
                } else {
                    // determine contextMenu position
                    var offset = this.offset();
                    offset.top += this.outerHeight();
                    offset.left += this.outerWidth() / 2 - $menu.outerWidth() / 2;
                    $menu.css(offset);
                }
            },
            // position menu
            position: function (opt, x, y) {
                var offset;
                // determine contextMenu position
                if (!x && !y) {
                    opt.determinePosition.call(this, opt.$menu);
                    return;
                } else if (x === 'maintain' && y === 'maintain') {
                    // x and y must not be changed (after re-show on command click)
                    offset = opt.$menu.position();
                } else {
                    // x and y are given (by mouse event)
                    var offsetParentOffset = opt.$menu.offsetParent().offset();
                    offset = {top: y - offsetParentOffset.top, left: x -offsetParentOffset.left};
                }

                // correct offset if viewport demands it
                var bottom = $win.scrollTop() + $win.height(),
                    right = $win.scrollLeft() + $win.width(),
                    height = opt.$menu.outerHeight(),
                    width = opt.$menu.outerWidth();

                if (offset.top + height > bottom) {
                    offset.top -= height;
                }

                if (offset.top < 0) {
                    offset.top = 0;
                }

                if (offset.left + width > right) {
                    offset.left -= width;
                }

                if (offset.left < 0) {
                    offset.left = 0;
                }

                opt.$menu.css(offset);
            },
            // position the sub-menu
            positionSubmenu: function ($menu) {
                if (typeof $menu === 'undefined') {
                    // When user hovers over item (which has sub items) handle.focusItem will call this.
                    // but the submenu does not exist yet if opt.items is a promise. just return, will
                    // call positionSubmenu after promise is completed.
                    return;
                }
                if ($.ui && $.ui.position) {
                    // .position() is provided as a jQuery UI utility
                    // (...and it won't work on hidden elements)
                    $menu.css('display', 'block').position({
                        my: 'left top-5',
                        at: 'right top',
                        of: this,
                        collision: 'flipfit fit'
                    }).css('display', '');
                } else {
                    // determine contextMenu position
                    var offset = {
                        top: -9,
                        left: this.outerWidth() - 5
                    };
                    $menu.css(offset);
                }
            },
            // offset to add to zIndex
            zIndex: 1,
            // show hide animation settings
            animation: {
                duration: 50,
                show: 'slideDown',
                hide: 'slideUp'
            },
            // events
            events: {
                show: $.noop,
                hide: $.noop
            },
            // default callback
            callback: null,
            // list of contextMenu items
            items: {}
        },
        // mouse position for hover activation
        hoveract = {
            timer: null,
            pageX: null,
            pageY: null
        },
        // determine zIndex
        zindex = function ($t) {
            var zin = 0,
                $tt = $t;

            while (true) {
                zin = Math.max(zin, parseInt($tt.css('z-index'), 10) || 0);
                $tt = $tt.parent();
                if (!$tt || !$tt.length || 'html body'.indexOf($tt.prop('nodeName').toLowerCase()) > -1) {
                    break;
                }
            }
            return zin;
        },
        // event handlers
        handle = {
            // abort anything
            abortevent: function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
            },
            // contextmenu show dispatcher
            contextmenu: function (e) {
                var $this = $(this);

                // disable actual context-menu if we are using the right mouse button as the trigger
                if (e.data.trigger === 'right') {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }

                // abort native-triggered events unless we're triggering on right click
                if ((e.data.trigger !== 'right' && e.data.trigger !== 'demand') && e.originalEvent) {
                    return;
                }

                // Let the current contextmenu decide if it should show or not based on its own trigger settings
                if (typeof e.mouseButton !== 'undefined' && e.data) {
                    if (!(e.data.trigger === 'left' && e.mouseButton === 0) && !(e.data.trigger === 'right' && e.mouseButton === 2)) {
                        // Mouse click is not valid.
                        return;
                    }
                }

                // abort event if menu is visible for this trigger
                if ($this.hasClass('context-menu-active')) {
                    return;
                }

                if (!$this.hasClass('context-menu-disabled')) {
                    // theoretically need to fire a show event at <menu>
                    // http://www.whatwg.org/specs/web-apps/current-work/multipage/interactive-elements.html#context-menus
                    // var evt = jQuery.Event("show", { data: data, pageX: e.pageX, pageY: e.pageY, relatedTarget: this });
                    // e.data.$menu.trigger(evt);

                    $currentTrigger = $this;
                    if (e.data.build) {
                        var built = e.data.build($currentTrigger, e);
                        // abort if build() returned false
                        if (built === false) {
                            return;
                        }

                        // dynamically build menu on invocation
                        e.data = $.extend(true, {}, defaults, e.data, built || {});

                        // abort if there are no items to display
                        if (!e.data.items || $.isEmptyObject(e.data.items)) {
                            // Note: jQuery captures and ignores errors from event handlers
                            if (window.console) {
                                (console.error || console.log).call(console, 'No items specified to show in contextMenu');
                            }

                            throw new Error('No Items specified');
                        }

                        // backreference for custom command type creation
                        e.data.$trigger = $currentTrigger;

                        op.create(e.data);
                    }
                    var showMenu = false;
                    for (var item in e.data.items) {
                        if (e.data.items.hasOwnProperty(item)) {
                            var visible;
                            if ($.isFunction(e.data.items[item].visible)) {
                                visible = e.data.items[item].visible.call($(e.currentTarget), item, e.data);
                            } else if (typeof e.data.items[item] !== 'undefined' && e.data.items[item].visible) {
                                visible = e.data.items[item].visible === true;
                            } else {
                                visible = true;
                            }
                            if (visible) {
                                showMenu = true;
                            }
                        }
                    }
                    if (showMenu) {
                        // show menu
                        op.show.call($this, e.data, e.pageX, e.pageY);
                    }
                }
            },
            // contextMenu left-click trigger
            click: function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                $(this).trigger($.Event('contextmenu', {data: e.data, pageX: e.pageX, pageY: e.pageY}));
            },
            // contextMenu right-click trigger
            mousedown: function (e) {
                // register mouse down
                var $this = $(this);

                // hide any previous menus
                if ($currentTrigger && $currentTrigger.length && !$currentTrigger.is($this)) {
                    $currentTrigger.data('contextMenu').$menu.trigger('contextmenu:hide');
                }

                // activate on right click
                if (e.button === 2) {
                    $currentTrigger = $this.data('contextMenuActive', true);
                }
            },
            // contextMenu right-click trigger
            mouseup: function (e) {
                // show menu
                var $this = $(this);
                if ($this.data('contextMenuActive') && $currentTrigger && $currentTrigger.length && $currentTrigger.is($this) && !$this.hasClass('context-menu-disabled')) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    $currentTrigger = $this;
                    $this.trigger($.Event('contextmenu', {data: e.data, pageX: e.pageX, pageY: e.pageY}));
                }

                $this.removeData('contextMenuActive');
            },
            // contextMenu hover trigger
            mouseenter: function (e) {
                var $this = $(this),
                    $related = $(e.relatedTarget),
                    $document = $(document);

                // abort if we're coming from a menu
                if ($related.is('.context-menu-list') || $related.closest('.context-menu-list').length) {
                    return;
                }

                // abort if a menu is shown
                if ($currentTrigger && $currentTrigger.length) {
                    return;
                }

                hoveract.pageX = e.pageX;
                hoveract.pageY = e.pageY;
                hoveract.data = e.data;
                $document.on('mousemove.contextMenuShow', handle.mousemove);
                hoveract.timer = setTimeout(function () {
                    hoveract.timer = null;
                    $document.off('mousemove.contextMenuShow');
                    $currentTrigger = $this;
                    $this.trigger($.Event('contextmenu', {
                        data: hoveract.data,
                        pageX: hoveract.pageX,
                        pageY: hoveract.pageY
                    }));
                }, e.data.delay);
            },
            // contextMenu hover trigger
            mousemove: function (e) {
                hoveract.pageX = e.pageX;
                hoveract.pageY = e.pageY;
            },
            // contextMenu hover trigger
            mouseleave: function (e) {
                // abort if we're leaving for a menu
                var $related = $(e.relatedTarget);
                if ($related.is('.context-menu-list') || $related.closest('.context-menu-list').length) {
                    return;
                }

                try {
                    clearTimeout(hoveract.timer);
                } catch (e) {
                }

                hoveract.timer = null;
            },
            // click on layer to hide contextMenu
            layerClick: function (e) {
                var $this = $(this),
                    root = $this.data('contextMenuRoot'),
                    button = e.button,
                    x = e.pageX,
                    y = e.pageY,
                    target,
                    offset;

                e.preventDefault();

                setTimeout(function () {
                    var $window;
                    var triggerAction = ((root.trigger === 'left' && button === 0) || (root.trigger === 'right' && button === 2));

                    // find the element that would've been clicked, wasn't the layer in the way
                    if (document.elementFromPoint && root.$layer) {
                        root.$layer.hide();
                        target = document.elementFromPoint(x - $win.scrollLeft(), y - $win.scrollTop());

                        // also need to try and focus this element if we're in a contenteditable area,
                        // as the layer will prevent the browser mouse action we want
                        if (target.isContentEditable) {
                            var range = document.createRange(),
                                sel = window.getSelection();
                            range.selectNode(target);
                            range.collapse(true);
                            sel.removeAllRanges();
                            sel.addRange(range);
                        }
                        $(target).trigger(e);
                        root.$layer.show();
                    }

                    if (root.reposition && triggerAction) {
                        if (document.elementFromPoint) {
                            if (root.$trigger.is(target)) {
                                root.position.call(root.$trigger, root, x, y);
                                return;
                            }
                        } else {
                            offset = root.$trigger.offset();
                            $window = $(window);
                            // while this looks kinda awful, it's the best way to avoid
                            // unnecessarily calculating any positions
                            offset.top += $window.scrollTop();
                            if (offset.top <= e.pageY) {
                                offset.left += $window.scrollLeft();
                                if (offset.left <= e.pageX) {
                                    offset.bottom = offset.top + root.$trigger.outerHeight();
                                    if (offset.bottom >= e.pageY) {
                                        offset.right = offset.left + root.$trigger.outerWidth();
                                        if (offset.right >= e.pageX) {
                                            // reposition
                                            root.position.call(root.$trigger, root, x, y);
                                            return;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if (target && triggerAction) {
                        root.$trigger.one('contextmenu:hidden', function () {
                            $(target).contextMenu({x: x, y: y, button: button});
                        });
                    }

                    if (root !== null && typeof root !== 'undefined' && root.$menu !== null  && typeof root.$menu !== 'undefined') {
                        root.$menu.trigger('contextmenu:hide');
                    }
                }, 50);
            },
            // key handled :hover
            keyStop: function (e, opt) {
                if (!opt.isInput) {
                    e.preventDefault();
                }

                e.stopPropagation();
            },
            key: function (e) {

                var opt = {};

                // Only get the data from $currentTrigger if it exists
                if ($currentTrigger) {
                    opt = $currentTrigger.data('contextMenu') || {};
                }
                // If the trigger happen on a element that are above the contextmenu do this
                if (typeof opt.zIndex === 'undefined') {
                    opt.zIndex = 0;
                }
                var targetZIndex = 0;
                var getZIndexOfTriggerTarget = function (target) {
                    if (target.style.zIndex !== '') {
                        targetZIndex = target.style.zIndex;
                    } else {
                        if (target.offsetParent !== null && typeof target.offsetParent !== 'undefined') {
                            getZIndexOfTriggerTarget(target.offsetParent);
                        }
                        else if (target.parentElement !== null && typeof target.parentElement !== 'undefined') {
                            getZIndexOfTriggerTarget(target.parentElement);
                        }
                    }
                };
                getZIndexOfTriggerTarget(e.target);
                // If targetZIndex is heigher then opt.zIndex dont progress any futher.
                // This is used to make sure that if you are using a dialog with a input / textarea / contenteditable div
                // and its above the contextmenu it wont steal keys events
                if (opt.$menu && parseInt(targetZIndex,10) > parseInt(opt.$menu.css("zIndex"),10)) {
                    return;
                }
                switch (e.keyCode) {
                    case 9:
                    case 38: // up
                        handle.keyStop(e, opt);
                        // if keyCode is [38 (up)] or [9 (tab) with shift]
                        if (opt.isInput) {
                            if (e.keyCode === 9 && e.shiftKey) {
                                e.preventDefault();
                                if (opt.$selected) {
                                    opt.$selected.find('input, textarea, select').blur();
                                }
                                if (opt.$menu !== null && typeof opt.$menu !== 'undefined') {
                                    opt.$menu.trigger('prevcommand');
                                }
                                return;
                            } else if (e.keyCode === 38 && opt.$selected.find('input, textarea, select').prop('type') === 'checkbox') {
                                // checkboxes don't capture this key
                                e.preventDefault();
                                return;
                            }
                        } else if (e.keyCode !== 9 || e.shiftKey) {
                            if (opt.$menu !== null && typeof opt.$menu !== 'undefined') {
                                opt.$menu.trigger('prevcommand');
                            }
                            return;
                        }
                        break;
                    // omitting break;
                    // case 9: // tab - reached through omitted break;
                    case 40: // down
                        handle.keyStop(e, opt);
                        if (opt.isInput) {
                            if (e.keyCode === 9) {
                                e.preventDefault();
                                if (opt.$selected) {
                                    opt.$selected.find('input, textarea, select').blur();
                                }
                                if (opt.$menu !== null && typeof opt.$menu !== 'undefined') {
                                    opt.$menu.trigger('nextcommand');
                                }
                                return;
                            } else if (e.keyCode === 40 && opt.$selected.find('input, textarea, select').prop('type') === 'checkbox') {
                                // checkboxes don't capture this key
                                e.preventDefault();
                                return;
                            }
                        } else {
                            if (opt.$menu !== null && typeof opt.$menu !== 'undefined') {
                                opt.$menu.trigger('nextcommand');
                            }
                            return;
                        }
                        break;

                    case 37: // left
                        handle.keyStop(e, opt);
                        if (opt.isInput || !opt.$selected || !opt.$selected.length) {
                            break;
                        }

                        if (!opt.$selected.parent().hasClass('context-menu-root')) {
                            var $parent = opt.$selected.parent().parent();
                            opt.$selected.trigger('contextmenu:blur');
                            opt.$selected = $parent;
                            return;
                        }
                        break;

                    case 39: // right
                        handle.keyStop(e, opt);
                        if (opt.isInput || !opt.$selected || !opt.$selected.length) {
                            break;
                        }

                        var itemdata = opt.$selected.data('contextMenu') || {};
                        if (itemdata.$menu && opt.$selected.hasClass('context-menu-submenu')) {
                            opt.$selected = null;
                            itemdata.$selected = null;
                            itemdata.$menu.trigger('nextcommand');
                            return;
                        }
                        break;

                    case 35: // end
                    case 36: // home
                        if (opt.$selected && opt.$selected.find('input, textarea, select').length) {
                            return;
                        } else {
                            (opt.$selected && opt.$selected.parent() || opt.$menu)
                                .children(':not(.' + opt.classNames.disabled + ', .' + opt.classNames.notSelectable + ')')[e.keyCode === 36 ? 'first' : 'last']()
                                .trigger('contextmenu:focus');
                            e.preventDefault();
                            return;
                        }
                        break;

                    case 13: // enter
                        handle.keyStop(e, opt);
                        if (opt.isInput) {
                            if (opt.$selected && !opt.$selected.is('textarea, select')) {
                                e.preventDefault();
                                return;
                            }
                            break;
                        }
                        if (typeof opt.$selected !== 'undefined' && opt.$selected !== null) {
                            opt.$selected.trigger('mouseup');
                        }
                        return;

                    case 32: // space
                    case 33: // page up
                    case 34: // page down
                        // prevent browser from scrolling down while menu is visible
                        handle.keyStop(e, opt);
                        return;

                    case 27: // esc
                        handle.keyStop(e, opt);
                        if (opt.$menu !== null && typeof opt.$menu !== 'undefined') {
                            opt.$menu.trigger('contextmenu:hide');
                        }
                        return;

                    default: // 0-9, a-z
                        var k = (String.fromCharCode(e.keyCode)).toUpperCase();
                        if (opt.accesskeys && opt.accesskeys[k]) {
                            // according to the specs accesskeys must be invoked immediately
                            opt.accesskeys[k].$node.trigger(opt.accesskeys[k].$menu ? 'contextmenu:focus' : 'mouseup');
                            return;
                        }
                        break;
                }
                // pass event to selected item,
                // stop propagation to avoid endless recursion
                e.stopPropagation();
                if (typeof opt.$selected !== 'undefined' && opt.$selected !== null) {
                    opt.$selected.trigger(e);
                }
            },
            // select previous possible command in menu
            prevItem: function (e) {
                e.stopPropagation();
                var opt = $(this).data('contextMenu') || {};
                var root = $(this).data('contextMenuRoot') || {};

                // obtain currently selected menu
                if (opt.$selected) {
                    var $s = opt.$selected;
                    opt = opt.$selected.parent().data('contextMenu') || {};
                    opt.$selected = $s;
                }

                var $children = opt.$menu.children(),
                    $prev = !opt.$selected || !opt.$selected.prev().length ? $children.last() : opt.$selected.prev(),
                    $round = $prev;

                // skip disabled or hidden elements
                while ($prev.hasClass(root.classNames.disabled) || $prev.hasClass(root.classNames.notSelectable) || $prev.is(':hidden')) {
                    if ($prev.prev().length) {
                        $prev = $prev.prev();
                    } else {
                        $prev = $children.last();
                    }
                    if ($prev.is($round)) {
                        // break endless loop
                        return;
                    }
                }

                // leave current
                if (opt.$selected) {
                    handle.itemMouseleave.call(opt.$selected.get(0), e);
                }

                // activate next
                handle.itemMouseenter.call($prev.get(0), e);

                // focus input
                var $input = $prev.find('input, textarea, select');
                if ($input.length) {
                    $input.focus();
                }
            },
            // select next possible command in menu
            nextItem: function (e) {
                e.stopPropagation();
                var opt = $(this).data('contextMenu') || {};
                var root = $(this).data('contextMenuRoot') || {};

                // obtain currently selected menu
                if (opt.$selected) {
                    var $s = opt.$selected;
                    opt = opt.$selected.parent().data('contextMenu') || {};
                    opt.$selected = $s;
                }

                var $children = opt.$menu.children(),
                    $next = !opt.$selected || !opt.$selected.next().length ? $children.first() : opt.$selected.next(),
                    $round = $next;

                // skip disabled
                while ($next.hasClass(root.classNames.disabled) || $next.hasClass(root.classNames.notSelectable) || $next.is(':hidden')) {
                    if ($next.next().length) {
                        $next = $next.next();
                    } else {
                        $next = $children.first();
                    }
                    if ($next.is($round)) {
                        // break endless loop
                        return;
                    }
                }

                // leave current
                if (opt.$selected) {
                    handle.itemMouseleave.call(opt.$selected.get(0), e);
                }

                // activate next
                handle.itemMouseenter.call($next.get(0), e);

                // focus input
                var $input = $next.find('input, textarea, select');
                if ($input.length) {
                    $input.focus();
                }
            },
            // flag that we're inside an input so the key handler can act accordingly
            focusInput: function () {
                var $this = $(this).closest('.context-menu-item'),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot;

                root.$selected = opt.$selected = $this;
                root.isInput = opt.isInput = true;
            },
            // flag that we're inside an input so the key handler can act accordingly
            blurInput: function () {
                var $this = $(this).closest('.context-menu-item'),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot;

                root.isInput = opt.isInput = false;
            },
            // :hover on menu
            menuMouseenter: function () {
                var root = $(this).data().contextMenuRoot;
                root.hovering = true;
            },
            // :hover on menu
            menuMouseleave: function (e) {
                var root = $(this).data().contextMenuRoot;
                if (root.$layer && root.$layer.is(e.relatedTarget)) {
                    root.hovering = false;
                }
            },
            // :hover done manually so key handling is possible
            itemMouseenter: function (e) {
                var $this = $(this),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot;

                root.hovering = true;

                // abort if we're re-entering
                if (e && root.$layer && root.$layer.is(e.relatedTarget)) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }

                // make sure only one item is selected
                (opt.$menu ? opt : root).$menu
                    .children('.' + root.classNames.hover).trigger('contextmenu:blur')
                    .children('.hover').trigger('contextmenu:blur');

                if ($this.hasClass(root.classNames.disabled) || $this.hasClass(root.classNames.notSelectable)) {
                    opt.$selected = null;
                    return;
                }


                $this.trigger('contextmenu:focus');
            },
            // :hover done manually so key handling is possible
            itemMouseleave: function (e) {
                var $this = $(this),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot;

                if (root !== opt && root.$layer && root.$layer.is(e.relatedTarget)) {
                    if (typeof root.$selected !== 'undefined' && root.$selected !== null) {
                        root.$selected.trigger('contextmenu:blur');
                    }
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    root.$selected = opt.$selected = opt.$node;
                    return;
                }

                if(opt && opt.$menu && opt.$menu.hasClass('context-menu-visible')){
                    return;
                }

                $this.trigger('contextmenu:blur');
            },
            // contextMenu item click
            itemClick: function (e) {
                var $this = $(this),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot,
                    key = data.contextMenuKey,
                    callback;

                // abort if the key is unknown or disabled or is a menu
                if (!opt.items[key] || $this.is('.' + root.classNames.disabled + ', .context-menu-separator, .' + root.classNames.notSelectable) || ($this.is('.context-menu-submenu') && root.selectableSubMenu === false )) {
                    return;
                }

                e.preventDefault();
                e.stopImmediatePropagation();

                if ($.isFunction(opt.callbacks[key]) && Object.prototype.hasOwnProperty.call(opt.callbacks, key)) {
                    // item-specific callback
                    callback = opt.callbacks[key];
                } else if ($.isFunction(root.callback)) {
                    // default callback
                    callback = root.callback;
                } else {
                    // no callback, no action
                    return;
                }

                // hide menu if callback doesn't stop that
                if (callback.call(root.$trigger, key, root, e) !== false) {
                    root.$menu.trigger('contextmenu:hide');
                } else if (root.$menu.parent().length) {
                    op.update.call(root.$trigger, root);
                }
            },
            // ignore click events on input elements
            inputClick: function (e) {
                e.stopImmediatePropagation();
            },
            // hide <menu>
            hideMenu: function (e, data) {
                var root = $(this).data('contextMenuRoot');
                op.hide.call(root.$trigger, root, data && data.force);
            },
            // focus <command>
            focusItem: function (e) {
                e.stopPropagation();
                var $this = $(this),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot;

                if ($this.hasClass(root.classNames.disabled) || $this.hasClass(root.classNames.notSelectable)) {
                    return;
                }

                $this
                    .addClass([root.classNames.hover, root.classNames.visible].join(' '))
                    // select other items and included items
                    .parent().find('.context-menu-item').not($this)
                    .removeClass(root.classNames.visible)
                    .filter('.' + root.classNames.hover)
                    .trigger('contextmenu:blur');

                // remember selected
                opt.$selected = root.$selected = $this;


                if(opt && opt.$node && opt.$node.hasClass('context-menu-submenu')){
                    opt.$node.addClass(root.classNames.hover);
                }

                // position sub-menu - do after show so dumb $.ui.position can keep up
                if (opt.$node) {
                    root.positionSubmenu.call(opt.$node, opt.$menu);
                }
            },
            // blur <command>
            blurItem: function (e) {
                e.stopPropagation();
                var $this = $(this),
                    data = $this.data(),
                    opt = data.contextMenu,
                    root = data.contextMenuRoot;

                if (opt.autoHide) { // for tablets and touch screens this needs to remain
                    $this.removeClass(root.classNames.visible);
                }
                $this.removeClass(root.classNames.hover);
                opt.$selected = null;
            }
        },
        // operations
        op = {
            show: function (opt, x, y) {
                var $trigger = $(this),
                    css = {};

                // hide any open menus
                $('#context-menu-layer').trigger('mousedown');

                // backreference for callbacks
                opt.$trigger = $trigger;

                // show event
                if (opt.events.show.call($trigger, opt) === false) {
                    $currentTrigger = null;
                    return;
                }

                // create or update context menu
                op.update.call($trigger, opt);

                // position menu
                opt.position.call($trigger, opt, x, y);

                // make sure we're in front
                if (opt.zIndex) {
                    var additionalZValue = opt.zIndex;
                    // If opt.zIndex is a function, call the function to get the right zIndex.
                    if (typeof opt.zIndex === 'function') {
                        additionalZValue = opt.zIndex.call($trigger, opt);
                    }
                    css.zIndex = zindex($trigger) + additionalZValue;
                }

                // add layer
                op.layer.call(opt.$menu, opt, css.zIndex);

                // adjust sub-menu zIndexes
                opt.$menu.find('ul').css('zIndex', css.zIndex + 1);

                // position and show context menu
                opt.$menu.css(css)[opt.animation.show](opt.animation.duration, function () {
                    $trigger.trigger('contextmenu:visible');
                });
                // make options available and set state
                $trigger
                    .data('contextMenu', opt)
                    .addClass('context-menu-active');

                // register key handler
                $(document).off('keydown.contextMenu').on('keydown.contextMenu', handle.key);
                // register autoHide handler
                if (opt.autoHide) {
                    // mouse position handler
                    $(document).on('mousemove.contextMenuAutoHide', function (e) {
                        // need to capture the offset on mousemove,
                        // since the page might've been scrolled since activation
                        var pos = $trigger.offset();
                        pos.right = pos.left + $trigger.outerWidth();
                        pos.bottom = pos.top + $trigger.outerHeight();

                        if (opt.$layer && !opt.hovering && (!(e.pageX >= pos.left && e.pageX <= pos.right) || !(e.pageY >= pos.top && e.pageY <= pos.bottom))) {
                            /* Additional hover check after short time, you might just miss the edge of the menu */
                            setTimeout(function () {
                                if (!opt.hovering && opt.$menu !== null && typeof opt.$menu !== 'undefined') {
                                    opt.$menu.trigger('contextmenu:hide');
                                }
                            }, 50);
                        }
                    });
                }
            },
            hide: function (opt, force) {
                var $trigger = $(this);
                if (!opt) {
                    opt = $trigger.data('contextMenu') || {};
                }

                // hide event
                if (!force && opt.events && opt.events.hide.call($trigger, opt) === false) {
                    return;
                }

                // remove options and revert state
                $trigger
                    .removeData('contextMenu')
                    .removeClass('context-menu-active');

                if (opt.$layer) {
                    // keep layer for a bit so the contextmenu event can be aborted properly by opera
                    setTimeout((function ($layer) {
                        return function () {
                            $layer.remove();
                        };
                    })(opt.$layer), 10);

                    try {
                        delete opt.$layer;
                    } catch (e) {
                        opt.$layer = null;
                    }
                }

                // remove handle
                $currentTrigger = null;
                // remove selected
                opt.$menu.find('.' + opt.classNames.hover).trigger('contextmenu:blur');
                opt.$selected = null;
                // collapse all submenus
                opt.$menu.find('.' + opt.classNames.visible).removeClass(opt.classNames.visible);
                // unregister key and mouse handlers
                // $(document).off('.contextMenuAutoHide keydown.contextMenu'); // http://bugs.jquery.com/ticket/10705
                $(document).off('.contextMenuAutoHide').off('keydown.contextMenu');
                // hide menu
                if (opt.$menu) {
                    opt.$menu[opt.animation.hide](opt.animation.duration, function () {
                        // tear down dynamically built menu after animation is completed.
                        if (opt.build) {
                            opt.$menu.remove();
                            $.each(opt, function (key) {
                                switch (key) {
                                    case 'ns':
                                    case 'selector':
                                    case 'build':
                                    case 'trigger':
                                        return true;

                                    default:
                                        opt[key] = undefined;
                                        try {
                                            delete opt[key];
                                        } catch (e) {
                                        }
                                        return true;
                                }
                            });
                        }

                        setTimeout(function () {
                            $trigger.trigger('contextmenu:hidden');
                        }, 10);
                    });
                }
            },
            create: function (opt, root) {
                if (typeof root === 'undefined') {
                    root = opt;
                }

                // create contextMenu
                opt.$menu = $('<ul class="context-menu-list"></ul>').addClass(opt.className || '').data({
                    'contextMenu': opt,
                    'contextMenuRoot': root
                });

                $.each(['callbacks', 'commands', 'inputs'], function (i, k) {
                    opt[k] = {};
                    if (!root[k]) {
                        root[k] = {};
                    }
                });

                if (!root.accesskeys) {
                    root.accesskeys = {};
                }

                function createNameNode(item) {
                    var $name = $('<span></span>');
                    if (item._accesskey) {
                        if (item._beforeAccesskey) {
                            $name.append(document.createTextNode(item._beforeAccesskey));
                        }
                        $('<span></span>')
                            .addClass('context-menu-accesskey')
                            .text(item._accesskey)
                            .appendTo($name);
                        if (item._afterAccesskey) {
                            $name.append(document.createTextNode(item._afterAccesskey));
                        }
                    } else {
                        if (item.isHtmlName) {
                            // restrict use with access keys
                            if (typeof item.accesskey !== 'undefined') {
                                throw new Error('accesskeys are not compatible with HTML names and cannot be used together in the same item');
                            }
                            $name.html(item.name);
                        } else {
                            $name.text(item.name);
                        }
                    }
                    return $name;
                }

                // create contextMenu items
                $.each(opt.items, function (key, item) {
                    var $t = $('<li class="context-menu-item"></li>').addClass(item.className || ''),
                        $label = null,
                        $input = null;

                    // iOS needs to see a click-event bound to an element to actually
                    // have the TouchEvents infrastructure trigger the click event
                    $t.on('click', $.noop);

                    // Make old school string seperator a real item so checks wont be
                    // akward later.
                    // And normalize 'cm_separator' into 'cm_seperator'.
                    if (typeof item === 'string' || item.type === 'cm_separator') {
                        item = {type: 'cm_seperator'};
                    }

                    item.$node = $t.data({
                        'contextMenu': opt,
                        'contextMenuRoot': root,
                        'contextMenuKey': key
                    });

                    // register accesskey
                    // NOTE: the accesskey attribute should be applicable to any element, but Safari5 and Chrome13 still can't do that
                    if (typeof item.accesskey !== 'undefined') {
                        var aks = splitAccesskey(item.accesskey);
                        for (var i = 0, ak; ak = aks[i]; i++) {
                            if (!root.accesskeys[ak]) {
                                root.accesskeys[ak] = item;
                                var matched = item.name.match(new RegExp('^(.*?)(' + ak + ')(.*)$', 'i'));
                                if (matched) {
                                    item._beforeAccesskey = matched[1];
                                    item._accesskey = matched[2];
                                    item._afterAccesskey = matched[3];
                                }
                                break;
                            }
                        }
                    }

                    if (item.type && types[item.type]) {
                        // run custom type handler
                        types[item.type].call($t, item, opt, root);
                        // register commands
                        $.each([opt, root], function (i, k) {
                            k.commands[key] = item;
                            // Overwrite only if undefined or the item is appended to the root. This so it
                            // doesn't overwrite callbacks of root elements if the name is the same.
                            if ($.isFunction(item.callback) && (typeof k.callbacks[key] === 'undefined' || typeof opt.type === 'undefined')) {
                                k.callbacks[key] = item.callback;
                            }
                        });
                    } else {
                        // add label for input
                        if (item.type === 'cm_seperator') {
                            $t.addClass('context-menu-separator ' + root.classNames.notSelectable);
                        } else if (item.type === 'html') {
                            $t.addClass('context-menu-html ' + root.classNames.notSelectable);
                        } else if (item.type === 'sub') {
                            // We don't want to execute the next else-if if it is a sub.
                        } else if (item.type) {
                            $label = $('<label></label>').appendTo($t);
                            createNameNode(item).appendTo($label);

                            $t.addClass('context-menu-input');
                            opt.hasTypes = true;
                            $.each([opt, root], function (i, k) {
                                k.commands[key] = item;
                                k.inputs[key] = item;
                            });
                        } else if (item.items) {
                            item.type = 'sub';
                        }

                        switch (item.type) {
                            case 'cm_seperator':
                                break;

                            case 'text':
                                $input = $('<input type="text" value="1" name="" />')
                                    .attr('name', 'context-menu-input-' + key)
                                    .val(item.value || '')
                                    .appendTo($label);
                                break;

                            case 'textarea':
                                $input = $('<textarea name=""></textarea>')
                                    .attr('name', 'context-menu-input-' + key)
                                    .val(item.value || '')
                                    .appendTo($label);

                                if (item.height) {
                                    $input.height(item.height);
                                }
                                break;

                            case 'checkbox':
                                $input = $('<input type="checkbox" value="1" name="" />')
                                    .attr('name', 'context-menu-input-' + key)
                                    .val(item.value || '')
                                    .prop('checked', !!item.selected)
                                    .prependTo($label);
                                break;

                            case 'radio':
                                $input = $('<input type="radio" value="1" name="" />')
                                    .attr('name', 'context-menu-input-' + item.radio)
                                    .val(item.value || '')
                                    .prop('checked', !!item.selected)
                                    .prependTo($label);
                                break;

                            case 'select':
                                $input = $('<select name=""></select>')
                                    .attr('name', 'context-menu-input-' + key)
                                    .appendTo($label);
                                if (item.options) {
                                    $.each(item.options, function (value, text) {
                                        $('<option></option>').val(value).text(text).appendTo($input);
                                    });
                                    $input.val(item.selected);
                                }
                                break;

                            case 'sub':
                                createNameNode(item).appendTo($t);
                                item.appendTo = item.$node;
                                $t.data('contextMenu', item).addClass('context-menu-submenu');
                                item.callback = null;

                                // If item contains items, and this is a promise, we should create it later
                                // check if subitems is of type promise. If it is a promise we need to create
                                // it later, after promise has been resolved.
                                if ('function' === typeof item.items.then) {
                                    // probably a promise, process it, when completed it will create the sub menu's.
                                    op.processPromises(item, root, item.items);
                                } else {
                                    // normal submenu.
                                    op.create(item, root);
                                }
                                break;

                            case 'html':
                                $(item.html).appendTo($t);
                                break;

                            default:
                                $.each([opt, root], function (i, k) {
                                    k.commands[key] = item;
                                    // Overwrite only if undefined or the item is appended to the root. This so it
                                    // doesn't overwrite callbacks of root elements if the name is the same.
                                    if ($.isFunction(item.callback) && (typeof k.callbacks[key] === 'undefined' || typeof opt.type === 'undefined')) {
                                        k.callbacks[key] = item.callback;
                                    }
                                });
                                createNameNode(item).appendTo($t);
                                break;
                        }

                        // disable key listener in <input>
                        if (item.type && item.type !== 'sub' && item.type !== 'html' && item.type !== 'cm_seperator') {
                            $input
                                .on('focus', handle.focusInput)
                                .on('blur', handle.blurInput);

                            if (item.events) {
                                $input.on(item.events, opt);
                            }
                        }

                        // add icons
                        if (item.icon) {
                            if ($.isFunction(item.icon)) {
                                item._icon = item.icon.call(this, this, $t, key, item);
                            } else {
                                if (typeof(item.icon) === 'string' && item.icon.substring(0, 3) === 'fa-') {
                                    // to enable font awesome
                                    item._icon = root.classNames.icon + ' ' + root.classNames.icon + '--fa fa ' + item.icon;
                                } else {
                                    item._icon = root.classNames.icon + ' ' + root.classNames.icon + '-' + item.icon;
                                }
                            }
                            $t.addClass(item._icon);
                        }
                    }

                    // cache contained elements
                    item.$input = $input;
                    item.$label = $label;

                    // attach item to menu
                    $t.appendTo(opt.$menu);

                    // Disable text selection
                    if (!opt.hasTypes && $.support.eventSelectstart) {
                        // browsers support user-select: none,
                        // IE has a special event for text-selection
                        // browsers supporting neither will not be preventing text-selection
                        $t.on('selectstart.disableTextSelect', handle.abortevent);
                    }
                });
                // attach contextMenu to <body> (to bypass any possible overflow:hidden issues on parents of the trigger element)
                if (!opt.$node) {
                    opt.$menu.css('display', 'none').addClass('context-menu-root');
                }
                opt.$menu.appendTo(opt.appendTo || document.body);
            },
            resize: function ($menu, nested) {
                var domMenu;
                // determine widths of submenus, as CSS won't grow them automatically
                // position:absolute within position:absolute; min-width:100; max-width:200; results in width: 100;
                // kinda sucks hard...

                // determine width of absolutely positioned element
                $menu.css({position: 'absolute', display: 'block'});
                // don't apply yet, because that would break nested elements' widths
                $menu.data('width',
                    (domMenu = $menu.get(0)).getBoundingClientRect ?
                        Math.ceil(domMenu.getBoundingClientRect().width) :
                        $menu.outerWidth() + 1); // outerWidth() returns rounded pixels
                // reset styles so they allow nested elements to grow/shrink naturally
                $menu.css({
                    position: 'static',
                    minWidth: '0px',
                    maxWidth: '100000px'
                });
                // identify width of nested menus
                $menu.find('> li > ul').each(function () {
                    op.resize($(this), true);
                });
                // reset and apply changes in the end because nested
                // elements' widths wouldn't be calculatable otherwise
                if (!nested) {
                    $menu.find('ul').addBack().css({
                        position: '',
                        display: '',
                        minWidth: '',
                        maxWidth: ''
                    }).outerWidth(function () {
                        return $(this).data('width');
                    });
                }
            },
            update: function (opt, root) {
                var $trigger = this;
                if (typeof root === 'undefined') {
                    root = opt;
                    op.resize(opt.$menu);
                }
                // re-check disabled for each item
                opt.$menu.children().each(function () {
                    var $item = $(this),
                        key = $item.data('contextMenuKey'),
                        item = opt.items[key],
                        disabled = ($.isFunction(item.disabled) && item.disabled.call($trigger, key, root)) || item.disabled === true,
                        visible;
                    if ($.isFunction(item.visible)) {
                        visible = item.visible.call($trigger, key, root);
                    } else if (typeof item.visible !== 'undefined') {
                        visible = item.visible === true;
                    } else {
                        visible = true;
                    }
                    $item[visible ? 'show' : 'hide']();

                    // dis- / enable item
                    $item[disabled ? 'addClass' : 'removeClass'](root.classNames.disabled);

                    if ($.isFunction(item.icon)) {
                        $item.removeClass(item._icon);
                        item._icon = item.icon.call(this, $trigger, $item, key, item);
                        $item.addClass(item._icon);
                    }

                    if (item.type) {
                        // dis- / enable input elements
                        $item.find('input, select, textarea').prop('disabled', disabled);

                        // update input states
                        switch (item.type) {
                            case 'text':
                            case 'textarea':
                                item.$input.val(item.value || '');
                                break;

                            case 'checkbox':
                            case 'radio':
                                item.$input.val(item.value || '').prop('checked', !!item.selected);
                                break;

                            case 'select':
                                item.$input.val((item.selected === 0 ? "0" : item.selected) || '');
                                break;
                        }
                    }

                    if (item.$menu) {
                        // update sub-menu
                        op.update.call($trigger, item, root);
                    }
                });
            },
            layer: function (opt, zIndex) {
                // add transparent layer for click area
                // filter and background for Internet Explorer, Issue #23
                var $layer = opt.$layer = $('<div id="context-menu-layer"></div>')
                    .css({
                        height: $win.height(),
                        width: $win.width(),
                        display: 'block',
                        position: 'fixed',
                        'z-index': zIndex,
                        top: 0,
                        left: 0,
                        opacity: 0,
                        filter: 'alpha(opacity=0)',
                        'background-color': '#000'
                    })
                    .data('contextMenuRoot', opt)
                    .insertBefore(this)
                    .on('contextmenu', handle.abortevent)
                    .on('mousedown', handle.layerClick);

                // IE6 doesn't know position:fixed;
                if (typeof document.body.style.maxWidth === 'undefined') { // IE6 doesn't support maxWidth
                    $layer.css({
                        'position': 'absolute',
                        'height': $(document).height()
                    });
                }

                return $layer;
            },
            processPromises: function (opt, root, promise) {
                // Start
                opt.$node.addClass(root.classNames.iconLoadingClass);

                function completedPromise(opt, root, items) {
                    // Completed promise (dev called promise.resolve). We now have a list of items which can
                    // be used to create the rest of the context menu.
                    if (typeof items === 'undefined') {
                        // Null result, dev should have checked
                        errorPromise(undefined);//own error object
                    }
                    finishPromiseProcess(opt, root, items);
                }

                function errorPromise(opt, root, errorItem) {
                    // User called promise.reject() with an error item, if not, provide own error item.
                    if (typeof errorItem === 'undefined') {
                        errorItem = {
                            "error": {
                                name: "No items and no error item",
                                icon: "context-menu-icon context-menu-icon-quit"
                            }
                        };
                        if (window.console) {
                            (console.error || console.log).call(console, 'When you reject a promise, provide an "items" object, equal to normal sub-menu items');
                        }
                    } else if (typeof errorItem === 'string') {
                        errorItem = {"error": {name: errorItem}};
                    }
                    finishPromiseProcess(opt, root, errorItem);
                }

                function finishPromiseProcess(opt, root, items) {
                    if (typeof root.$menu === 'undefined' || !root.$menu.is(':visible')) {
                        return;
                    }
                    opt.$node.removeClass(root.classNames.iconLoadingClass);
                    opt.items = items;
                    op.create(opt, root, true); // Create submenu
                    op.update(opt, root); // Correctly update position if user is already hovered over menu item
                    root.positionSubmenu.call(opt.$node, opt.$menu); // positionSubmenu, will only do anything if user already hovered over menu item that just got new subitems.
                }

                // Wait for promise completion. .then(success, error, notify) (we don't track notify). Bind the opt
                // and root to avoid scope problems
                promise.then(completedPromise.bind(this, opt, root), errorPromise.bind(this, opt, root));
            }
        };

    // split accesskey according to http://www.whatwg.org/specs/web-apps/current-work/multipage/editing.html#assigned-access-key
    function splitAccesskey(val) {
        var t = val.split(/\s+/);
        var keys = [];

        for (var i = 0, k; k = t[i]; i++) {
            k = k.charAt(0).toUpperCase(); // first character only
            // theoretically non-accessible characters should be ignored, but different systems, different keyboard layouts, ... screw it.
            // a map to look up already used access keys would be nice
            keys.push(k);
        }

        return keys;
    }

// handle contextMenu triggers
    $.fn.contextMenu = function (operation) {
        var $t = this, $o = operation;
        if (this.length > 0) {  // this is not a build on demand menu
            if (typeof operation === 'undefined') {
                this.first().trigger('contextmenu');
            } else if (typeof operation.x !== 'undefined' && typeof operation.y !== 'undefined') {
                this.first().trigger($.Event('contextmenu', {
                    pageX: operation.x,
                    pageY: operation.y,
                    mouseButton: operation.button
                }));
            } else if (operation === 'hide') {
                var $menu = this.first().data('contextMenu') ? this.first().data('contextMenu').$menu : null;
                if ($menu) {
                    $menu.trigger('contextmenu:hide');
                }
            } else if (operation === 'destroy') {
                $.contextMenu('destroy', {context: this});
            } else if ($.isPlainObject(operation)) {
                operation.context = this;
                $.contextMenu('create', operation);
            } else if (operation) {
                this.removeClass('context-menu-disabled');
            } else if (!operation) {
                this.addClass('context-menu-disabled');
            }
        } else {
            $.each(menus, function () {
                if (this.selector === $t.selector) {
                    $o.data = this;

                    $.extend($o.data, {trigger: 'demand'});
                }
            });

            handle.contextmenu.call($o.target, $o);
        }

        return this;
    };

    // manage contextMenu instances
    $.contextMenu = function (operation, options) {
        if (typeof operation !== 'string') {
            options = operation;
            operation = 'create';
        }

        if (typeof options === 'string') {
            options = {selector: options};
        } else if (typeof options === 'undefined') {
            options = {};
        }

        // merge with default options
        var o = $.extend(true, {}, defaults, options || {});
        var $document = $(document);
        var $context = $document;
        var _hasContext = false;

        if (!o.context || !o.context.length) {
            o.context = document;
        } else {
            // you never know what they throw at you...
            $context = $(o.context).first();
            o.context = $context.get(0);
            _hasContext = !$(o.context).is(document);
        }

        switch (operation) {
            case 'create':
                // no selector no joy
                if (!o.selector) {
                    throw new Error('No selector specified');
                }
                // make sure internal classes are not bound to
                if (o.selector.match(/.context-menu-(list|item|input)($|\s)/)) {
                    throw new Error('Cannot bind to selector "' + o.selector + '" as it contains a reserved className');
                }
                if (!o.build && (!o.items || $.isEmptyObject(o.items))) {
                    throw new Error('No Items specified');
                }
                counter++;
                o.ns = '.contextMenu' + counter;
                if (!_hasContext) {
                    namespaces[o.selector] = o.ns;
                }
                menus[o.ns] = o;

                // default to right click
                if (!o.trigger) {
                    o.trigger = 'right';
                }

                if (!initialized) {
                    var itemClick = o.itemClickEvent === 'click' ? 'click.contextMenu' : 'mouseup.contextMenu';
                    var contextMenuItemObj = {
                        // 'mouseup.contextMenu': handle.itemClick,
                        // 'click.contextMenu': handle.itemClick,
                        'contextmenu:focus.contextMenu': handle.focusItem,
                        'contextmenu:blur.contextMenu': handle.blurItem,
                        'contextmenu.contextMenu': handle.abortevent,
                        'mouseenter.contextMenu': handle.itemMouseenter,
                        'mouseleave.contextMenu': handle.itemMouseleave
                    };
                    contextMenuItemObj[itemClick] = handle.itemClick;
                    // make sure item click is registered first
                    $document
                        .on({
                            'contextmenu:hide.contextMenu': handle.hideMenu,
                            'prevcommand.contextMenu': handle.prevItem,
                            'nextcommand.contextMenu': handle.nextItem,
                            'contextmenu.contextMenu': handle.abortevent,
                            'mouseenter.contextMenu': handle.menuMouseenter,
                            'mouseleave.contextMenu': handle.menuMouseleave
                        }, '.context-menu-list')
                        .on('mouseup.contextMenu', '.context-menu-input', handle.inputClick)
                        .on(contextMenuItemObj, '.context-menu-item');

                    initialized = true;
                }

                // engage native contextmenu event
                $context
                    .on('contextmenu' + o.ns, o.selector, o, handle.contextmenu);

                if (_hasContext) {
                    // add remove hook, just in case
                    $context.on('remove' + o.ns, function () {
                        $(this).contextMenu('destroy');
                    });
                }

                switch (o.trigger) {
                    case 'hover':
                        $context
                            .on('mouseenter' + o.ns, o.selector, o, handle.mouseenter)
                            .on('mouseleave' + o.ns, o.selector, o, handle.mouseleave);
                        break;

                    case 'left':
                        $context.on('click' + o.ns, o.selector, o, handle.click);
                        break;
                    case 'touchstart':
                        $context.on('touchstart' + o.ns, o.selector, o, handle.click);
                        break;
                    /*
                     default:
                     // http://www.quirksmode.org/dom/events/contextmenu.html
                     $document
                     .on('mousedown' + o.ns, o.selector, o, handle.mousedown)
                     .on('mouseup' + o.ns, o.selector, o, handle.mouseup);
                     break;
                     */
                }

                // create menu
                if (!o.build) {
                    op.create(o);
                }
                break;

            case 'destroy':
                var $visibleMenu;
                if (_hasContext) {
                    // get proper options
                    var context = o.context;
                    $.each(menus, function (ns, o) {

                        if (!o) {
                            return true;
                        }

                        // Is this menu equest to the context called from
                        if (!$(context).is(o.selector)) {
                            return true;
                        }

                        $visibleMenu = $('.context-menu-list').filter(':visible');
                        if ($visibleMenu.length && $visibleMenu.data().contextMenuRoot.$trigger.is($(o.context).find(o.selector))) {
                            $visibleMenu.trigger('contextmenu:hide', {force: true});
                        }

                        try {
                            if (menus[o.ns].$menu) {
                                menus[o.ns].$menu.remove();
                            }

                            delete menus[o.ns];
                        } catch (e) {
                            menus[o.ns] = null;
                        }

                        $(o.context).off(o.ns);

                        return true;
                    });
                } else if (!o.selector) {
                    $document.off('.contextMenu .contextMenuAutoHide');
                    $.each(menus, function (ns, o) {
                        $(o.context).off(o.ns);
                    });

                    namespaces = {};
                    menus = {};
                    counter = 0;
                    initialized = false;

                    $('#context-menu-layer, .context-menu-list').remove();
                } else if (namespaces[o.selector]) {
                    $visibleMenu = $('.context-menu-list').filter(':visible');
                    if ($visibleMenu.length && $visibleMenu.data().contextMenuRoot.$trigger.is(o.selector)) {
                        $visibleMenu.trigger('contextmenu:hide', {force: true});
                    }

                    try {
                        if (menus[namespaces[o.selector]].$menu) {
                            menus[namespaces[o.selector]].$menu.remove();
                        }

                        delete menus[namespaces[o.selector]];
                    } catch (e) {
                        menus[namespaces[o.selector]] = null;
                    }

                    $document.off(namespaces[o.selector]);
                }
                break;

            case 'html5':
                // if <command> and <menuitem> are not handled by the browser,
                // or options was a bool true,
                // initialize $.contextMenu for them
                if ((!$.support.htmlCommand && !$.support.htmlMenuitem) || (typeof options === 'boolean' && options)) {
                    $('menu[type="context"]').each(function () {
                        if (this.id) {
                            $.contextMenu({
                                selector: '[contextmenu=' + this.id + ']',
                                items: $.contextMenu.fromMenu(this)
                            });
                        }
                    }).css('display', 'none');
                }
                break;

            default:
                throw new Error('Unknown operation "' + operation + '"');
        }

        return this;
    };

// import values into <input> commands
    $.contextMenu.setInputValues = function (opt, data) {
        if (typeof data === 'undefined') {
            data = {};
        }

        $.each(opt.inputs, function (key, item) {
            switch (item.type) {
                case 'text':
                case 'textarea':
                    item.value = data[key] || '';
                    break;

                case 'checkbox':
                    item.selected = data[key] ? true : false;
                    break;

                case 'radio':
                    item.selected = (data[item.radio] || '') === item.value;
                    break;

                case 'select':
                    item.selected = data[key] || '';
                    break;
            }
        });
    };

// export values from <input> commands
    $.contextMenu.getInputValues = function (opt, data) {
        if (typeof data === 'undefined') {
            data = {};
        }

        $.each(opt.inputs, function (key, item) {
            switch (item.type) {
                case 'text':
                case 'textarea':
                case 'select':
                    data[key] = item.$input.val();
                    break;

                case 'checkbox':
                    data[key] = item.$input.prop('checked');
                    break;

                case 'radio':
                    if (item.$input.prop('checked')) {
                        data[item.radio] = item.value;
                    }
                    break;
            }
        });

        return data;
    };

// find <label for="xyz">
    function inputLabel(node) {
        return (node.id && $('label[for="' + node.id + '"]').val()) || node.name;
    }

// convert <menu> to items object
    function menuChildren(items, $children, counter) {
        if (!counter) {
            counter = 0;
        }

        $children.each(function () {
            var $node = $(this),
                node = this,
                nodeName = this.nodeName.toLowerCase(),
                label,
                item;

            // extract <label><input>
            if (nodeName === 'label' && $node.find('input, textarea, select').length) {
                label = $node.text();
                $node = $node.children().first();
                node = $node.get(0);
                nodeName = node.nodeName.toLowerCase();
            }

            /*
             * <menu> accepts flow-content as children. that means <embed>, <canvas> and such are valid menu items.
             * Not being the sadistic kind, $.contextMenu only accepts:
             * <command>, <menuitem>, <hr>, <span>, <p> <input [text, radio, checkbox]>, <textarea>, <select> and of course <menu>.
             * Everything else will be imported as an html node, which is not interfaced with contextMenu.
             */

            // http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#concept-command
            switch (nodeName) {
                // http://www.whatwg.org/specs/web-apps/current-work/multipage/interactive-elements.html#the-menu-element
                case 'menu':
                    item = {name: $node.attr('label'), items: {}};
                    counter = menuChildren(item.items, $node.children(), counter);
                    break;

                // http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#using-the-a-element-to-define-a-command
                case 'a':
                // http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#using-the-button-element-to-define-a-command
                case 'button':
                    item = {
                        name: $node.text(),
                        disabled: !!$node.attr('disabled'),
                        callback: (function () {
                            return function () {
                                $node.get(0).click()
                            };
                        })()
                    };
                    break;

                // http://www.whatwg.org/specs/web-apps/current-work/multipage/commands.html#using-the-command-element-to-define-a-command
                case 'menuitem':
                case 'command':
                    switch ($node.attr('type')) {
                        case undefined:
                        case 'command':
                        case 'menuitem':
                            item = {
                                name: $node.attr('label'),
                                disabled: !!$node.attr('disabled'),
                                icon: $node.attr('icon'),
                                callback: (function () {
                                    return function () {
                                        $node.get(0).click()
                                    };
                                })()
                            };
                            break;

                        case 'checkbox':
                            item = {
                                type: 'checkbox',
                                disabled: !!$node.attr('disabled'),
                                name: $node.attr('label'),
                                selected: !!$node.attr('checked')
                            };
                            break;
                        case 'radio':
                            item = {
                                type: 'radio',
                                disabled: !!$node.attr('disabled'),
                                name: $node.attr('label'),
                                radio: $node.attr('radiogroup'),
                                value: $node.attr('id'),
                                selected: !!$node.attr('checked')
                            };
                            break;

                        default:
                            item = undefined;
                    }
                    break;

                case 'hr':
                    item = '-------';
                    break;

                case 'input':
                    switch ($node.attr('type')) {
                        case 'text':
                            item = {
                                type: 'text',
                                name: label || inputLabel(node),
                                disabled: !!$node.attr('disabled'),
                                value: $node.val()
                            };
                            break;

                        case 'checkbox':
                            item = {
                                type: 'checkbox',
                                name: label || inputLabel(node),
                                disabled: !!$node.attr('disabled'),
                                selected: !!$node.attr('checked')
                            };
                            break;

                        case 'radio':
                            item = {
                                type: 'radio',
                                name: label || inputLabel(node),
                                disabled: !!$node.attr('disabled'),
                                radio: !!$node.attr('name'),
                                value: $node.val(),
                                selected: !!$node.attr('checked')
                            };
                            break;

                        default:
                            item = undefined;
                            break;
                    }
                    break;

                case 'select':
                    item = {
                        type: 'select',
                        name: label || inputLabel(node),
                        disabled: !!$node.attr('disabled'),
                        selected: $node.val(),
                        options: {}
                    };
                    $node.children().each(function () {
                        item.options[this.value] = $(this).text();
                    });
                    break;

                case 'textarea':
                    item = {
                        type: 'textarea',
                        name: label || inputLabel(node),
                        disabled: !!$node.attr('disabled'),
                        value: $node.val()
                    };
                    break;

                case 'label':
                    break;

                default:
                    item = {type: 'html', html: $node.clone(true)};
                    break;
            }

            if (item) {
                counter++;
                items['key' + counter] = item;
            }
        });

        return counter;
    }

// convert html5 menu
    $.contextMenu.fromMenu = function (element) {
        var $this = $(element),
            items = {};

        menuChildren(items, $this.children());

        return items;
    };

// make defaults accessible
    $.contextMenu.defaults = defaults;
    $.contextMenu.types = types;
// export internal functions - undocumented, for hacking only!
    $.contextMenu.handle = handle;
    $.contextMenu.op = op;
    $.contextMenu.menus = menus;
});


/* END jQuery Context Menu */
// ######################################################################################################### //







// ######################################################################################################### //
/* START jQuery Non-Obfusicating Alert Box (Generic Implementation) */

/*
* Project: Bootstrap Notify = v3.1.5
* Description: Turns standard Bootstrap alerts into "Growl-like" notifications.
* Author: Mouse0270 aka Robert McIntosh
* License: MIT License
* Website: https://github.com/mouse0270/bootstrap-growl
*/

/* global define:false, require: false, jQuery:false */

(function (factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS
        factory(require('jquery'));
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function ($) {
    // Create the defaults once
    var defaults = {
        element: 'body',
        position: null,
        type: "info",
        allow_dismiss: true,
        allow_duplicates: true,
        newest_on_top: false,
        showProgressbar: false,
        placement: {
            from: "top",
            align: "right"
        },
        offset: 20,
        spacing: 10,
        z_index: 1031,
        delay: 5000,
        timer: 1000,
        url_target: '_blank',
        mouse_over: null,
        animate: {
            enter: 'animated fadeInDown',
            exit: 'animated fadeOutUp'
        },
        onShow: null,
        onShown: null,
        onClose: null,
        onClosed: null,
        onClick: null,
        icon_type: 'class',
        template: '<div data-notify="container" class="col-xs-11 col-sm-4 alert alert-{0}" role="alert"><button type="button" aria-hidden="true" class="close" data-notify="dismiss">&times;</button><span data-notify="icon"></span> <span data-notify="title">{1}</span> <span data-notify="message">{2}</span><div class="progress" data-notify="progressbar"><div class="progress-bar progress-bar-{0}" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div><a href="{3}" target="{4}" data-notify="url"></a></div>'
    };

    String.format = function () {
        var args = arguments;
        var str = arguments[0];
        return str.replace(/(\{\{\d\}\}|\{\d\})/g, function (str) {
            if (str.substring(0, 2) === "{{") return str;
            var num = parseInt(str.match(/\d/)[0]);
            return args[num + 1];
        });
    };

    function isDuplicateNotification(notification) {
        var isDupe = false;

        $('[data-notify="container"]').each(function (i, el) {
            var $el = $(el);
            var title = $el.find('[data-notify="title"]').html().trim();
            var message = $el.find('[data-notify="message"]').html().trim();

            // The input string might be different than the actual parsed HTML string!
            // (<br> vs <br /> for example)
            // So we have to force-parse this as HTML here!
            var isSameTitle = title === $("<div>" + notification.settings.content.title + "</div>").html().trim();
            var isSameMsg = message === $("<div>" + notification.settings.content.message + "</div>").html().trim();
            var isSameType = $el.hasClass('alert-' + notification.settings.type);

            if (isSameTitle && isSameMsg && isSameType) {
                //we found the dupe. Set the var and stop checking.
                isDupe = true;
            }
            return !isDupe;
        });

        return isDupe;
    }

    function Notify(element, content, options) {
        // Setup Content of Notify
        var contentObj = {
            content: {
                message: typeof content === 'object' ? content.message : content,
                title: content.title ? content.title : '',
                icon: content.icon ? content.icon : '',
                url: content.url ? content.url : '#',
                target: content.target ? content.target : '-'
            }
        };

        options = $.extend(true, {}, contentObj, options);
        this.settings = $.extend(true, {}, defaults, options);
        this._defaults = defaults;
        if (this.settings.content.target === "-") {
            this.settings.content.target = this.settings.url_target;
        }
        this.animations = {
            start: 'webkitAnimationStart oanimationstart MSAnimationStart animationstart',
            end: 'webkitAnimationEnd oanimationend MSAnimationEnd animationend'
        };

        if (typeof this.settings.offset === 'number') {
            this.settings.offset = {
                x: this.settings.offset,
                y: this.settings.offset
            };
        }

        //if duplicate messages are not allowed, then only continue if this new message is not a duplicate of one that it already showing
        if (this.settings.allow_duplicates || (!this.settings.allow_duplicates && !isDuplicateNotification(this))) {
            this.init();
        }
    }

    $.extend(Notify.prototype, {
        init: function () {
            var self = this;

            this.buildNotify();
            if (this.settings.content.icon) {
                this.setIcon();
            }
            if (this.settings.content.url != "#") {
                this.styleURL();
            }
            this.styleDismiss();
            this.placement();
            this.bind();

            this.notify = {
                $ele: this.$ele,
                update: function (command, update) {
                    var commands = {};
                    if (typeof command === "string") {
                        commands[command] = update;
                    } else {
                        commands = command;
                    }
                    for (var cmd in commands) {
                        switch (cmd) {
                            case "type":
                                this.$ele.removeClass('alert-' + self.settings.type);
                                this.$ele.find('[data-notify="progressbar"] > .progress-bar').removeClass('progress-bar-' + self.settings.type);
                                self.settings.type = commands[cmd];
                                this.$ele.addClass('alert-' + commands[cmd]).find('[data-notify="progressbar"] > .progress-bar').addClass('progress-bar-' + commands[cmd]);
                                break;
                            case "icon":
                                var $icon = this.$ele.find('[data-notify="icon"]');
                                if (self.settings.icon_type.toLowerCase() === 'class') {
                                    $icon.removeClass(self.settings.content.icon).addClass(commands[cmd]);
                                } else {
                                    if (!$icon.is('img')) {
                                        $icon.find('img');
                                    }
                                    $icon.attr('src', commands[cmd]);
                                }
                                self.settings.content.icon = commands[command];
                                break;
                            case "progress":
                                var newDelay = self.settings.delay - (self.settings.delay * (commands[cmd] / 100));
                                this.$ele.data('notify-delay', newDelay);
                                this.$ele.find('[data-notify="progressbar"] > div').attr('aria-valuenow', commands[cmd]).css('width', commands[cmd] + '%');
                                break;
                            case "url":
                                this.$ele.find('[data-notify="url"]').attr('href', commands[cmd]);
                                break;
                            case "target":
                                this.$ele.find('[data-notify="url"]').attr('target', commands[cmd]);
                                break;
                            default:
                                this.$ele.find('[data-notify="' + cmd + '"]').html(commands[cmd]);
                        }
                    }
                    var posX = this.$ele.outerHeight() + parseInt(self.settings.spacing) + parseInt(self.settings.offset.y);
                    self.reposition(posX);
                },
                close: function () {
                    self.close();
                }
            };

        },
        buildNotify: function () {
            var content = this.settings.content;
            this.$ele = $(String.format(this.settings.template, this.settings.type, content.title, content.message, content.url, content.target));
            this.$ele.attr('data-notify-position', this.settings.placement.from + '-' + this.settings.placement.align);
            if (!this.settings.allow_dismiss) {
                this.$ele.find('[data-notify="dismiss"]').css('display', 'none');
            }
            if ((this.settings.delay <= 0 && !this.settings.showProgressbar) || !this.settings.showProgressbar) {
                this.$ele.find('[data-notify="progressbar"]').remove();
            }
        },
        setIcon: function () {
            if (this.settings.icon_type.toLowerCase() === 'class') {
                this.$ele.find('[data-notify="icon"]').addClass(this.settings.content.icon);
            } else {
                if (this.$ele.find('[data-notify="icon"]').is('img')) {
                    this.$ele.find('[data-notify="icon"]').attr('src', this.settings.content.icon);
                } else {
                    this.$ele.find('[data-notify="icon"]').append('<img src="' + this.settings.content.icon + '" alt="Notify Icon" />');
                }
            }
        },
        styleDismiss: function () {
            this.$ele.find('[data-notify="dismiss"]').css({
                position: 'absolute',
                right: '10px',
                top: '5px',
                zIndex: this.settings.z_index + 2
            });
        },
        styleURL: function () {
            this.$ele.find('[data-notify="url"]').css({
                backgroundImage: 'url(data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7)',
                height: '100%',
                left: 0,
                position: 'absolute',
                top: 0,
                width: '100%',
                zIndex: this.settings.z_index + 1
            });
        },
        placement: function () {
            var self = this,
                offsetAmt = this.settings.offset.y,
                css = {
                    display: 'inline-block',
                    margin: '0px auto',
                    position: this.settings.position ? this.settings.position : (this.settings.element === 'body' ? 'fixed' : 'absolute'),
                    transition: 'all .5s ease-in-out',
                    zIndex: this.settings.z_index
                },
                hasAnimation = false,
                settings = this.settings;

            $('[data-notify-position="' + this.settings.placement.from + '-' + this.settings.placement.align + '"]:not([data-closing="true"])').each(function () {
                offsetAmt = Math.max(offsetAmt, parseInt($(this).css(settings.placement.from)) + parseInt($(this).outerHeight()) + parseInt(settings.spacing));
            });
            if (this.settings.newest_on_top === true) {
                offsetAmt = this.settings.offset.y;
            }
            css[this.settings.placement.from] = offsetAmt + 'px';

            switch (this.settings.placement.align) {
                case "left":
                case "right":
                    css[this.settings.placement.align] = this.settings.offset.x + 'px';
                    break;
                case "center":
                    css.left = 0;
                    css.right = 0;
                    break;
            }
            this.$ele.css(css).addClass(this.settings.animate.enter);
            $.each(Array('webkit-', 'moz-', 'o-', 'ms-', ''), function (index, prefix) {
                self.$ele[0].style[prefix + 'AnimationIterationCount'] = 1;
            });

            $(this.settings.element).append(this.$ele);

            if (this.settings.newest_on_top === true) {
                offsetAmt = (parseInt(offsetAmt) + parseInt(this.settings.spacing)) + this.$ele.outerHeight();
                this.reposition(offsetAmt);
            }

            if ($.isFunction(self.settings.onShow)) {
                self.settings.onShow.call(this.$ele);
            }

            this.$ele.one(this.animations.start, function () {
                hasAnimation = true;
            }).one(this.animations.end, function () {
                self.$ele.removeClass(self.settings.animate.enter);
                if ($.isFunction(self.settings.onShown)) {
                    self.settings.onShown.call(this);
                }
            });

            setTimeout(function () {
                if (!hasAnimation) {
                    if ($.isFunction(self.settings.onShown)) {
                        self.settings.onShown.call(this);
                    }
                }
            }, 600);
        },
        bind: function () {
            var self = this;

            this.$ele.find('[data-notify="dismiss"]').on('click', function () {
                self.close();
            });

            if ($.isFunction(self.settings.onClick)) {
                this.$ele.on('click', function (event) {
                    if (event.target != self.$ele.find('[data-notify="dismiss"]')[0]) {
                        self.settings.onClick.call(this, event);
                    }
                });
            }

            this.$ele.mouseover(function () {
                $(this).data('data-hover', "true");
            }).mouseout(function () {
                $(this).data('data-hover', "false");
            });
            this.$ele.data('data-hover', "false");

            if (this.settings.delay > 0) {
                self.$ele.data('notify-delay', self.settings.delay);
                var timer = setInterval(function () {
                    var delay = parseInt(self.$ele.data('notify-delay')) - self.settings.timer;
                    if ((self.$ele.data('data-hover') === 'false' && self.settings.mouse_over === "pause") || self.settings.mouse_over != "pause") {
                        var percent = ((self.settings.delay - delay) / self.settings.delay) * 100;
                        self.$ele.data('notify-delay', delay);
                        self.$ele.find('[data-notify="progressbar"] > div').attr('aria-valuenow', percent).css('width', percent + '%');
                    }
                    if (delay <= -(self.settings.timer)) {
                        clearInterval(timer);
                        self.close();
                    }
                }, self.settings.timer);
            }
        },
        close: function () {
            var self = this,
                posX = parseInt(this.$ele.css(this.settings.placement.from)),
                hasAnimation = false;

            this.$ele.attr('data-closing', 'true').addClass(this.settings.animate.exit);
            self.reposition(posX);

            if ($.isFunction(self.settings.onClose)) {
                self.settings.onClose.call(this.$ele);
            }

            this.$ele.one(this.animations.start, function () {
                hasAnimation = true;
            }).one(this.animations.end, function () {
                $(this).remove();
                if ($.isFunction(self.settings.onClosed)) {
                    self.settings.onClosed.call(this);
                }
            });

            setTimeout(function () {
                if (!hasAnimation) {
                    self.$ele.remove();
                    if ($.isFunction(self.settings.onClosed)) {
                        self.settings.onClosed.call(this);
                    }
                }
            }, 600);
        },
        reposition: function (posX) {
            var self = this,
                notifies = '[data-notify-position="' + this.settings.placement.from + '-' + this.settings.placement.align + '"]:not([data-closing="true"])',
                $elements = this.$ele.nextAll(notifies);
            if (this.settings.newest_on_top === true) {
                $elements = this.$ele.prevAll(notifies);
            }
            $elements.each(function () {
                $(this).css(self.settings.placement.from, posX);
                posX = (parseInt(posX) + parseInt(self.settings.spacing)) + $(this).outerHeight();
            });
        }
    });

    $.notify = function (content, options) {
        var plugin = new Notify(this, content, options);
        return plugin.notify;
    };
    $.notifyDefaults = function (options) {
        defaults = $.extend(true, {}, defaults, options);
        return defaults;
    };

    $.notifyClose = function (selector) {

        if (typeof selector === "undefined" || selector === "all") {
            $('[data-notify]').find('[data-notify="dismiss"]').trigger('click');
        }else if(selector === 'success' || selector === 'info' || selector === 'warning' || selector === 'danger'){
            $('.alert-' + selector + '[data-notify]').find('[data-notify="dismiss"]').trigger('click');
        } else if(selector){
            $(selector + '[data-notify]').find('[data-notify="dismiss"]').trigger('click');
        }
        else {
            $('[data-notify-position="' + selector + '"]').find('[data-notify="dismiss"]').trigger('click');
        }
    };

    $.notifyCloseExcept = function (selector) {

        if(selector === 'success' || selector === 'info' || selector === 'warning' || selector === 'danger'){
            $('[data-notify]').not('.alert-' + selector).find('[data-notify="dismiss"]').trigger('click');
        } else{
            $('[data-notify]').not(selector).find('[data-notify="dismiss"]').trigger('click');
        }
    };


}));


/* END jQuery Non-Obfusicating Alert Box (Generic Implementation) */
// ######################################################################################################### //



jQuery.fn.extend({autocompleteHelper : function(resourceName, defaultId) {
    var _this = $('<input class="js-typeahead form-control" type="search" autocomplete="off" />');

    if (this.attr('name'))
        _this.attr('name', this.attr('name'));
    if (this.attr('id'))
        _this.attr('id', this.attr('id'));

    var container = $('<div class="typeahead__container form-control-container">').append(
        $('<div class="typeahead__field">').append(
            $('<span class="typeahead__query">').append(
                _this
            )
        )
    );

    this.replaceWith(container);

    _this.typeahead({
        display: ['name'],
        order: 'asc',
        minLength : 1,
        dynamic: true,
        source: {
            users: {
                ajax: {
                    url: fimApi.directory + 'api/acHelper.php',
                    data: {
                        'access_token': fimApi.lastSessionHash,
                        'list': resourceName,
                        'search': '{{query}}'
                    },
                    path: 'entries'
                }
            }
        },
        template : function (query, item) {
            if (item.avatar)
                return '<span class="userName userNameAvatar"><img src="{{avatar}}" style="max-width: 20px; max-height: 20px;"/> <span>{{name}}</span></span>';
            else
                return '<span>{{name}}</span>';
        },
        callback : {
            onClick : function(node, a, item, event) {
                $(node).val(item.name);
                $(node).attr('data-id', item.id);
                $(node).attr('data-value', item.name);
                $(node).removeClass('is-invalid');

                $(node).trigger('autocompleteChange').change();
            },
            onCancel : function(node, event) {
                $(node).attr('data-id', '');
                $(node).attr('data-value', '');
                $(node).removeClass('is-invalid');

                $(node).trigger('autocompleteChange').change();
            }
        }
    });
        _this.on('change', function(event) {console.log('change', event)});


    _this.off('keyup.autocompleteHelper').on('keyup.autocompleteHelper', function(event) {
        if ($(event.target).attr('data-value') != $(event.target).val()) {
            $(event.target).removeClass('is-invalid');
            $(event.target).attr('data-id', '');
            $(event.target).attr('data-value', '');
        }
    });


    function resolveInput(target, callback) {
        if (!target.val()) {
            target.attr('data-id', '');
            target.attr('data-value', '');

            target.removeClass('is-invalid');

            target.trigger('autocompleteChange');

            if (callback) callback();//
        }
        else {
            $.when(Resolver.resolveFromName(resourceName, target.val())).then(function (pairs) {
                if (pairs[target.val()]) {
                    target.attr('data-value', pairs[target.val()].name);
                    target.attr('data-id', pairs[target.val()].id);

                    target.removeClass('is-invalid');

                    target.trigger('autocompleteChange');

                    if (callback) callback();
                    //target.popover('dispose');
                }
                else {
                    target.attr('data-id', '');
                    target.attr('data-value', '');

                    target.addClass('is-invalid');

                    if (callback) callback();
                }
            });
        }
    }


    // Catch form submissions in order to resolve manually-inputted data
    _this.closest('form').off('submit.autocompleteHelper').on('submit.autocompleteHelper', function(event) {

        if (_this.val() && !(_this.attr('data-id')) && !_this.hasClass('is-invalid')) {
            console.log("fetcher invalid");
            event.stopImmediatePropagation();

            // Refire the submit when we've resolved the text.
            resolveInput(_this, function() {
                // Re-trigger the event
                $(event.target).trigger('submit');
            });

            // Prevent the event from continuing (since we have to wait on a promise before we can finish this callback)
            return false;
        }

        return true;
    });


    // Catch change events in order to resolve manually-inputted data
    /*_this.off('change.autocompleteHelper').on('change.autocompleteHelper', function(event) {
        resolveInput($(event.target));
    });*/


    // Set the initial value of the form field, if needed
    if (defaultId) {
        $.when(Resolver.resolveFromId(resourceName, defaultId)).then(function(pairs) {
            _this.val(pairs[defaultId].name);
            _this.attr('data-value', pairs[defaultId].name);
            _this.attr('data-id', defaultId);
        });
    }
    else {
        _this.val('');
        _this.attr('data-id', '');
        _this.attr('data-value', '');
    }


    return _this;
}});






// ######################################################################################################### //
/* Start Generic Notify
 * Joseph Todd Parsons
 * http://www.gnu.org/licenses/gpl.html */

var notify = {
    webkitNotifySupported: function() {
        return "Notification" in window;
    },

    webkitNotifyRequest: function () {
        window.Notification.requestPermission();
    },

    webkitNotify: function (icon, title, notifyData) {
        if (window.Notification.permission != "granted") {
            notify.webkitNotifyRequest();
        }
        else {
            new window.Notification(title, {
                body : notifyData,
                icon : icon,
                tag : "fm-notify"
            });
        }
    }
};

/* End Generic Notify */
// ######################################################################################################### //











// ######################################################################################################### //

/* Start Dia -- Simplified jQueryUI Dialogues
 * Joseph T. Parsons
 * http://www.gnu7.org/licenses/gpl.html */
var dia = {
    exception: function(exception) {
        $('#modal-dynamicException .modal-title').html('Exception: ' + exception.string);
        $('#modal-dynamicException .modal-body').html(exception.details);
        $('#modal-dynamicException').modal();

        console.log("Stack trace for " + exception.string + ":");
        console.log(exception.trace);
    },

    error: function (message) {
        console.log('Error: ' + message);

        $('#modal-dynamicError .modal-body').html(message);
        $('#modal-dynamicError').modal();
    },

    info: function (message, type) {
        $.notify({
            message : message
        }, {
            type : (type ? type : "info"),
            placement: {
                from : 'top',
                align : 'center',
            },
            delay : 3000,
        });
    },

    confirm: function (options, title) {
        $('#modal-dynamicConfirm .modal-title').text(title);
        $('#modal-dynamicConfirm .modal-body').html(options.text);

        $('#modal-dynamicConfirm button[name=confirm]').off('click').on('click', function() {
            if (typeof options['true'] !== 'undefined') options['true']();

            $('#modal-dynamicConfirm').modal('hide');
        });

        $('#modal-dynamicConfirm button[name=cancel]').off('click').on('click', function() {
            if (typeof options['false'] !== 'undefined') options['false']();

            $('#modal-dynamicConfirm').modal('hide');
        });

        $('#modal-dynamicConfirm').modal();
    },

    // Supported options: autoShow (true), id, content, width (600), oF, cF
    full: function (options) {
        $('#modal-dynamicFull .modal-title').text(options.title);
        $('#modal-dynamicFull .modal-body').html(options.content);

        $('#modal-dynamicFull').modal();

        $('#modal-dynamicFull .modal-footer').html('');
        jQuery.each(options.buttons, function(buttonName, buttonAction) {
            $('#modal-dynamicFull .modal-footer').append($('<button>').attr('class', 'btn btn-secondary').text(buttonName).click(function() {
                buttonAction();
                $('#modal-dynamicFull').modal('hide');
                return false;
            }));
        });

        if (typeof options.oF !== 'undefined') options.oF();

        if (typeof options.cF !== 'undefined') {
            $('#modal-dynamicFull').on('hidden.bs.modal', function () {
                options.cF();
            });
        }
    }
};

/* End Dia -- Simplified jQueryUI Dialogues */
// ######################################################################################################### //










// ######################################################################################################### //
/* Start HashChange Abstraction */

/*!
 * jQuery hashchange event - v1.3 - 7/21/2010
 * http://benalman.com/projects/jquery-hashchange-plugin/
 *
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */

// Script: jQuery hashchange event
//
// *Version: 1.3, Last updated: 7/21/2010*
//
// Project Home - http://benalman.com/projects/jquery-hashchange-plugin/
// GitHub       - http://github.com/cowboy/jquery-hashchange/
// Source       - http://github.com/cowboy/jquery-hashchange/raw/master/jquery.ba-hashchange.js
// (Minified)   - http://github.com/cowboy/jquery-hashchange/raw/master/jquery.ba-hashchange.min.js (0.8kb gzipped)
//
// About: License
//
// Copyright (c) 2010 "Cowboy" Ben Alman,
// Dual licensed under the MIT and GPL licenses.
// http://benalman.com/about/license/
//
// About: Examples
//
// These working examples, complete with fully commented code, illustrate a few
// ways in which this plugin can be used.
//
// hashchange event - http://benalman.com/code/projects/jquery-hashchange/examples/hashchange/
// document.domain - http://benalman.com/code/projects/jquery-hashchange/examples/document_domain/
//
// About: Support and Testing
//
// Information about what version or versions of jQuery this plugin has been
// tested with, what browsers it has been tested in, and where the unit tests
// reside (so you can test it yourself).
//
// jQuery Versions - 1.2.6, 1.3.2, 1.4.1, 1.4.2
// Browsers Tested - Internet Explorer 6-8, Firefox 2-4, Chrome 5-6, Safari 3.2-5,
//                   Opera 9.6-10.60, iPhone 3.1, Android 1.6-2.2, BlackBerry 4.6-5.
// Unit Tests      - http://benalman.com/code/projects/jquery-hashchange/unit/
//
// About: Known issues
//
// While this jQuery hashchange event implementation is quite stable and
// robust, there are a few unfortunate browser bugs surrounding expected
// hashchange event-based behaviors, independent of any JavaScript
// window.onhashchange abstraction. See the following examples for more
// information:
//
// Chrome: Back Button - http://benalman.com/code/projects/jquery-hashchange/examples/bug-chrome-back-button/
// Firefox: Remote XMLHttpRequest - http://benalman.com/code/projects/jquery-hashchange/examples/bug-firefox-remote-xhr/
// WebKit: Back Button in an Iframe - http://benalman.com/code/projects/jquery-hashchange/examples/bug-webkit-hash-iframe/
// Safari: Back Button from a different domain - http://benalman.com/code/projects/jquery-hashchange/examples/bug-safari-back-from-diff-domain/
//
// Also note that should a browser natively support the window.onhashchange
// event, but not report that it does, the fallback polling loop will be used.
//
// About: Release History
//
// 1.3.20140227 - Replaced msie check, which is no longer available -- JTP.
// 1.3   - (7/21/2010) Reorganized IE6/7 Iframe code to make it more
//         "removable" for mobile-only development. Added IE6/7 document.title
//         support. Attempted to make Iframe as hidden as possible by using
//         techniques from http://www.paciellogroup.com/blog/?p=604. Added
//         support for the "shortcut" format $(window).hashchange( fn ) and
//         $(window).hashchange() like jQuery provides for built-in events.
//         Renamed jQuery.hashchangeDelay to <jQuery.fn.hashchange.delay> and
//         lowered its default value to 50. Added <jQuery.fn.hashchange.domain>
//         and <jQuery.fn.hashchange.src> properties plus document-domain.html
//         file to address access denied issues when setting document.domain in
//         IE6/7.
// 1.2   - (2/11/2010) Fixed a bug where coming back to a page using this plugin
//         from a page on another domain would cause an error in Safari 4. Also,
//         IE6/7 Iframe is now inserted after the body (this actually works),
//         which prevents the page from scrolling when the event is first bound.
//         Event can also now be bound before DOM ready, but it won't be usable
//         before then in IE6/7.
// 1.1   - (1/21/2010) Incorporated document.documentMode test to fix IE8 bug
//         where browser version is incorrectly reported as 8.0, despite
//         inclusion of the X-UA-Compatible IE=EmulateIE7 meta tag.
// 1.0   - (1/9/2010) Initial Release. Broke out the jQuery BBQ event.special
//         window.onhashchange functionality into a separate plugin for users
//         who want just the basic event & back button support, without all the
//         extra awesomeness that BBQ provides. This plugin will be included as
//         part of jQuery BBQ, but also be available separately.

(function ($, window, undefined) {
    '$:nomunge'; // Used by YUI compressor.

    // Reused string.
    var str_hashchange = 'hashchange',

    // Method / object references.
        doc = document,
        fake_onhashchange,
        special = $.event.special,

    // Does the browser support window.onhashchange? Note that IE8 running in
    // IE7 compatibility mode reports true for 'onhashchange' in window, even
    // though the event isn't supported, so also test document.documentMode.
        doc_mode = doc.documentMode,
        supports_onhashchange = 'on' + str_hashchange in window && (doc_mode === undefined || doc_mode > 7);

    // Get location.hash (or what you'd expect location.hash to be) sans any
    // leading #. Thanks for making this necessary, Firefox!
    function get_fragment(url) {
        url = url || location.href;
        return '#' + url.replace(/^[^#]*#?(.*)$/, '$1');
    };

    // Method: jQuery.fn.hashchange
    //
    // Bind a handler to the window.onhashchange event or trigger all bound
    // window.onhashchange event handlers. This behavior is consistent with
    // jQuery's built-in event handlers.
    //
    // Usage:
    //
    // > jQuery(window).hashchange( [ handler ] );
    //
    // Arguments:
    //
    //  handler - (Function) Optional handler to be bound to the hashchange
    //    event. This is a "shortcut" for the more verbose form:
    //    jQuery(window).bind( 'hashchange', handler ). If handler is omitted,
    //    all bound window.onhashchange event handlers will be triggered. This
    //    is a shortcut for the more verbose
    //    jQuery(window).trigger( 'hashchange' ). These forms are described in
    //    the <hashchange event> section.
    //
    // Returns:
    //
    //  (jQuery) The initial jQuery collection of elements.

    // Allow the "shortcut" format $(elem).hashchange( fn ) for binding and
    // $(elem).hashchange() for triggering, like jQuery does for built-in events.
    $.fn[str_hashchange] = function (fn) {
        return fn ? this.bind(str_hashchange, fn) : this.trigger(str_hashchange);
    };

    // Property: jQuery.fn.hashchange.delay
    //
    // The numeric interval (in milliseconds) at which the <hashchange event>
    // polling loop executes. Defaults to 50.

    // Property: jQuery.fn.hashchange.domain
    //
    // If you're setting document.domain in your JavaScript, and you want hash
    // history to work in IE6/7, not only must this property be set, but you must
    // also set document.domain BEFORE jQuery is loaded into the page. This
    // property is only applicable if you are supporting IE6/7 (or IE8 operating
    // in "IE7 compatibility" mode).
    //
    // In addition, the <jQuery.fn.hashchange.src> property must be set to the
    // path of the included "document-domain.html" file, which can be renamed or
    // modified if necessary (note that the document.domain specified must be the
    // same in both your main JavaScript as well as in this file).
    //
    // Usage:
    //
    // jQuery.fn.hashchange.domain = document.domain;

    // Property: jQuery.fn.hashchange.src
    //
    // If, for some reason, you need to specify an Iframe src file (for example,
    // when setting document.domain as in <jQuery.fn.hashchange.domain>), you can
    // do so using this property. Note that when using this property, history
    // won't be recorded in IE6/7 until the Iframe src file loads. This property
    // is only applicable if you are supporting IE6/7 (or IE8 operating in "IE7
    // compatibility" mode).
    //
    // Usage:
    //
    // jQuery.fn.hashchange.src = 'path/to/file.html';

    $.fn[str_hashchange].delay = 50;
    /*
     $.fn[ str_hashchange ].domain = null;
     $.fn[ str_hashchange ].src = null;
     */

    // Event: hashchange event
    //
    // Fired when location.hash changes. In browsers that support it, the native
    // HTML5 window.onhashchange event is used, otherwise a polling loop is
    // initialized, running every <jQuery.fn.hashchange.delay> milliseconds to
    // see if the hash has changed. In IE6/7 (and IE8 operating in "IE7
    // compatibility" mode), a hidden Iframe is created to allow the back button
    // and hash-based history to work.
    //
    // Usage as described in <jQuery.fn.hashchange>:
    //
    // > // Bind an event handler.
    // > jQuery(window).hashchange( function(e) {
    // >   var hash = location.hash;
    // >   ...
    // > });
    // >
    // > // Manually trigger the event handler.
    // > jQuery(window).hashchange();
    //
    // A more verbose usage that allows for event namespacing:
    //
    // > // Bind an event handler.
    // > jQuery(window).bind( 'hashchange', function(e) {
    // >   var hash = location.hash;
    // >   ...
    // > });
    // >
    // > // Manually trigger the event handler.
    // > jQuery(window).trigger( 'hashchange' );
    //
    // Additional Notes:
    //
    // * The polling loop and Iframe are not created until at least one handler
    //   is actually bound to the 'hashchange' event.
    // * If you need the bound handler(s) to execute immediately, in cases where
    //   a location.hash exists on page load, via bookmark or page refresh for
    //   example, use jQuery(window).hashchange() or the more verbose
    //   jQuery(window).trigger( 'hashchange' ).
    // * The event can be bound before DOM ready, but since it won't be usable
    //   before then in IE6/7 (due to the necessary Iframe), recommended usage is
    //   to bind it inside a DOM ready handler.

    // Override existing $.event.special.hashchange methods (allowing this plugin
    // to be defined after jQuery BBQ in BBQ's source code).
    special[str_hashchange] = $.extend(special[str_hashchange], {

        // Called only when the first 'hashchange' event is bound to window.
        setup: function () {
            // If window.onhashchange is supported natively, there's nothing to do..
            if (supports_onhashchange) {
                return false;
            }

            // Otherwise, we need to create our own. And we don't want to call this
            // until the user binds to the event, just in case they never do, since it
            // will create a polling loop and possibly even a hidden Iframe.
            $(fake_onhashchange.start);
        },

        // Called only when the last 'hashchange' event is unbound from window.
        teardown: function () {
            // If window.onhashchange is supported natively, there's nothing to do..
            if (supports_onhashchange) {
                return false;
            }

            // Otherwise, we need to stop ours (if possible).
            $(fake_onhashchange.stop);
        }

    });

    // fake_onhashchange does all the work of triggering the window.onhashchange
    // event for browsers that don't natively support it, including creating a
    // polling loop to watch for hash changes and in IE 6/7 creating a hidden
    // Iframe to enable back and forward.
    fake_onhashchange = (function () {
        var self = {},
            timeout_id,

        // Remember the initial hash so it doesn't get triggered immediately.
            last_hash = get_fragment(),

            fn_retval = function (val) {
                return val;
            },
            history_set = fn_retval,
            history_get = fn_retval;

        // Start the polling loop.
        self.start = function () {
            timeout_id || poll();
        };

        // Stop the polling loop.
        self.stop = function () {
            timeout_id && clearTimeout(timeout_id);
            timeout_id = undefined;
        };

        // This polling loop checks every $.fn.hashchange.delay milliseconds to see
        // if location.hash has changed, and triggers the 'hashchange' event on
        // window when necessary.
        function poll() {
            var hash = get_fragment(),
                history_hash = history_get(last_hash);

            if (hash !== last_hash) {
                history_set(last_hash = hash, history_hash);

                $(window).trigger(str_hashchange);

            }
            else if (history_hash !== last_hash) {
                location.href = location.href.replace(/#.*/, '') + history_hash;
            }

            timeout_id = setTimeout(poll, $.fn[str_hashchange].delay);
        };

        // vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
        // vvvvvvvvvvvvvvvvvvv REMOVE IF NOT SUPPORTING IE6/7/8 vvvvvvvvvvvvvvvvvvv
        // vvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvvv
        navigator.userAgent.match(/msie [6,7,8]/i) && !supports_onhashchange && (function () { // TODO: Test change.
            // Not only do IE6/7 need the "magical" Iframe treatment, but so does IE8
            // when running in "IE7 compatibility" mode.

            var iframe,
                iframe_src;

            // When the event is bound and polling starts in IE 6/7, create a hidden
            // Iframe for history handling.
            self.start = function () {
                if (!iframe) {
                    iframe_src = $.fn[str_hashchange].src;
                    iframe_src = iframe_src && iframe_src + get_fragment();

                    // Create hidden Iframe. Attempt to make Iframe as hidden as possible
                    // by using techniques from http://www.paciellogroup.com/blog/?p=604.
                    iframe = $('<iframe tabindex="-1" title="empty"/>').hide()

                        // When Iframe has completely loaded, initialize the history and
                        // start polling.
                        .one('load', function () {
                            iframe_src || history_set(get_fragment());
                            poll();
                        })

                        // Load Iframe src if specified, otherwise nothing.
                        .attr('src', iframe_src || 'javascript:0')

                        // Append Iframe after the end of the body to prevent unnecessary
                        // initial page scrolling (yes, this works).
                        .insertAfter('body')[0].contentWindow;

                    // Whenever `document.title` changes, update the Iframe's title to
                    // prettify the back/next history menu entries. Since IE sometimes
                    // errors with "Unspecified error" the very first time this is set
                    // (yes, very useful) wrap this with a try/catch block.
                    doc.onpropertychange = function () {
                        try {
                            if (event.propertyName === 'title') {
                                iframe.document.title = doc.title;
                            }
                        }
                        catch (e) {}
                    };

                }
            };

            // Override the "stop" method since an IE6/7 Iframe was created. Even
            // if there are no longer any bound event handlers, the polling loop
            // is still necessary for back/next to work at all!
            self.stop = fn_retval;

            // Get history by looking at the hidden Iframe's location.hash.
            history_get = function () {
                return get_fragment(iframe.location.href);
            };

            // Set a new history item by opening and then closing the Iframe
            // document, *then* setting its location.hash. If document.domain has
            // been set, update that as well.
            history_set = function (hash, history_hash) {
                var iframe_doc = iframe.document,
                    domain = $.fn[str_hashchange].domain;

                if (hash !== history_hash) {
                    // Update Iframe with any initial `document.title` that might be set.
                    iframe_doc.title = doc.title;

                    // Opening the Iframe's document after it has been closed is what
                    // actually adds a history entry.
                    iframe_doc.open();

                    // Set document.domain for the Iframe document as well, if necessary.
                    domain && iframe_doc.write('<script>document.domain="' + domain + '"</script>');

                    iframe_doc.close();

                    // Update the Iframe's hash, for great justice.
                    iframe.location.hash = hash;
                }
            };

        })();
        // ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
        // ^^^^^^^^^^^^^^^^^^^ REMOVE IF NOT SUPPORTING IE6/7/8 ^^^^^^^^^^^^^^^^^^^
        // ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

        return self;
    })();

})(jQuery, this);

/* End HashChange Abstraction */
// ######################################################################################################### //














// ######################################################################################################### //
/* Start HashChange Abstraction */

/*jslint adsafe: false, bitwise: true, browser: true, cap: false, css: false,
 debug: false, devel: true, eqeqeq: true, es5: false, evil: false,
 forin: false, fragment: false, immed: true, laxbreak: false, newcap: true,
 nomen: false, on: false, onevar: true, passfail: false, plusplus: true,
 regexp: false, rhino: true, safe: false, strict: false, sub: false,
 undef: true, white: false, widget: false, windows: false */
/*global jQuery: false, window: false */

/*
 * Original code (c) 2010 Nick Galbreath
 * http://code.google.com/p/stringencoders/source/browse/#svn/trunk/javascript
 *
 * jQuery port (c) 2010 Carlo Zottmann
 * http://github.com/carlo/jquery-base64
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 */

/* base64 encode/decode compatible with window.btoa/atob
 *
 * window.atob/btoa is a Firefox extension to convert binary data (the "b")
 * to base64 (ascii, the "a").
 *
 * It is also found in Safari and Chrome.  It is not available in IE.
 *
 * if (!window.btoa) window.btoa = $.base64.encode
 * if (!window.atob) window.atob = $.base64.decode
 *
 * The original spec's for atob/btoa are a bit lacking
 * https://developer.mozilla.org/en/DOM/window.atob
 * https://developer.mozilla.org/en/DOM/window.btoa
 *
 * window.btoa and $.base64.encode takes a string where charCodeAt is [0,255]
 * If any character is not [0,255], then an exception is thrown.
 *
 * window.atob and $.base64.decode take a base64-encoded string
 * If the input length is not a multiple of 4, or contains invalid characters
 *   then an exception is thrown.
 */

jQuery.base64 = (function ($) {

    var _PADCHAR = "=",
        _ALPHA = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/",
        _VERSION = "1.0";


    function _getbyte64(s, i) {
        // This is oddly fast, except on Chrome/V8.
        // Minimal or no improvement in performance by using a
        // object with properties mapping chars to value (eg. 'A': 0)

        var idx = _ALPHA.indexOf(s.charAt(i));

        if (idx === -1) {
            throw "Cannot decode base64";
        }

        return idx;
    }


    function _decode(s) {
        var pads = 0,
            i,
            b10,
            imax = s.length,
            x = [];

        s = String(s);

        if (imax === 0) {
            return s;
        }

        if (imax % 4 !== 0) {
            throw "Cannot decode base64";
        }

        if (s.charAt(imax - 1) === _PADCHAR) {
            pads = 1;

            if (s.charAt(imax - 2) === _PADCHAR) {
                pads = 2;
            }

            // either way, we want to ignore this last block
            imax -= 4;
        }

        for (i = 0; i < imax; i += 4) {
            b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12) | (_getbyte64(s, i + 2) << 6) | _getbyte64(s, i + 3);
            x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 0xff, b10 & 0xff));
        }

        switch (pads) {
            case 1:
                b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12) | (_getbyte64(s, i + 2) << 6);
                x.push(String.fromCharCode(b10 >> 16, (b10 >> 8) & 0xff));
                break;

            case 2:
                b10 = (_getbyte64(s, i) << 18) | (_getbyte64(s, i + 1) << 12);
                x.push(String.fromCharCode(b10 >> 16));
                break;
        }

        return x.join("");
    }


    function _getbyte(s, i) {
        var x = s.charCodeAt(i);

        if (x > 255) {
            throw "INVALID_CHARACTER_ERR: DOM Exception 5";
        }

        return x;
    }


    function _encode(s) {
        if (arguments.length !== 1) {
            throw "SyntaxError: exactly one argument required";
        }

        s = String(s);

        var i,
            b10,
            x = [],
            imax = s.length - s.length % 3;

        if (s.length === 0) {
            return s;
        }

        for (i = 0; i < imax; i += 3) {
            b10 = (_getbyte(s, i) << 16) | (_getbyte(s, i + 1) << 8) | _getbyte(s, i + 2);
            x.push(_ALPHA.charAt(b10 >> 18));
            x.push(_ALPHA.charAt((b10 >> 12) & 0x3F));
            x.push(_ALPHA.charAt((b10 >> 6) & 0x3f));
            x.push(_ALPHA.charAt(b10 & 0x3f));
        }

        switch (s.length - imax) {
            case 1:
                b10 = _getbyte(s, i) << 16;
                x.push(_ALPHA.charAt(b10 >> 18) + _ALPHA.charAt((b10 >> 12) & 0x3F) + _PADCHAR + _PADCHAR);
                break;

            case 2:
                b10 = (_getbyte(s, i) << 16) | (_getbyte(s, i + 1) << 8);
                x.push(_ALPHA.charAt(b10 >> 18) + _ALPHA.charAt((b10 >> 12) & 0x3F) + _ALPHA.charAt((b10 >> 6) & 0x3f) + _PADCHAR);
                break;
        }

        return x.join("");
    }


    return {
        decode: _decode,
        encode: _encode,
        VERSION: _VERSION
    };

}(jQuery));

/* End HashChange Abstraction */
// ######################################################################################################### //

















/*
 * jQuery File Upload Plugin
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/* jshint nomen:false */
/* global define, require, window, document, location, Blob, FormData */


/*
 * jQuery File Upload Plugin
 * https://github.com/blueimp/jQuery-File-Upload
 *
 * Copyright 2010, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * https://opensource.org/licenses/MIT
 */

/* jshint nomen:false */
/* global define, require, window, document, location, Blob, FormData */

;(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        // Register as an anonymous AMD module:
        define([
            'jquery',
            'jquery-ui/ui/widget'
        ], factory);
    } else if (typeof exports === 'object') {
        // Node/CommonJS:
        factory(
            require('jquery'),
            require('./vendor/jquery.ui.widget')
        );
    } else {
        // Browser globals:
        factory(window.jQuery);
    }
}(function ($) {
    'use strict';

    // Detect file input support, based on
    // http://viljamis.com/blog/2012/file-upload-support-on-mobile/
    $.support.fileInput = !(new RegExp(
        // Handle devices which give false positives for the feature detection:
        '(Android (1\\.[0156]|2\\.[01]))' +
        '|(Windows Phone (OS 7|8\\.0))|(XBLWP)|(ZuneWP)|(WPDesktop)' +
        '|(w(eb)?OSBrowser)|(webOS)' +
        '|(Kindle/(1\\.0|2\\.[05]|3\\.0))'
        ).test(window.navigator.userAgent) ||
        // Feature detection for all other devices:
        $('<input type="file">').prop('disabled'));

    // The FileReader API is not actually used, but works as feature detection,
    // as some Safari versions (5?) support XHR file uploads via the FormData API,
    // but not non-multipart XHR file uploads.
    // window.XMLHttpRequestUpload is not available on IE10, so we check for
    // window.ProgressEvent instead to detect XHR2 file upload capability:
    $.support.xhrFileUpload = !!(window.ProgressEvent && window.FileReader);
    $.support.xhrFormDataFileUpload = !!window.FormData;

    // Detect support for Blob slicing (required for chunked uploads):
    $.support.blobSlice = window.Blob && (Blob.prototype.slice ||
        Blob.prototype.webkitSlice || Blob.prototype.mozSlice);

    // Helper function to create drag handlers for dragover/dragenter/dragleave:
    function getDragHandler(type) {
        var isDragOver = type === 'dragover';
        return function (e) {
            e.dataTransfer = e.originalEvent && e.originalEvent.dataTransfer;
            var dataTransfer = e.dataTransfer;
            if (dataTransfer && $.inArray('Files', dataTransfer.types) !== -1 &&
                this._trigger(
                    type,
                    $.Event(type, {delegatedEvent: e})
                ) !== false) {
                e.preventDefault();
                if (isDragOver) {
                    dataTransfer.dropEffect = 'copy';
                }
            }
        };
    }

    // The fileupload widget listens for change events on file input fields defined
    // via fileInput setting and paste or drop events of the given dropZone.
    // In addition to the default jQuery Widget methods, the fileupload widget
    // exposes the "add" and "send" methods, to add or directly send files using
    // the fileupload API.
    // By default, files added via file input selection, paste, drag & drop or
    // "add" method are uploaded immediately, but it is possible to override
    // the "add" callback option to queue file uploads.
    $.widget('blueimp.fileupload', {

        options: {
            // The drop target element(s), by the default the complete document.
            // Set to null to disable drag & drop support:
            dropZone: $(document),
            // The paste target element(s), by the default undefined.
            // Set to a DOM node or jQuery object to enable file pasting:
            pasteZone: undefined,
            // The file input field(s), that are listened to for change events.
            // If undefined, it is set to the file input fields inside
            // of the widget element on plugin initialization.
            // Set to null to disable the change listener.
            fileInput: undefined,
            // By default, the file input field is replaced with a clone after
            // each input field change event. This is required for iframe transport
            // queues and allows change events to be fired for the same file
            // selection, but can be disabled by setting the following option to false:
            replaceFileInput: true,
            // The parameter name for the file form data (the request argument name).
            // If undefined or empty, the name property of the file input field is
            // used, or "files[]" if the file input name property is also empty,
            // can be a string or an array of strings:
            paramName: undefined,
            // By default, each file of a selection is uploaded using an individual
            // request for XHR type uploads. Set to false to upload file
            // selections in one request each:
            singleFileUploads: true,
            // To limit the number of files uploaded with one XHR request,
            // set the following option to an integer greater than 0:
            limitMultiFileUploads: undefined,
            // The following option limits the number of files uploaded with one
            // XHR request to keep the request size under or equal to the defined
            // limit in bytes:
            limitMultiFileUploadSize: undefined,
            // Multipart file uploads add a number of bytes to each uploaded file,
            // therefore the following option adds an overhead for each file used
            // in the limitMultiFileUploadSize configuration:
            limitMultiFileUploadSizeOverhead: 512,
            // Set the following option to true to issue all file upload requests
            // in a sequential order:
            sequentialUploads: false,
            // To limit the number of concurrent uploads,
            // set the following option to an integer greater than 0:
            limitConcurrentUploads: undefined,
            // Set the following option to true to force iframe transport uploads:
            forceIframeTransport: false,
            // Set the following option to the location of a redirect url on the
            // origin server, for cross-domain iframe transport uploads:
            redirect: undefined,
            // The parameter name for the redirect url, sent as part of the form
            // data and set to 'redirect' if this option is empty:
            redirectParamName: undefined,
            // Set the following option to the location of a postMessage window,
            // to enable postMessage transport uploads:
            postMessage: undefined,
            // By default, XHR file uploads are sent as multipart/form-data.
            // The iframe transport is always using multipart/form-data.
            // Set to false to enable non-multipart XHR uploads:
            multipart: true,
            // To upload large files in smaller chunks, set the following option
            // to a preferred maximum chunk size. If set to 0, null or undefined,
            // or the browser does not support the required Blob API, files will
            // be uploaded as a whole.
            maxChunkSize: undefined,
            // When a non-multipart upload or a chunked multipart upload has been
            // aborted, this option can be used to resume the upload by setting
            // it to the size of the already uploaded bytes. This option is most
            // useful when modifying the options object inside of the "add" or
            // "send" callbacks, as the options are cloned for each file upload.
            uploadedBytes: undefined,
            // By default, failed (abort or error) file uploads are removed from the
            // global progress calculation. Set the following option to false to
            // prevent recalculating the global progress data:
            recalculateProgress: true,
            // Interval in milliseconds to calculate and trigger progress events:
            progressInterval: 100,
            // Interval in milliseconds to calculate progress bitrate:
            bitrateInterval: 500,
            // By default, uploads are started automatically when adding files:
            autoUpload: true,

            // Error and info messages:
            messages: {
                uploadedBytes: 'Uploaded bytes exceed file size'
            },

            // Translation function, gets the message key to be translated
            // and an object with context specific data as arguments:
            i18n: function (message, context) {
                message = this.messages[message] || message.toString();
                if (context) {
                    $.each(context, function (key, value) {
                        message = message.replace('{' + key + '}', value);
                    });
                }
                return message;
            },

            // Additional form data to be sent along with the file uploads can be set
            // using this option, which accepts an array of objects with name and
            // value properties, a function returning such an array, a FormData
            // object (for XHR file uploads), or a simple object.
            // The form of the first fileInput is given as parameter to the function:
            formData: function (form) {
                return form.serializeArray();
            },

            // The add callback is invoked as soon as files are added to the fileupload
            // widget (via file input selection, drag & drop, paste or add API call).
            // If the singleFileUploads option is enabled, this callback will be
            // called once for each file in the selection for XHR file uploads, else
            // once for each file selection.
            //
            // The upload starts when the submit method is invoked on the data parameter.
            // The data object contains a files property holding the added files
            // and allows you to override plugin options as well as define ajax settings.
            //
            // Listeners for this callback can also be bound the following way:
            // .bind('fileuploadadd', func);
            //
            // data.submit() returns a Promise object and allows to attach additional
            // handlers using jQuery's Deferred callbacks:
            // data.submit().done(func).fail(func).always(func);
            add: function (e, data) {
                if (e.isDefaultPrevented()) {
                    return false;
                }
                if (data.autoUpload || (data.autoUpload !== false &&
                        $(this).fileupload('option', 'autoUpload'))) {
                    data.process().done(function () {
                        data.submit();
                    });
                }
            },

            // Other callbacks:

            // Callback for the submit event of each file upload:
            // submit: function (e, data) {}, // .bind('fileuploadsubmit', func);

            // Callback for the start of each file upload request:
            // send: function (e, data) {}, // .bind('fileuploadsend', func);

            // Callback for successful uploads:
            // done: function (e, data) {}, // .bind('fileuploaddone', func);

            // Callback for failed (abort or error) uploads:
            // fail: function (e, data) {}, // .bind('fileuploadfail', func);

            // Callback for completed (success, abort or error) requests:
            // always: function (e, data) {}, // .bind('fileuploadalways', func);

            // Callback for upload progress events:
            // progress: function (e, data) {}, // .bind('fileuploadprogress', func);

            // Callback for global upload progress events:
            // progressall: function (e, data) {}, // .bind('fileuploadprogressall', func);

            // Callback for uploads start, equivalent to the global ajaxStart event:
            // start: function (e) {}, // .bind('fileuploadstart', func);

            // Callback for uploads stop, equivalent to the global ajaxStop event:
            // stop: function (e) {}, // .bind('fileuploadstop', func);

            // Callback for change events of the fileInput(s):
            // change: function (e, data) {}, // .bind('fileuploadchange', func);

            // Callback for paste events to the pasteZone(s):
            // paste: function (e, data) {}, // .bind('fileuploadpaste', func);

            // Callback for drop events of the dropZone(s):
            // drop: function (e, data) {}, // .bind('fileuploaddrop', func);

            // Callback for dragover events of the dropZone(s):
            // dragover: function (e) {}, // .bind('fileuploaddragover', func);

            // Callback for the start of each chunk upload request:
            // chunksend: function (e, data) {}, // .bind('fileuploadchunksend', func);

            // Callback for successful chunk uploads:
            // chunkdone: function (e, data) {}, // .bind('fileuploadchunkdone', func);

            // Callback for failed (abort or error) chunk uploads:
            // chunkfail: function (e, data) {}, // .bind('fileuploadchunkfail', func);

            // Callback for completed (success, abort or error) chunk upload requests:
            // chunkalways: function (e, data) {}, // .bind('fileuploadchunkalways', func);

            // The plugin options are used as settings object for the ajax calls.
            // The following are jQuery ajax settings required for the file uploads:
            processData: false,
            contentType: false,
            cache: false,
            timeout: 0
        },

        // A list of options that require reinitializing event listeners and/or
        // special initialization code:
        _specialOptions: [
            'fileInput',
            'dropZone',
            'pasteZone',
            'multipart',
            'forceIframeTransport'
        ],

        _blobSlice: $.support.blobSlice && function () {
            var slice = this.slice || this.webkitSlice || this.mozSlice;
            return slice.apply(this, arguments);
        },

        _BitrateTimer: function () {
            this.timestamp = ((Date.now) ? Date.now() : (new Date()).getTime());
            this.loaded = 0;
            this.bitrate = 0;
            this.getBitrate = function (now, loaded, interval) {
                var timeDiff = now - this.timestamp;
                if (!this.bitrate || !interval || timeDiff > interval) {
                    this.bitrate = (loaded - this.loaded) * (1000 / timeDiff) * 8;
                    this.loaded = loaded;
                    this.timestamp = now;
                }
                return this.bitrate;
            };
        },

        _isXHRUpload: function (options) {
            return !options.forceIframeTransport &&
                ((!options.multipart && $.support.xhrFileUpload) ||
                    $.support.xhrFormDataFileUpload);
        },

        _getFormData: function (options) {
            var formData;
            if ($.type(options.formData) === 'function') {
                return options.formData(options.form);
            }
            if ($.isArray(options.formData)) {
                return options.formData;
            }
            if ($.type(options.formData) === 'object') {
                formData = [];
                $.each(options.formData, function (name, value) {
                    formData.push({name: name, value: value});
                });
                return formData;
            }
            return [];
        },

        _getTotal: function (files) {
            var total = 0;
            $.each(files, function (index, file) {
                total += file.size || 1;
            });
            return total;
        },

        _initProgressObject: function (obj) {
            var progress = {
                loaded: 0,
                total: 0,
                bitrate: 0
            };
            if (obj._progress) {
                $.extend(obj._progress, progress);
            } else {
                obj._progress = progress;
            }
        },

        _initResponseObject: function (obj) {
            var prop;
            if (obj._response) {
                for (prop in obj._response) {
                    if (obj._response.hasOwnProperty(prop)) {
                        delete obj._response[prop];
                    }
                }
            } else {
                obj._response = {};
            }
        },

        _onProgress: function (e, data) {
            if (e.lengthComputable) {
                var now = ((Date.now) ? Date.now() : (new Date()).getTime()),
                    loaded;
                if (data._time && data.progressInterval &&
                    (now - data._time < data.progressInterval) &&
                    e.loaded !== e.total) {
                    return;
                }
                data._time = now;
                loaded = Math.floor(
                    e.loaded / e.total * (data.chunkSize || data._progress.total)
                ) + (data.uploadedBytes || 0);
                // Add the difference from the previously loaded state
                // to the global loaded counter:
                this._progress.loaded += (loaded - data._progress.loaded);
                this._progress.bitrate = this._bitrateTimer.getBitrate(
                    now,
                    this._progress.loaded,
                    data.bitrateInterval
                );
                data._progress.loaded = data.loaded = loaded;
                data._progress.bitrate = data.bitrate = data._bitrateTimer.getBitrate(
                    now,
                    loaded,
                    data.bitrateInterval
                );
                // Trigger a custom progress event with a total data property set
                // to the file size(s) of the current upload and a loaded data
                // property calculated accordingly:
                this._trigger(
                    'progress',
                    $.Event('progress', {delegatedEvent: e}),
                    data
                );
                // Trigger a global progress event for all current file uploads,
                // including ajax calls queued for sequential file uploads:
                this._trigger(
                    'progressall',
                    $.Event('progressall', {delegatedEvent: e}),
                    this._progress
                );
            }
        },

        _initProgressListener: function (options) {
            var that = this,
                xhr = options.xhr ? options.xhr() : $.ajaxSettings.xhr();
            // Accesss to the native XHR object is required to add event listeners
            // for the upload progress event:
            if (xhr.upload) {
                $(xhr.upload).bind('progress', function (e) {
                    var oe = e.originalEvent;
                    // Make sure the progress event properties get copied over:
                    e.lengthComputable = oe.lengthComputable;
                    e.loaded = oe.loaded;
                    e.total = oe.total;
                    that._onProgress(e, options);
                });
                options.xhr = function () {
                    return xhr;
                };
            }
        },

        _isInstanceOf: function (type, obj) {
            // Cross-frame instanceof check
            return Object.prototype.toString.call(obj) === '[object ' + type + ']';
        },

        _initXHRData: function (options) {
            var that = this,
                formData,
                file = options.files[0],
                // Ignore non-multipart setting if not supported:
                multipart = options.multipart || !$.support.xhrFileUpload,
                paramName = $.type(options.paramName) === 'array' ?
                    options.paramName[0] : options.paramName;
            options.headers = $.extend({}, options.headers);
            if (options.contentRange) {
                options.headers['Content-Range'] = options.contentRange;
            }
            if (!multipart || options.blob || !this._isInstanceOf('File', file)) {
                options.headers['Content-Disposition'] = 'attachment; filename="' +
                    encodeURI(file.name) + '"';
            }
            if (!multipart) {
                options.contentType = file.type || 'application/octet-stream';
                options.data = options.blob || file;
            } else if ($.support.xhrFormDataFileUpload) {
                if (options.postMessage) {
                    // window.postMessage does not allow sending FormData
                    // objects, so we just add the File/Blob objects to
                    // the formData array and let the postMessage window
                    // create the FormData object out of this array:
                    formData = this._getFormData(options);
                    if (options.blob) {
                        formData.push({
                            name: paramName,
                            value: options.blob
                        });
                    } else {
                        $.each(options.files, function (index, file) {
                            formData.push({
                                name: ($.type(options.paramName) === 'array' &&
                                    options.paramName[index]) || paramName,
                                value: file
                            });
                        });
                    }
                } else {
                    if (that._isInstanceOf('FormData', options.formData)) {
                        formData = options.formData;
                    } else {
                        formData = new FormData();
                        $.each(this._getFormData(options), function (index, field) {
                            formData.append(field.name, field.value);
                        });
                    }
                    if (options.blob) {
                        formData.append(paramName, options.blob, file.name);
                    } else {
                        $.each(options.files, function (index, file) {
                            // This check allows the tests to run with
                            // dummy objects:
                            if (that._isInstanceOf('File', file) ||
                                that._isInstanceOf('Blob', file)) {
                                formData.append(
                                    ($.type(options.paramName) === 'array' &&
                                        options.paramName[index]) || paramName,
                                    file,
                                    file.uploadName || file.name
                                );
                            }
                        });
                    }
                }
                options.data = formData;
            }
            // Blob reference is not needed anymore, free memory:
            options.blob = null;
        },

        _initIframeSettings: function (options) {
            var targetHost = $('<a></a>').prop('href', options.url).prop('host');
            // Setting the dataType to iframe enables the iframe transport:
            options.dataType = 'iframe ' + (options.dataType || '');
            // The iframe transport accepts a serialized array as form data:
            options.formData = this._getFormData(options);
            // Add redirect url to form data on cross-domain uploads:
            if (options.redirect && targetHost && targetHost !== location.host) {
                options.formData.push({
                    name: options.redirectParamName || 'redirect',
                    value: options.redirect
                });
            }
        },

        _initDataSettings: function (options) {
            if (this._isXHRUpload(options)) {
                if (!this._chunkedUpload(options, true)) {
                    if (!options.data) {
                        this._initXHRData(options);
                    }
                    this._initProgressListener(options);
                }
                if (options.postMessage) {
                    // Setting the dataType to postmessage enables the
                    // postMessage transport:
                    options.dataType = 'postmessage ' + (options.dataType || '');
                }
            } else {
                this._initIframeSettings(options);
            }
        },

        _getParamName: function (options) {
            var fileInput = $(options.fileInput),
                paramName = options.paramName;
            if (!paramName) {
                paramName = [];
                fileInput.each(function () {
                    var input = $(this),
                        name = input.prop('name') || 'files[]',
                        i = (input.prop('files') || [1]).length;
                    while (i) {
                        paramName.push(name);
                        i -= 1;
                    }
                });
                if (!paramName.length) {
                    paramName = [fileInput.prop('name') || 'files[]'];
                }
            } else if (!$.isArray(paramName)) {
                paramName = [paramName];
            }
            return paramName;
        },

        _initFormSettings: function (options) {
            // Retrieve missing options from the input field and the
            // associated form, if available:
            if (!options.form || !options.form.length) {
                options.form = $(options.fileInput.prop('form'));
                // If the given file input doesn't have an associated form,
                // use the default widget file input's form:
                if (!options.form.length) {
                    options.form = $(this.options.fileInput.prop('form'));
                }
            }
            options.paramName = this._getParamName(options);
            if (!options.url) {
                options.url = options.form.prop('action') || location.href;
            }
            // The HTTP request method must be "POST" or "PUT":
            options.type = (options.type ||
                ($.type(options.form.prop('method')) === 'string' &&
                    options.form.prop('method')) || ''
            ).toUpperCase();
            if (options.type !== 'POST' && options.type !== 'PUT' &&
                options.type !== 'PATCH') {
                options.type = 'POST';
            }
            if (!options.formAcceptCharset) {
                options.formAcceptCharset = options.form.attr('accept-charset');
            }
        },

        _getAJAXSettings: function (data) {
            var options = $.extend({}, this.options, data);
            this._initFormSettings(options);
            this._initDataSettings(options);
            return options;
        },

        // jQuery 1.6 doesn't provide .state(),
        // while jQuery 1.8+ removed .isRejected() and .isResolved():
        _getDeferredState: function (deferred) {
            if (deferred.state) {
                return deferred.state();
            }
            if (deferred.isResolved()) {
                return 'resolved';
            }
            if (deferred.isRejected()) {
                return 'rejected';
            }
            return 'pending';
        },

        // Maps jqXHR callbacks to the equivalent
        // methods of the given Promise object:
        _enhancePromise: function (promise) {
            promise.success = promise.done;
            promise.error = promise.fail;
            promise.complete = promise.always;
            return promise;
        },

        // Creates and returns a Promise object enhanced with
        // the jqXHR methods abort, success, error and complete:
        _getXHRPromise: function (resolveOrReject, context, args) {
            var dfd = $.Deferred(),
                promise = dfd.promise();
            context = context || this.options.context || promise;
            if (resolveOrReject === true) {
                dfd.resolveWith(context, args);
            } else if (resolveOrReject === false) {
                dfd.rejectWith(context, args);
            }
            promise.abort = dfd.promise;
            return this._enhancePromise(promise);
        },

        // Adds convenience methods to the data callback argument:
        _addConvenienceMethods: function (e, data) {
            var that = this,
                getPromise = function (args) {
                    return $.Deferred().resolveWith(that, args).promise();
                };
            data.process = function (resolveFunc, rejectFunc) {
                if (resolveFunc || rejectFunc) {
                    data._processQueue = this._processQueue =
                        (this._processQueue || getPromise([this])).then(
                            function () {
                                if (data.errorThrown) {
                                    return $.Deferred()
                                        .rejectWith(that, [data]).promise();
                                }
                                return getPromise(arguments);
                            }
                        ).then(resolveFunc, rejectFunc);
                }
                return this._processQueue || getPromise([this]);
            };
            data.submit = function () {
                if (this.state() !== 'pending') {
                    data.jqXHR = this.jqXHR =
                        (that._trigger(
                            'submit',
                            $.Event('submit', {delegatedEvent: e}),
                            this
                        ) !== false) && that._onSend(e, this);
                }
                return this.jqXHR || that._getXHRPromise();
            };
            data.abort = function () {
                if (this.jqXHR) {
                    return this.jqXHR.abort();
                }
                this.errorThrown = 'abort';
                that._trigger('fail', null, this);
                return that._getXHRPromise(false);
            };
            data.state = function () {
                if (this.jqXHR) {
                    return that._getDeferredState(this.jqXHR);
                }
                if (this._processQueue) {
                    return that._getDeferredState(this._processQueue);
                }
            };
            data.processing = function () {
                return !this.jqXHR && this._processQueue && that
                    ._getDeferredState(this._processQueue) === 'pending';
            };
            data.progress = function () {
                return this._progress;
            };
            data.response = function () {
                return this._response;
            };
        },

        // Parses the Range header from the server response
        // and returns the uploaded bytes:
        _getUploadedBytes: function (jqXHR) {
            var range = jqXHR.getResponseHeader('Range'),
                parts = range && range.split('-'),
                upperBytesPos = parts && parts.length > 1 &&
                    parseInt(parts[1], 10);
            return upperBytesPos && upperBytesPos + 1;
        },

        // Uploads a file in multiple, sequential requests
        // by splitting the file up in multiple blob chunks.
        // If the second parameter is true, only tests if the file
        // should be uploaded in chunks, but does not invoke any
        // upload requests:
        _chunkedUpload: function (options, testOnly) {
            options.uploadedBytes = options.uploadedBytes || 0;
            var that = this,
                file = options.files[0],
                fs = file.size,
                ub = options.uploadedBytes,
                mcs = options.maxChunkSize || fs,
                slice = this._blobSlice,
                dfd = $.Deferred(),
                promise = dfd.promise(),
                jqXHR,
                upload;
            if (!(this._isXHRUpload(options) && slice && (ub || ($.type(mcs) === 'function' ? mcs(options) : mcs) < fs)) ||
                options.data) {
                return false;
            }
            if (testOnly) {
                return true;
            }
            if (ub >= fs) {
                file.error = options.i18n('uploadedBytes');
                return this._getXHRPromise(
                    false,
                    options.context,
                    [null, 'error', file.error]
                );
            }
            // The chunk upload method:
            upload = function () {
                // Clone the options object for each chunk upload:
                var o = $.extend({}, options),
                    currentLoaded = o._progress.loaded;
                o.blob = slice.call(
                    file,
                    ub,
                    ub + ($.type(mcs) === 'function' ? mcs(o) : mcs),
                    file.type
                );
                // Store the current chunk size, as the blob itself
                // will be dereferenced after data processing:
                o.chunkSize = o.blob.size;
                // Expose the chunk bytes position range:
                o.contentRange = 'bytes ' + ub + '-' +
                    (ub + o.chunkSize - 1) + '/' + fs;
                // Process the upload data (the blob and potential form data):
                that._initXHRData(o);
                // Add progress listeners for this chunk upload:
                that._initProgressListener(o);
                jqXHR = ((that._trigger('chunksend', null, o) !== false && $.ajax(o)) ||
                    that._getXHRPromise(false, o.context))
                    .done(function (result, textStatus, jqXHR) {
                        ub = that._getUploadedBytes(jqXHR) ||
                            (ub + o.chunkSize);
                        // Create a progress event if no final progress event
                        // with loaded equaling total has been triggered
                        // for this chunk:
                        if (currentLoaded + o.chunkSize - o._progress.loaded) {
                            that._onProgress($.Event('progress', {
                                lengthComputable: true,
                                loaded: ub - o.uploadedBytes,
                                total: ub - o.uploadedBytes
                            }), o);
                        }
                        options.uploadedBytes = o.uploadedBytes = ub;
                        o.result = result;
                        o.textStatus = textStatus;
                        o.jqXHR = jqXHR;
                        that._trigger('chunkdone', null, o);
                        that._trigger('chunkalways', null, o);
                        if (ub < fs) {
                            // File upload not yet complete,
                            // continue with the next chunk:
                            upload();
                        } else {
                            dfd.resolveWith(
                                o.context,
                                [result, textStatus, jqXHR]
                            );
                        }
                    })
                    .fail(function (jqXHR, textStatus, errorThrown) {
                        o.jqXHR = jqXHR;
                        o.textStatus = textStatus;
                        o.errorThrown = errorThrown;
                        that._trigger('chunkfail', null, o);
                        that._trigger('chunkalways', null, o);
                        dfd.rejectWith(
                            o.context,
                            [jqXHR, textStatus, errorThrown]
                        );
                    });
            };
            this._enhancePromise(promise);
            promise.abort = function () {
                return jqXHR.abort();
            };
            upload();
            return promise;
        },

        _beforeSend: function (e, data) {
            if (this._active === 0) {
                // the start callback is triggered when an upload starts
                // and no other uploads are currently running,
                // equivalent to the global ajaxStart event:
                this._trigger('start');
                // Set timer for global bitrate progress calculation:
                this._bitrateTimer = new this._BitrateTimer();
                // Reset the global progress values:
                this._progress.loaded = this._progress.total = 0;
                this._progress.bitrate = 0;
            }
            // Make sure the container objects for the .response() and
            // .progress() methods on the data object are available
            // and reset to their initial state:
            this._initResponseObject(data);
            this._initProgressObject(data);
            data._progress.loaded = data.loaded = data.uploadedBytes || 0;
            data._progress.total = data.total = this._getTotal(data.files) || 1;
            data._progress.bitrate = data.bitrate = 0;
            this._active += 1;
            // Initialize the global progress values:
            this._progress.loaded += data.loaded;
            this._progress.total += data.total;
        },

        _onDone: function (result, textStatus, jqXHR, options) {
            var total = options._progress.total,
                response = options._response;
            if (options._progress.loaded < total) {
                // Create a progress event if no final progress event
                // with loaded equaling total has been triggered:
                this._onProgress($.Event('progress', {
                    lengthComputable: true,
                    loaded: total,
                    total: total
                }), options);
            }
            response.result = options.result = result;
            response.textStatus = options.textStatus = textStatus;
            response.jqXHR = options.jqXHR = jqXHR;
            this._trigger('done', null, options);
        },

        _onFail: function (jqXHR, textStatus, errorThrown, options) {
            var response = options._response;
            if (options.recalculateProgress) {
                // Remove the failed (error or abort) file upload from
                // the global progress calculation:
                this._progress.loaded -= options._progress.loaded;
                this._progress.total -= options._progress.total;
            }
            response.jqXHR = options.jqXHR = jqXHR;
            response.textStatus = options.textStatus = textStatus;
            response.errorThrown = options.errorThrown = errorThrown;
            this._trigger('fail', null, options);
        },

        _onAlways: function (jqXHRorResult, textStatus, jqXHRorError, options) {
            // jqXHRorResult, textStatus and jqXHRorError are added to the
            // options object via done and fail callbacks
            this._trigger('always', null, options);
        },

        _onSend: function (e, data) {
            if (!data.submit) {
                this._addConvenienceMethods(e, data);
            }
            var that = this,
                jqXHR,
                aborted,
                slot,
                pipe,
                options = that._getAJAXSettings(data),
                send = function () {
                    that._sending += 1;
                    // Set timer for bitrate progress calculation:
                    options._bitrateTimer = new that._BitrateTimer();
                    jqXHR = jqXHR || (
                        ((aborted || that._trigger(
                            'send',
                            $.Event('send', {delegatedEvent: e}),
                            options
                            ) === false) &&
                            that._getXHRPromise(false, options.context, aborted)) ||
                        that._chunkedUpload(options) || $.ajax(options)
                    ).done(function (result, textStatus, jqXHR) {
                        that._onDone(result, textStatus, jqXHR, options);
                    }).fail(function (jqXHR, textStatus, errorThrown) {
                        that._onFail(jqXHR, textStatus, errorThrown, options);
                    }).always(function (jqXHRorResult, textStatus, jqXHRorError) {
                        that._onAlways(
                            jqXHRorResult,
                            textStatus,
                            jqXHRorError,
                            options
                        );
                        that._sending -= 1;
                        that._active -= 1;
                        if (options.limitConcurrentUploads &&
                            options.limitConcurrentUploads > that._sending) {
                            // Start the next queued upload,
                            // that has not been aborted:
                            var nextSlot = that._slots.shift();
                            while (nextSlot) {
                                if (that._getDeferredState(nextSlot) === 'pending') {
                                    nextSlot.resolve();
                                    break;
                                }
                                nextSlot = that._slots.shift();
                            }
                        }
                        if (that._active === 0) {
                            // The stop callback is triggered when all uploads have
                            // been completed, equivalent to the global ajaxStop event:
                            that._trigger('stop');
                        }
                    });
                    return jqXHR;
                };
            this._beforeSend(e, options);
            if (this.options.sequentialUploads ||
                (this.options.limitConcurrentUploads &&
                    this.options.limitConcurrentUploads <= this._sending)) {
                if (this.options.limitConcurrentUploads > 1) {
                    slot = $.Deferred();
                    this._slots.push(slot);
                    pipe = slot.then(send);
                } else {
                    this._sequence = this._sequence.then(send, send);
                    pipe = this._sequence;
                }
                // Return the piped Promise object, enhanced with an abort method,
                // which is delegated to the jqXHR object of the current upload,
                // and jqXHR callbacks mapped to the equivalent Promise methods:
                pipe.abort = function () {
                    aborted = [undefined, 'abort', 'abort'];
                    if (!jqXHR) {
                        if (slot) {
                            slot.rejectWith(options.context, aborted);
                        }
                        return send();
                    }
                    return jqXHR.abort();
                };
                return this._enhancePromise(pipe);
            }
            return send();
        },

        _onAdd: function (e, data) {
            var that = this,
                result = true,
                options = $.extend({}, this.options, data),
                files = data.files,
                filesLength = files.length,
                limit = options.limitMultiFileUploads,
                limitSize = options.limitMultiFileUploadSize,
                overhead = options.limitMultiFileUploadSizeOverhead,
                batchSize = 0,
                paramName = this._getParamName(options),
                paramNameSet,
                paramNameSlice,
                fileSet,
                i,
                j = 0;
            if (!filesLength) {
                return false;
            }
            if (limitSize && files[0].size === undefined) {
                limitSize = undefined;
            }
            if (!(options.singleFileUploads || limit || limitSize) ||
                !this._isXHRUpload(options)) {
                fileSet = [files];
                paramNameSet = [paramName];
            } else if (!(options.singleFileUploads || limitSize) && limit) {
                fileSet = [];
                paramNameSet = [];
                for (i = 0; i < filesLength; i += limit) {
                    fileSet.push(files.slice(i, i + limit));
                    paramNameSlice = paramName.slice(i, i + limit);
                    if (!paramNameSlice.length) {
                        paramNameSlice = paramName;
                    }
                    paramNameSet.push(paramNameSlice);
                }
            } else if (!options.singleFileUploads && limitSize) {
                fileSet = [];
                paramNameSet = [];
                for (i = 0; i < filesLength; i = i + 1) {
                    batchSize += files[i].size + overhead;
                    if (i + 1 === filesLength ||
                        ((batchSize + files[i + 1].size + overhead) > limitSize) ||
                        (limit && i + 1 - j >= limit)) {
                        fileSet.push(files.slice(j, i + 1));
                        paramNameSlice = paramName.slice(j, i + 1);
                        if (!paramNameSlice.length) {
                            paramNameSlice = paramName;
                        }
                        paramNameSet.push(paramNameSlice);
                        j = i + 1;
                        batchSize = 0;
                    }
                }
            } else {
                paramNameSet = paramName;
            }
            data.originalFiles = files;
            $.each(fileSet || files, function (index, element) {
                var newData = $.extend({}, data);
                newData.files = fileSet ? element : [element];
                newData.paramName = paramNameSet[index];
                that._initResponseObject(newData);
                that._initProgressObject(newData);
                that._addConvenienceMethods(e, newData);
                result = that._trigger(
                    'add',
                    $.Event('add', {delegatedEvent: e}),
                    newData
                );
                return result;
            });
            return result;
        },

        _replaceFileInput: function (data) {
            var input = data.fileInput,
                inputClone = input.clone(true),
                restoreFocus = input.is(document.activeElement);
            // Add a reference for the new cloned file input to the data argument:
            data.fileInputClone = inputClone;
            $('<form></form>').append(inputClone)[0].reset();
            // Detaching allows to insert the fileInput on another form
            // without loosing the file input value:
            input.after(inputClone).detach();
            // If the fileInput had focus before it was detached,
            // restore focus to the inputClone.
            if (restoreFocus) {
                inputClone.focus();
            }
            // Avoid memory leaks with the detached file input:
            $.cleanData(input.unbind('remove'));
            // Replace the original file input element in the fileInput
            // elements set with the clone, which has been copied including
            // event handlers:
            this.options.fileInput = this.options.fileInput.map(function (i, el) {
                if (el === input[0]) {
                    return inputClone[0];
                }
                return el;
            });
            // If the widget has been initialized on the file input itself,
            // override this.element with the file input clone:
            if (input[0] === this.element[0]) {
                this.element = inputClone;
            }
        },

        _handleFileTreeEntry: function (entry, path) {
            var that = this,
                dfd = $.Deferred(),
                entries = [],
                dirReader,
                errorHandler = function (e) {
                    if (e && !e.entry) {
                        e.entry = entry;
                    }
                    // Since $.when returns immediately if one
                    // Deferred is rejected, we use resolve instead.
                    // This allows valid files and invalid items
                    // to be returned together in one set:
                    dfd.resolve([e]);
                },
                successHandler = function (entries) {
                    that._handleFileTreeEntries(
                        entries,
                        path + entry.name + '/'
                    ).done(function (files) {
                        dfd.resolve(files);
                    }).fail(errorHandler);
                },
                readEntries = function () {
                    dirReader.readEntries(function (results) {
                        if (!results.length) {
                            successHandler(entries);
                        } else {
                            entries = entries.concat(results);
                            readEntries();
                        }
                    }, errorHandler);
                };
            path = path || '';
            if (entry.isFile) {
                if (entry._file) {
                    // Workaround for Chrome bug #149735
                    entry._file.relativePath = path;
                    dfd.resolve(entry._file);
                } else {
                    entry.file(function (file) {
                        file.relativePath = path;
                        dfd.resolve(file);
                    }, errorHandler);
                }
            } else if (entry.isDirectory) {
                dirReader = entry.createReader();
                readEntries();
            } else {
                // Return an empy list for file system items
                // other than files or directories:
                dfd.resolve([]);
            }
            return dfd.promise();
        },

        _handleFileTreeEntries: function (entries, path) {
            var that = this;
            return $.when.apply(
                $,
                $.map(entries, function (entry) {
                    return that._handleFileTreeEntry(entry, path);
                })
            ).then(function () {
                return Array.prototype.concat.apply(
                    [],
                    arguments
                );
            });
        },

        _getDroppedFiles: function (dataTransfer) {
            dataTransfer = dataTransfer || {};
            var items = dataTransfer.items;
            if (items && items.length && (items[0].webkitGetAsEntry ||
                    items[0].getAsEntry)) {
                return this._handleFileTreeEntries(
                    $.map(items, function (item) {
                        var entry;
                        if (item.webkitGetAsEntry) {
                            entry = item.webkitGetAsEntry();
                            if (entry) {
                                // Workaround for Chrome bug #149735:
                                entry._file = item.getAsFile();
                            }
                            return entry;
                        }
                        return item.getAsEntry();
                    })
                );
            }
            return $.Deferred().resolve(
                $.makeArray(dataTransfer.files)
            ).promise();
        },

        _getSingleFileInputFiles: function (fileInput) {
            fileInput = $(fileInput);
            var entries = fileInput.prop('webkitEntries') ||
                fileInput.prop('entries'),
                files,
                value;
            if (entries && entries.length) {
                return this._handleFileTreeEntries(entries);
            }
            files = $.makeArray(fileInput.prop('files'));
            if (!files.length) {
                value = fileInput.prop('value');
                if (!value) {
                    return $.Deferred().resolve([]).promise();
                }
                // If the files property is not available, the browser does not
                // support the File API and we add a pseudo File object with
                // the input value as name with path information removed:
                files = [{name: value.replace(/^.*\\/, '')}];
            } else if (files[0].name === undefined && files[0].fileName) {
                // File normalization for Safari 4 and Firefox 3:
                $.each(files, function (index, file) {
                    file.name = file.fileName;
                    file.size = file.fileSize;
                });
            }
            return $.Deferred().resolve(files).promise();
        },

        _getFileInputFiles: function (fileInput) {
            if (!(fileInput instanceof $) || fileInput.length === 1) {
                return this._getSingleFileInputFiles(fileInput);
            }
            return $.when.apply(
                $,
                $.map(fileInput, this._getSingleFileInputFiles)
            ).then(function () {
                return Array.prototype.concat.apply(
                    [],
                    arguments
                );
            });
        },

        _onChange: function (e) {
            var that = this,
                data = {
                    fileInput: $(e.target),
                    form: $(e.target.form)
                };
            this._getFileInputFiles(data.fileInput).always(function (files) {
                data.files = files;
                if (that.options.replaceFileInput) {
                    that._replaceFileInput(data);
                }
                if (that._trigger(
                        'change',
                        $.Event('change', {delegatedEvent: e}),
                        data
                    ) !== false) {
                    that._onAdd(e, data);
                }
            });
        },

        _onPaste: function (e) {
            var items = e.originalEvent && e.originalEvent.clipboardData &&
                e.originalEvent.clipboardData.items,
                data = {files: []};
            if (items && items.length) {
                $.each(items, function (index, item) {
                    var file = item.getAsFile && item.getAsFile();
                    if (file) {
                        data.files.push(file);
                    }
                });
                if (this._trigger(
                        'paste',
                        $.Event('paste', {delegatedEvent: e}),
                        data
                    ) !== false) {
                    this._onAdd(e, data);
                }
            }
        },

        _onDrop: function (e) {
            e.dataTransfer = e.originalEvent && e.originalEvent.dataTransfer;
            var that = this,
                dataTransfer = e.dataTransfer,
                data = {};
            if (dataTransfer && dataTransfer.files && dataTransfer.files.length) {
                e.preventDefault();
                this._getDroppedFiles(dataTransfer).always(function (files) {
                    data.files = files;
                    if (that._trigger(
                            'drop',
                            $.Event('drop', {delegatedEvent: e}),
                            data
                        ) !== false) {
                        that._onAdd(e, data);
                    }
                });
            }
        },

        _onDragOver: getDragHandler('dragover'),

        _onDragEnter: getDragHandler('dragenter'),

        _onDragLeave: getDragHandler('dragleave'),

        _initEventHandlers: function () {
            if (this._isXHRUpload(this.options)) {
                this._on(this.options.dropZone, {
                    dragover: this._onDragOver,
                    drop: this._onDrop,
                    // event.preventDefault() on dragenter is required for IE10+:
                    dragenter: this._onDragEnter,
                    // dragleave is not required, but added for completeness:
                    dragleave: this._onDragLeave
                });
                this._on(this.options.pasteZone, {
                    paste: this._onPaste
                });
            }
            if ($.support.fileInput) {
                this._on(this.options.fileInput, {
                    change: this._onChange
                });
            }
        },

        _destroyEventHandlers: function () {
            this._off(this.options.dropZone, 'dragenter dragleave dragover drop');
            this._off(this.options.pasteZone, 'paste');
            this._off(this.options.fileInput, 'change');
        },

        _destroy: function () {
            this._destroyEventHandlers();
        },

        _setOption: function (key, value) {
            var reinit = $.inArray(key, this._specialOptions) !== -1;
            if (reinit) {
                this._destroyEventHandlers();
            }
            this._super(key, value);
            if (reinit) {
                this._initSpecialOptions();
                this._initEventHandlers();
            }
        },

        _initSpecialOptions: function () {
            var options = this.options;
            if (options.fileInput === undefined) {
                options.fileInput = this.element.is('input[type="file"]') ?
                    this.element : this.element.find('input[type="file"]');
            } else if (!(options.fileInput instanceof $)) {
                options.fileInput = $(options.fileInput);
            }
            if (!(options.dropZone instanceof $)) {
                options.dropZone = $(options.dropZone);
            }
            if (!(options.pasteZone instanceof $)) {
                options.pasteZone = $(options.pasteZone);
            }
        },

        _getRegExp: function (str) {
            var parts = str.split('/'),
                modifiers = parts.pop();
            parts.shift();
            return new RegExp(parts.join('/'), modifiers);
        },

        _isRegExpOption: function (key, value) {
            return key !== 'url' && $.type(value) === 'string' &&
                /^\/.*\/[igm]{0,3}$/.test(value);
        },

        _initDataAttributes: function () {
            var that = this,
                options = this.options,
                data = this.element.data();
            // Initialize options set via HTML5 data-attributes:
            $.each(
                this.element[0].attributes,
                function (index, attr) {
                    var key = attr.name.toLowerCase(),
                        value;
                    if (/^data-/.test(key)) {
                        // Convert hyphen-ated key to camelCase:
                        key = key.slice(5).replace(/-[a-z]/g, function (str) {
                            return str.charAt(1).toUpperCase();
                        });
                        value = data[key];
                        if (that._isRegExpOption(key, value)) {
                            value = that._getRegExp(value);
                        }
                        options[key] = value;
                    }
                }
            );
        },

        _create: function () {
            this._initDataAttributes();
            this._initSpecialOptions();
            this._slots = [];
            this._sequence = this._getXHRPromise(true);
            this._sending = this._active = 0;
            this._initProgressObject(this);
            this._initEventHandlers();
        },

        // This method is exposed to the widget API and allows to query
        // the number of active uploads:
        active: function () {
            return this._active;
        },

        // This method is exposed to the widget API and allows to query
        // the widget upload progress.
        // It returns an object with loaded, total and bitrate properties
        // for the running uploads:
        progress: function () {
            return this._progress;
        },

        // This method is exposed to the widget API and allows adding files
        // using the fileupload API. The data parameter accepts an object which
        // must have a files property and can contain additional options:
        // .fileupload('add', {files: filesList});
        add: function (data) {
            var that = this;
            if (!data || this.options.disabled) {
                return;
            }
            if (data.fileInput && !data.files) {
                this._getFileInputFiles(data.fileInput).always(function (files) {
                    data.files = files;
                    that._onAdd(null, data);
                });
            } else {
                data.files = $.makeArray(data.files);
                this._onAdd(null, data);
            }
        },

        // This method is exposed to the widget API and allows sending files
        // using the fileupload API. The data parameter accepts an object which
        // must have a files or fileInput property and can contain additional options:
        // .fileupload('send', {files: filesList});
        // The method returns a Promise object for the file upload call.
        send: function (data) {
            if (data && !this.options.disabled) {
                if (data.fileInput && !data.files) {
                    var that = this,
                        dfd = $.Deferred(),
                        promise = dfd.promise(),
                        jqXHR,
                        aborted;
                    promise.abort = function () {
                        aborted = true;
                        if (jqXHR) {
                            return jqXHR.abort();
                        }
                        dfd.reject(null, 'abort', 'abort');
                        return promise;
                    };
                    this._getFileInputFiles(data.fileInput).always(
                        function (files) {
                            if (aborted) {
                                return;
                            }
                            if (!files.length) {
                                dfd.reject();
                                return;
                            }
                            data.files = files;
                            jqXHR = that._onSend(null, data);
                            jqXHR.then(
                                function (result, textStatus, jqXHR) {
                                    dfd.resolve(result, textStatus, jqXHR);
                                },
                                function (jqXHR, textStatus, errorThrown) {
                                    dfd.reject(jqXHR, textStatus, errorThrown);
                                }
                            );
                        }
                    );
                    return this._enhancePromise(promise);
                }
                data.files = $.makeArray(data.files);
                if (data.files.length) {
                    return this._onSend(null, data);
                }
            }
            return this._getXHRPromise(false, data && data.context);
        }

    });

}));




/*!
  SerializeJSON jQuery plugin.
  https://github.com/marioizquierdo/jquery.serializeJSON
  version 2.9.0 (Jan, 2018)

  Copyright (c) 2012-2018 Mario Izquierdo
  Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
  and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
*/
(function (factory) {
    if (typeof define === 'function' && define.amd) { // AMD. Register as an anonymous module.
        define(['jquery'], factory);
    } else if (typeof exports === 'object') { // Node/CommonJS
        var jQuery = require('jquery');
        module.exports = factory(jQuery);
    } else { // Browser globals (zepto supported)
        factory(window.jQuery || window.Zepto || window.$); // Zepto supported on browsers as well
    }

}(function ($) {
    "use strict";

    // jQuery('form').serializeJSON()
    $.fn.serializeJSON = function (options) {
        var f, $form, opts, formAsArray, serializedObject, name, value, parsedValue, _obj, nameWithNoType, type, keys, skipFalsy;
        f = $.serializeJSON;
        $form = this; // NOTE: the set of matched elements is most likely a form, but it could also be a group of inputs
        opts = f.setupOpts(options); // calculate values for options {parseNumbers, parseBoolens, parseNulls, ...} with defaults

        // Use native `serializeArray` function to get an array of {name, value} objects.
        formAsArray = $form.serializeArray();
        f.readCheckboxUncheckedValues(formAsArray, opts, $form); // add objects to the array from unchecked checkboxes if needed

        // Convert the formAsArray into a serializedObject with nested keys
        serializedObject = {};
        $.each(formAsArray, function (i, obj) {
            name  = obj.name; // original input name
            value = obj.value; // input value
            _obj = f.extractTypeAndNameWithNoType(name);
            nameWithNoType = _obj.nameWithNoType; // input name with no type (i.e. "foo:string" => "foo")
            type = _obj.type; // type defined from the input name in :type colon notation
            if (!type) type = f.attrFromInputWithName($form, name, 'data-value-type');
            f.validateType(name, type, opts); // make sure that the type is one of the valid types if defined

            if (type !== 'skip') { // ignore inputs with type 'skip'
                keys = f.splitInputNameIntoKeysArray(nameWithNoType);
                parsedValue = f.parseValue(value, name, type, opts); // convert to string, number, boolean, null or customType

                skipFalsy = !parsedValue && f.shouldSkipFalsy($form, name, nameWithNoType, type, opts); // ignore falsy inputs if specified
                if (!skipFalsy) {
                    f.deepSet(serializedObject, keys, parsedValue, opts);
                }
            }
        });
        return serializedObject;
    };

    // Use $.serializeJSON as namespace for the auxiliar functions
    // and to define defaults
    $.serializeJSON = {

        defaultOptions: {
            checkboxUncheckedValue: undefined, // to include that value for unchecked checkboxes (instead of ignoring them)

            parseNumbers: false, // convert values like "1", "-2.33" to 1, -2.33
            parseBooleans: false, // convert "true", "false" to true, false
            parseNulls: false, // convert "null" to null
            parseAll: false, // all of the above
            parseWithFunction: null, // to use custom parser, a function like: function(val){ return parsed_val; }

            skipFalsyValuesForTypes: [], // skip serialization of falsy values for listed value types
            skipFalsyValuesForFields: [], // skip serialization of falsy values for listed field names

            customTypes: {}, // override defaultTypes
            defaultTypes: {
                "string":  function(str) { return String(str); },
                "number":  function(str) { return Number(str); },
                "boolean": function(str) { var falses = ["false", "null", "undefined", "", "0"]; return falses.indexOf(str) === -1; },
                "null":    function(str) { var falses = ["false", "null", "undefined", "", "0"]; return falses.indexOf(str) === -1 ? str : null; },
                "array":   function(str) { return JSON.parse(str); },
                "object":  function(str) { return JSON.parse(str); },
                "auto":    function(str) { return $.serializeJSON.parseValue(str, null, null, {parseNumbers: true, parseBooleans: true, parseNulls: true}); }, // try again with something like "parseAll"
                "skip":    null // skip is a special type that makes it easy to ignore elements
            },

            useIntKeysAsArrayIndex: false // name="foo[2]" value="v" => {foo: [null, null, "v"]}, instead of {foo: ["2": "v"]}
        },

        // Merge option defaults into the options
        setupOpts: function(options) {
            var opt, validOpts, defaultOptions, optWithDefault, parseAll, f;
            f = $.serializeJSON;

            if (options == null) { options = {}; }   // options ||= {}
            defaultOptions = f.defaultOptions || {}; // defaultOptions

            // Make sure that the user didn't misspell an option
            validOpts = ['checkboxUncheckedValue', 'parseNumbers', 'parseBooleans', 'parseNulls', 'parseAll', 'parseWithFunction', 'skipFalsyValuesForTypes', 'skipFalsyValuesForFields', 'customTypes', 'defaultTypes', 'useIntKeysAsArrayIndex']; // re-define because the user may override the defaultOptions
            for (opt in options) {
                if (validOpts.indexOf(opt) === -1) {
                    throw new  Error("serializeJSON ERROR: invalid option '" + opt + "'. Please use one of " + validOpts.join(', '));
                }
            }

            // Helper to get the default value for this option if none is specified by the user
            optWithDefault = function(key) { return (options[key] !== false) && (options[key] !== '') && (options[key] || defaultOptions[key]); };

            // Return computed options (opts to be used in the rest of the script)
            parseAll = optWithDefault('parseAll');
            return {
                checkboxUncheckedValue:    optWithDefault('checkboxUncheckedValue'),

                parseNumbers:  parseAll || optWithDefault('parseNumbers'),
                parseBooleans: parseAll || optWithDefault('parseBooleans'),
                parseNulls:    parseAll || optWithDefault('parseNulls'),
                parseWithFunction:         optWithDefault('parseWithFunction'),

                skipFalsyValuesForTypes:   optWithDefault('skipFalsyValuesForTypes'),
                skipFalsyValuesForFields:  optWithDefault('skipFalsyValuesForFields'),
                typeFunctions: $.extend({}, optWithDefault('defaultTypes'), optWithDefault('customTypes')),

                useIntKeysAsArrayIndex: optWithDefault('useIntKeysAsArrayIndex')
            };
        },

        // Given a string, apply the type or the relevant "parse" options, to return the parsed value
        parseValue: function(valStr, inputName, type, opts) {
            var f, parsedVal;
            f = $.serializeJSON;
            parsedVal = valStr; // if no parsing is needed, the returned value will be the same

            if (opts.typeFunctions && type && opts.typeFunctions[type]) { // use a type if available
                parsedVal = opts.typeFunctions[type](valStr);
            } else if (opts.parseNumbers  && f.isNumeric(valStr)) { // auto: number
                parsedVal = Number(valStr);
            } else if (opts.parseBooleans && (valStr === "true" || valStr === "false")) { // auto: boolean
                parsedVal = (valStr === "true");
            } else if (opts.parseNulls    && valStr == "null") { // auto: null
                parsedVal = null;
            } else if (opts.typeFunctions && opts.typeFunctions["string"]) { // make sure to apply :string type if it was re-defined
                parsedVal = opts.typeFunctions["string"](valStr);
            }

            // Custom parse function: apply after parsing options, unless there's an explicit type.
            if (opts.parseWithFunction && !type) {
                parsedVal = opts.parseWithFunction(parsedVal, inputName);
            }

            return parsedVal;
        },

        isObject:          function(obj) { return obj === Object(obj); }, // is it an Object?
        isUndefined:       function(obj) { return obj === void 0; }, // safe check for undefined values
        isValidArrayIndex: function(val) { return /^[0-9]+$/.test(String(val)); }, // 1,2,3,4 ... are valid array indexes
        isNumeric:         function(obj) { return obj - parseFloat(obj) >= 0; }, // taken from jQuery.isNumeric implementation. Not using jQuery.isNumeric to support old jQuery and Zepto versions

        optionKeys: function(obj) { if (Object.keys) { return Object.keys(obj); } else { var key, keys = []; for(key in obj){ keys.push(key); } return keys;} }, // polyfill Object.keys to get option keys in IE<9


        // Fill the formAsArray object with values for the unchecked checkbox inputs,
        // using the same format as the jquery.serializeArray function.
        // The value of the unchecked values is determined from the opts.checkboxUncheckedValue
        // and/or the data-unchecked-value attribute of the inputs.
        readCheckboxUncheckedValues: function (formAsArray, opts, $form) {
            var selector, $uncheckedCheckboxes, $el, uncheckedValue, f, name;
            if (opts == null) { opts = {}; }
            f = $.serializeJSON;

            selector = 'input[type=checkbox][name]:not(:checked):not([disabled])';
            $uncheckedCheckboxes = $form.find(selector).add($form.filter(selector));
            $uncheckedCheckboxes.each(function (i, el) {
                // Check data attr first, then the option
                $el = $(el);
                uncheckedValue = $el.attr('data-unchecked-value');
                if (uncheckedValue == null) {
                    uncheckedValue = opts.checkboxUncheckedValue;
                }

                // If there's an uncheckedValue, push it into the serialized formAsArray
                if (uncheckedValue != null) {
                    if (el.name && el.name.indexOf("[][") !== -1) { // identify a non-supported
                        throw new Error("serializeJSON ERROR: checkbox unchecked values are not supported on nested arrays of objects like '"+el.name+"'. See https://github.com/marioizquierdo/jquery.serializeJSON/issues/67");
                    }
                    formAsArray.push({name: el.name, value: uncheckedValue});
                }
            });
        },

        // Returns and object with properties {name_without_type, type} from a given name.
        // The type is null if none specified. Example:
        //   "foo"           =>  {nameWithNoType: "foo",      type:  null}
        //   "foo:boolean"   =>  {nameWithNoType: "foo",      type: "boolean"}
        //   "foo[bar]:null" =>  {nameWithNoType: "foo[bar]", type: "null"}
        extractTypeAndNameWithNoType: function(name) {
            var match;
            if (match = name.match(/(.*):([^:]+)$/)) {
                return {nameWithNoType: match[1], type: match[2]};
            } else {
                return {nameWithNoType: name, type: null};
            }
        },


        // Check if this input should be skipped when it has a falsy value,
        // depending on the options to skip values by name or type, and the data-skip-falsy attribute.
        shouldSkipFalsy: function($form, name, nameWithNoType, type, opts) {
            var f = $.serializeJSON;

            var skipFromDataAttr = f.attrFromInputWithName($form, name, 'data-skip-falsy');
            if (skipFromDataAttr != null) {
                return skipFromDataAttr !== 'false'; // any value is true, except if explicitly using 'false'
            }

            var optForFields = opts.skipFalsyValuesForFields;
            if (optForFields && (optForFields.indexOf(nameWithNoType) !== -1 || optForFields.indexOf(name) !== -1)) {
                return true;
            }

            var optForTypes = opts.skipFalsyValuesForTypes;
            if (type == null) type = 'string'; // assume fields with no type are targeted as string
            if (optForTypes && optForTypes.indexOf(type) !== -1) {
                return true
            }

            return false;
        },

        // Finds the first input in $form with this name, and get the given attr from it.
        // Returns undefined if no input or no attribute was found.
        attrFromInputWithName: function($form, name, attrName) {
            var escapedName, selector, $input, attrValue;
            escapedName = name.replace(/(:|\.|\[|\]|\s)/g,'\\$1'); // every non-standard character need to be escaped by \\
            selector = '[name="' + escapedName + '"]';
            $input = $form.find(selector).add($form.filter(selector)); // NOTE: this returns only the first $input element if multiple are matched with the same name (i.e. an "array[]"). So, arrays with different element types specified through the data-value-type attr is not supported.
            return $input.attr(attrName);
        },

        // Raise an error if the type is not recognized.
        validateType: function(name, type, opts) {
            var validTypes, f;
            f = $.serializeJSON;
            validTypes = f.optionKeys(opts ? opts.typeFunctions : f.defaultOptions.defaultTypes);
            if (!type || validTypes.indexOf(type) !== -1) {
                return true;
            } else {
                throw new Error("serializeJSON ERROR: Invalid type " + type + " found in input name '" + name + "', please use one of " + validTypes.join(', '));
            }
        },


        // Split the input name in programatically readable keys.
        // Examples:
        // "foo"              => ['foo']
        // "[foo]"            => ['foo']
        // "foo[inn][bar]"    => ['foo', 'inn', 'bar']
        // "foo[inn[bar]]"    => ['foo', 'inn', 'bar']
        // "foo[inn][arr][0]" => ['foo', 'inn', 'arr', '0']
        // "arr[][val]"       => ['arr', '', 'val']
        splitInputNameIntoKeysArray: function(nameWithNoType) {
            var keys, f;
            f = $.serializeJSON;
            keys = nameWithNoType.split('['); // split string into array
            keys = $.map(keys, function (key) { return key.replace(/\]/g, ''); }); // remove closing brackets
            if (keys[0] === '') { keys.shift(); } // ensure no opening bracket ("[foo][inn]" should be same as "foo[inn]")
            return keys;
        },

        // Set a value in an object or array, using multiple keys to set in a nested object or array:
        //
        // deepSet(obj, ['foo'], v)               // obj['foo'] = v
        // deepSet(obj, ['foo', 'inn'], v)        // obj['foo']['inn'] = v // Create the inner obj['foo'] object, if needed
        // deepSet(obj, ['foo', 'inn', '123'], v) // obj['foo']['arr']['123'] = v //
        //
        // deepSet(obj, ['0'], v)                                   // obj['0'] = v
        // deepSet(arr, ['0'], v, {useIntKeysAsArrayIndex: true})   // arr[0] = v
        // deepSet(arr, [''], v)                                    // arr.push(v)
        // deepSet(obj, ['arr', ''], v)                             // obj['arr'].push(v)
        //
        // arr = [];
        // deepSet(arr, ['', v]          // arr => [v]
        // deepSet(arr, ['', 'foo'], v)  // arr => [v, {foo: v}]
        // deepSet(arr, ['', 'bar'], v)  // arr => [v, {foo: v, bar: v}]
        // deepSet(arr, ['', 'bar'], v)  // arr => [v, {foo: v, bar: v}, {bar: v}]
        //
        deepSet: function (o, keys, value, opts) {
            var key, nextKey, tail, lastIdx, lastVal, f;
            if (opts == null) { opts = {}; }
            f = $.serializeJSON;
            if (f.isUndefined(o)) { throw new Error("ArgumentError: param 'o' expected to be an object or array, found undefined"); }
            if (!keys || keys.length === 0) { throw new Error("ArgumentError: param 'keys' expected to be an array with least one element"); }

            key = keys[0];

            // Only one key, then it's not a deepSet, just assign the value.
            if (keys.length === 1) {
                if (key === '') {
                    o.push(value); // '' is used to push values into the array (assume o is an array)
                } else {
                    o[key] = value; // other keys can be used as object keys or array indexes
                }

                // With more keys is a deepSet. Apply recursively.
            } else {
                nextKey = keys[1];

                // '' is used to push values into the array,
                // with nextKey, set the value into the same object, in object[nextKey].
                // Covers the case of ['', 'foo'] and ['', 'var'] to push the object {foo, var}, and the case of nested arrays.
                if (key === '') {
                    lastIdx = o.length - 1; // asume o is array
                    lastVal = o[lastIdx];
                    if (f.isObject(lastVal) && (f.isUndefined(lastVal[nextKey]) || keys.length > 2)) { // if nextKey is not present in the last object element, or there are more keys to deep set
                        key = lastIdx; // then set the new value in the same object element
                    } else {
                        key = lastIdx + 1; // otherwise, point to set the next index in the array
                    }
                }

                // '' is used to push values into the array "array[]"
                if (nextKey === '') {
                    if (f.isUndefined(o[key]) || !$.isArray(o[key])) {
                        o[key] = []; // define (or override) as array to push values
                    }
                } else {
                    if (opts.useIntKeysAsArrayIndex && f.isValidArrayIndex(nextKey)) { // if 1, 2, 3 ... then use an array, where nextKey is the index
                        if (f.isUndefined(o[key]) || !$.isArray(o[key])) {
                            o[key] = []; // define (or override) as array, to insert values using int keys as array indexes
                        }
                    } else { // for anything else, use an object, where nextKey is going to be the attribute name
                        if (f.isUndefined(o[key]) || !f.isObject(o[key])) {
                            o[key] = {}; // define (or override) as object, to set nested properties
                        }
                    }
                }

                // Recursively set the inner object
                tail = keys.slice(1);
                f.deepSet(o[key], tail, value, opts);
            }
        }

    };

}));






/*!
 * jQuery Typeahead
 * Copyright (C) 2017 RunningCoder.org
 * Licensed under the MIT license
 *
 * @author Tom Bertrand
 * @version 2.10.4 (2017-10-17)
 * @link http://www.runningcoder.org/jquerytypeahead/
 */
(function (factory) {
    if (typeof define === "function" && define.amd) {
        define("jquery-typeahead", ["jquery"], function (jQuery) {
            return factory(jQuery);
        });
    } else if (typeof module === "object" && module.exports) {
        module.exports = (function (jQuery, root) {
            if (jQuery === undefined) {
                if (typeof window !== "undefined") {
                    jQuery = require("jquery");
                } else {
                    jQuery = require("jquery")(root);
                }
            }
            return factory(jQuery);
        })();
    } else {
        factory(jQuery);
    }
})(function ($) {
    "use strict";

    window.Typeahead = {
        version: '2.10.4'
    };

    /**
     * @private
     * Default options
     * @link http://www.runningcoder.org/jquerytypeahead/documentation/
     */
    var _options = {
        input: null,                // *RECOMMENDED*, jQuery selector to reach Typeahead's input for initialization
        minLength: 2,               // Accepts 0 to search on focus, minimum character length to perform a search
        maxLength: false,           // False as "Infinity" will not put character length restriction for searching results
        maxItem: 8,                 // Accepts 0 / false as "Infinity" meaning all the results will be displayed
        dynamic: false,             // When true, Typeahead will get a new dataset from the source option on every key press
        delay: 300,                 // delay in ms when dynamic option is set to true
        order: null,                // "asc" or "desc" to sort results
        offset: false,              // Set to true to match items starting from their first character
        hint: false,                // Added support for excessive "space" characters
        accent: false,              // Will allow to type accent and give letter equivalent results, also can define a custom replacement object
        highlight: true,            // Added "any" to highlight any word in the template, by default true will only highlight display keys
        multiselect: null,          // Multiselect configuration object, see documentation for all options
        group: false,               // Improved feature, Boolean,string,object(key, template (string, function))
        groupOrder: null,           // New feature, order groups "asc", "desc", Array, Function
        maxItemPerGroup: null,      // Maximum number of result per Group
        dropdownFilter: false,      // Take group options string and create a dropdown filter
        dynamicFilter: null,        // Filter the typeahead results based on dynamic value, Ex: Players based on TeamID
        backdrop: false,            // Add a backdrop behind Typeahead results
        backdropOnFocus: false,     // Display the backdrop option as the Typeahead input is :focused
        cache: false,               // Improved option, true OR 'localStorage' OR 'sessionStorage'
        ttl: 3600000,               // Cache time to live in ms
        compression: false,         // Requires LZString library
        searchOnFocus: false,       // Display search results on input focus
        blurOnTab: true,            // Blur Typeahead when Tab key is pressed, if false Tab will go though search results
        resultContainer: null,      // List the results inside any container string or jQuery object
        generateOnLoad: null,       // Forces the source to be generated on page load even if the input is not focused!
        mustSelectItem: false,      // The submit function only gets called if an item is selected
        href: null,                 // String or Function to format the url for right-click & open in new tab on link results
        display: ["display"],       // Allows search in multiple item keys ["display1", "display2"]
        template: null,             // Display template of each of the result list
        templateValue: null,        // Set the input value template when an item is clicked
        groupTemplate: null,        // Set a custom template for the groups
        correlativeTemplate: false, // Compile display keys, enables multiple key search from the template string
        emptyTemplate: false,       // Display an empty template if no result
        cancelButton: true,         // If text is detected in the input, a cancel button will be available to reset the input (pressing ESC also cancels)
        loadingAnimation: true,     // Display a loading animation when typeahead is doing request / searching for results
        filter: true,               // Set to false or function to bypass Typeahead filtering. WARNING: accent, correlativeTemplate, offset & matcher will not be interpreted
        matcher: null,              // Add an extra filtering function after the typeahead functions
        source: null,               // Source of data for Typeahead to filter
        callback: {
            onInit: null,               // When Typeahead is first initialized (happens only once)
            onReady: null,              // When the Typeahead initial preparation is completed
            onShowLayout: null,         // Called when the layout is shown
            onHideLayout: null,         // Called when the layout is hidden
            onSearch: null,             // When data is being fetched & analyzed to give search results
            onResult: null,             // When the result container is displayed
            onLayoutBuiltBefore: null,  // When the result HTML is build, modify it before it get showed
            onLayoutBuiltAfter: null,   // Modify the dom right after the results gets inserted in the result container
            onNavigateBefore: null,     // When a key is pressed to navigate the results, before the navigation happens
            onNavigateAfter: null,      // When a key is pressed to navigate the results
            onEnter: null,              // When an item in the result list is focused
            onLeave: null,              // When an item in the result list is blurred
            onClickBefore: null,        // Possibility to e.preventDefault() to prevent the Typeahead behaviors
            onClickAfter: null,         // Happens after the default clicked behaviors has been executed
            onDropdownFilter: null,     // When the dropdownFilter is changed, trigger this callback
            onSendRequest: null,        // Gets called when the Ajax request(s) are sent
            onReceiveRequest: null,     // Gets called when the Ajax request(s) are all received
            onPopulateSource: null,     // Perform operation on the source data before it gets in Typeahead data
            onCacheSave: null,          // Perform operation on the source data before it gets in Typeahead cache
            onSubmit: null,             // When Typeahead form is submitted
            onCancel: null              // Triggered if the typeahead had text inside and is cleared
        },
        selector: {
            container: "typeahead__container",
            result: "typeahead__result",
            list: "typeahead__list",
            group: "typeahead__group",
            item: "typeahead__item",
            empty: "typeahead__empty",
            display: "typeahead__display",
            query: "typeahead__query",
            filter: "typeahead__filter",
            filterButton: "typeahead__filter-button",
            dropdown: "typeahead__dropdown",
            dropdownItem: "typeahead__dropdown-item",
            labelContainer: "typeahead__label-container",
            label: "typeahead__label",
            button: "typeahead__button",
            backdrop: "typeahead__backdrop",
            hint: "typeahead__hint",
            cancelButton: "typeahead__cancel-button"
        },
        debug: false // Display debug information (RECOMMENDED for dev environment)
    };

    /**
     * @private
     * Event namespace
     */
    var _namespace = ".typeahead";

    /**
     * @private
     * Accent equivalents
     */
    var _accent = {
        from: "ãàáäâẽèéëêìíïîõòóöôùúüûñç",
        to: "aaaaaeeeeeiiiiooooouuuunc"
    };

    /**
     * #62 IE9 doesn't trigger "input" event when text gets removed (backspace, ctrl+x, etc)
     * @private
     */
    var _isIE9 = ~window.navigator.appVersion.indexOf("MSIE 9.");

    /**
     * #193 Clicking on a suggested option does not select it on IE10/11
     * @private
     */
    var _isIE10 = ~window.navigator.appVersion.indexOf("MSIE 10");
    var _isIE11 = ~window.navigator.userAgent.indexOf("Trident")
        ? ~window.navigator.userAgent.indexOf("rv:11")
        : false;

    // SOURCE GROUP RESERVED WORDS: ajax, data, url
    // SOURCE ITEMS RESERVED KEYS: group, display, data, matchedKey, compiled, href

    /**
     * @constructor
     * Typeahead Class
     *
     * @param {object} node jQuery input object
     * @param {object} options User defined options
     */
    var Typeahead = function (node, options) {
        this.rawQuery = node.val() || "";   // Unmodified input query
        this.query = node.val() || "";      // Input query
        this.selector = node[0].selector;   // Typeahead instance selector (to reach from window.Typeahead[SELECTOR])
        this.deferred = null;               // Promise when "input" event in triggered, this.node.triggerHandler('input').then(() => {})
        this.tmpSource = {};                // Temp var to preserve the source order for the searchResult function
        this.source = {};                   // The generated source kept in memory
        this.dynamicGroups = [];            // Store the source groups that are defined as dynamic
        this.hasDynamicGroups = false;      // Boolean if at least one of the groups has a dynamic source
        this.generatedGroupCount = 0;       // Number of groups generated, if limit reached the search can be done
        this.groupBy = "group";             // This option will change according to filtering or custom grouping
        this.groups = [];                   // Array of all the available groups, used to build the groupTemplate
        this.searchGroups = [];             // Array of groups to generate when Typeahead searches data
        this.generateGroups = [];           // Array of groups to generate when Typeahead requests data
        this.requestGroups = [];            // Array of groups to request via Ajax
        this.result = [];                   // Results based on Source-query match (only contains the displayed elements)
        this.tmpResult = {};                // Temporary object of results, before they get passed to the buildLayout function
        this.groupTemplate = "";            // Result template at the {{group}} level
        this.resultHtml = null;             // HTML Results (displayed elements)
        this.resultCount = 0;               // Total results based on Source-query match
        this.resultCountPerGroup = {};      // Total results based on Source-query match per group
        this.options = options;             // Typeahead options (Merged default & user defined)
        this.node = node;                   // jQuery object of the Typeahead <input>
        this.namespace =
            "." +
            this.helper.slugify.call(this, this.selector) +
            _namespace;                     // Every Typeahead instance gets its own namespace for events
        this.isContentEditable = typeof this.node.attr('contenteditable') !== "undefined"
            && this.node.attr('contenteditable') !== "false";
        this.container = null;              // Typeahead container, usually right after <form>
        this.resultContainer = null;        // Typeahead result container (html)
        this.item = null;                   // Selected item
        this.items = null;                  // Multiselect selected items
        this.comparedItems = null;          // Multiselect items stored for comparison
        this.xhr = {};                      // Ajax request(s) stack
        this.hintIndex = null;              // Numeric value of the hint index in the result list
        this.filters = {                    // Filter list for searching, dropdown and dynamic(s)
            dropdown: {},                   // Dropdown menu if options.dropdownFilter is set
            dynamic: {}                     // Checkbox / Radio / Select to filter the source data
        };
        this.dropdownFilter = {
            static: [],                     // Objects that has a value
            dynamic: []
        };
        this.dropdownFilterAll = null;      // The last "all" definition
        this.isDropdownEvent = false;       // If a dropdownFilter is clicked, this will be true to trigger the callback

        this.requests = {};                 // Store the group:request instead of generating them every time

        this.backdrop = {};                 // The backdrop object
        this.hint = {};                     // The hint object
        this.label = {};                    // The label object
        this.hasDragged = false;            // Will cancel mouseend events if true
        this.focusOnly = false;             // Focus the input preventing any operations

        this.__construct();
    };

    Typeahead.prototype = {
        _validateCacheMethod: function (cache) {
            var supportedCache = ["localStorage", "sessionStorage"],
                supported;

            if (cache === true) {
                cache = "localStorage";
            } else if (typeof cache === "string" && !~supportedCache.indexOf(cache)) {
                // {debug}
                if (this.options.debug) {
                    _debug.log({
                        node: this.selector,
                        function: "extendOptions()",
                        message: 'Invalid options.cache, possible options are "localStorage" or "sessionStorage"'
                    });

                    _debug.print();
                }
                // {/debug}
                return false;
            }

            supported = typeof window[cache] !== "undefined";

            try {
                window[cache].setItem("typeahead", "typeahead");
                window[cache].removeItem("typeahead");
            } catch (e) {
                supported = false;
            }

            return (supported && cache) || false;
        },

        extendOptions: function () {
            this.options.cache = this._validateCacheMethod(this.options.cache);

            if (this.options.compression) {
                if (typeof LZString !== "object" || !this.options.cache) {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "extendOptions()",
                            message: "Missing LZString Library or options.cache, no compression will occur."
                        });

                        _debug.print();
                    }
                    // {/debug}
                    this.options.compression = false;
                }
            }

            if (!this.options.maxLength || isNaN(this.options.maxLength)) {
                this.options.maxLength = Infinity;
            }

            if (
                typeof this.options.maxItem !== "undefined" && ~[0, false].indexOf(this.options.maxItem)
            ) {
                this.options.maxItem = Infinity;
            }

            if (
                this.options.maxItemPerGroup && !/^\d+$/.test(this.options.maxItemPerGroup)
            ) {
                this.options.maxItemPerGroup = null;
            }

            if (this.options.display && !Array.isArray(this.options.display)) {
                this.options.display = [this.options.display];
            }

            if (this.options.multiselect) {
                this.items = [];
                this.comparedItems = [];
                if (typeof this.options.multiselect.matchOn === "string") {
                    this.options.multiselect.matchOn = [this.options.multiselect.matchOn];
                }
            }

            if (this.options.group) {
                if (!Array.isArray(this.options.group)) {
                    if (typeof this.options.group === "string") {
                        this.options.group = {
                            key: this.options.group
                        };
                    } else if (typeof this.options.group === "boolean") {
                        this.options.group = {
                            key: "group"
                        };
                    }

                    this.options.group.key = this.options.group.key || "group";
                } else {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "extendOptions()",
                            message: "options.group must be a boolean|string|object as of 2.5.0"
                        });

                        _debug.print();
                    }
                    // {/debug}
                }
            }

            if (this.options.highlight && !~["any", true].indexOf(this.options.highlight)) {
                this.options.highlight = false;
            }

            if (
                this.options.dropdownFilter &&
                this.options.dropdownFilter instanceof Object
            ) {
                if (!Array.isArray(this.options.dropdownFilter)) {
                    this.options.dropdownFilter = [this.options.dropdownFilter];
                }
                for (var i = 0, ii = this.options.dropdownFilter.length; i < ii; ++i) {
                    this.dropdownFilter[
                        this.options.dropdownFilter[i].value ? "static" : "dynamic"
                        ].push(this.options.dropdownFilter[i]);
                }
            }

            if (this.options.dynamicFilter && !Array.isArray(this.options.dynamicFilter)) {
                this.options.dynamicFilter = [this.options.dynamicFilter];
            }

            if (this.options.accent) {
                if (typeof this.options.accent === "object") {
                    if (
                        this.options.accent.from &&
                        this.options.accent.to &&
                        this.options.accent.from.length !== this.options.accent.to.length
                    ) {
                        // {debug}
                        if (this.options.debug) {
                            _debug.log({
                                node: this.selector,
                                function: "extendOptions()",
                                message: 'Invalid "options.accent", from and to must be defined and same length.'
                            });

                            _debug.print();
                        }
                        // {/debug}
                    }

                } else {
                    this.options.accent = _accent;
                }
            }

            if (this.options.groupTemplate) {
                this.groupTemplate = this.options.groupTemplate;
            }

            if (this.options.resultContainer) {
                if (typeof this.options.resultContainer === "string") {
                    this.options.resultContainer = $(this.options.resultContainer);
                }

                if (
                    !(this.options.resultContainer instanceof $) || !this.options.resultContainer[0]
                ) {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "extendOptions()",
                            message: 'Invalid jQuery selector or jQuery Object for "options.resultContainer".'
                        });

                        _debug.print();
                    }
                    // {/debug}
                } else {
                    this.resultContainer = this.options.resultContainer;
                }
            }

            if (
                this.options.maxItemPerGroup &&
                this.options.group &&
                this.options.group.key
            ) {
                this.groupBy = this.options.group.key;
            }

            // Compatibility onClick callback
            if (this.options.callback && this.options.callback.onClick) {
                this.options.callback.onClickBefore = this.options.callback.onClick;
                delete this.options.callback.onClick;
            }

            // Compatibility onNavigate callback
            if (this.options.callback && this.options.callback.onNavigate) {
                this.options.callback.onNavigateBefore = this.options.callback.onNavigate;
                delete this.options.callback.onNavigate;
            }

            this.options = $.extend(true, {}, _options, this.options);
        },

        unifySourceFormat: function () {
            this.dynamicGroups = [];

            // source: ['item1', 'item2', 'item3']
            if (Array.isArray(this.options.source)) {
                this.options.source = {
                    group: {
                        data: this.options.source
                    }
                };
            }

            // source: "http://www.test.com/url.json"
            if (typeof this.options.source === "string") {
                this.options.source = {
                    group: {
                        ajax: {
                            url: this.options.source
                        }
                    }
                };
            }

            if (this.options.source.ajax) {
                this.options.source = {
                    group: {
                        ajax: this.options.source.ajax
                    }
                };
            }

            // source: {data: ['item1', 'item2'], url: "http://www.test.com/url.json"}
            if (this.options.source.url || this.options.source.data) {
                this.options.source = {
                    group: this.options.source
                };
            }

            var group, groupSource, tmpAjax;

            for (group in this.options.source) {
                if (!this.options.source.hasOwnProperty(group)) continue;

                groupSource = this.options.source[group];

                // source: {group: "http://www.test.com/url.json"}
                if (typeof groupSource === "string") {
                    groupSource = {
                        ajax: {
                            url: groupSource
                        }
                    };
                }

                // source: {group: {url: ["http://www.test.com/url.json", "json.path"]}}
                tmpAjax = groupSource.url || groupSource.ajax;
                if (Array.isArray(tmpAjax)) {
                    groupSource.ajax =
                        typeof tmpAjax[0] === "string"
                            ? {
                                url: tmpAjax[0]
                            }
                            : tmpAjax[0];
                    groupSource.ajax.path = groupSource.ajax.path || tmpAjax[1] || null;
                    delete groupSource.url;
                } else {
                    // source: {group: {url: {url: "http://www.test.com/url.json", method: "GET"}}}
                    // source: {group: {url: "http://www.test.com/url.json", dataType: "jsonp"}}
                    if (typeof groupSource.url === "object") {
                        groupSource.ajax = groupSource.url;
                    } else if (typeof groupSource.url === "string") {
                        groupSource.ajax = {
                            url: groupSource.url
                        };
                    }
                    delete groupSource.url;
                }

                if (!groupSource.data && !groupSource.ajax) {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "unifySourceFormat()",
                            arguments: JSON.stringify(this.options.source),
                            message: 'Undefined "options.source.' +
                            group +
                            '.[data|ajax]" is Missing - Typeahead dropped'
                        });

                        _debug.print();
                    }
                    // {/debug}

                    return false;
                }

                if (groupSource.display && !Array.isArray(groupSource.display)) {
                    groupSource.display = [groupSource.display];
                }

                groupSource.minLength =
                    typeof groupSource.minLength === "number"
                        ? groupSource.minLength
                        : this.options.minLength;
                groupSource.maxLength =
                    typeof groupSource.maxLength === "number"
                        ? groupSource.maxLength
                        : this.options.maxLength;
                groupSource.dynamic =
                    typeof groupSource.dynamic === "boolean" || this.options.dynamic;

                if (groupSource.minLength > groupSource.maxLength) {
                    groupSource.minLength = groupSource.maxLength;
                }
                this.options.source[group] = groupSource;

                if (this.options.source[group].dynamic) {
                    this.dynamicGroups.push(group);
                }

                groupSource.cache =
                    typeof groupSource.cache !== "undefined"
                        ? this._validateCacheMethod(groupSource.cache)
                        : this.options.cache;

                if (groupSource.compression) {
                    if (typeof LZString !== "object" || !groupSource.cache) {
                        // {debug}
                        if (this.options.debug) {
                            _debug.log({
                                node: this.selector,
                                function: "unifySourceFormat()",
                                message: "Missing LZString Library or group.cache, no compression will occur on group: " +
                                group
                            });

                            _debug.print();
                        }
                        // {/debug}
                        groupSource.compression = false;
                    }
                }
            }

            this.hasDynamicGroups =
                this.options.dynamic || !!this.dynamicGroups.length;

            return true;
        },

        init: function () {
            this.helper.executeCallback.call(this, this.options.callback.onInit, [
                this.node
            ]);

            this.container = this.node.closest("." + this.options.selector.container);

            // {debug}
            if (this.options.debug) {
                _debug.log({
                    node: this.selector,
                    function: "init()",
                    //'arguments': JSON.stringify(this.options),
                    message: "OK - Typeahead activated on " + this.selector
                });

                _debug.print();
            }
            // {/debug}
        },

        delegateEvents: function () {
            var scope = this,
                events = [
                    "focus" + this.namespace,
                    "input" + this.namespace,
                    "propertychange" + this.namespace, // IE8 Fix
                    "keydown" + this.namespace,
                    "keyup" + this.namespace, // IE9 Fix
                    "search" + this.namespace,
                    "generate" + this.namespace
                ];

            // #149 - Adding support for Mobiles
            $("html")
                .on("touchmove", function () {
                    scope.hasDragged = true;
                })
                .on("touchstart", function () {
                    scope.hasDragged = false;
                });

            this.node
                .closest("form")
                .on("submit", function (e) {
                    if (
                        scope.options.mustSelectItem &&
                        scope.helper.isEmpty(scope.item)
                    ) {
                        e.preventDefault();
                        return;
                    }

                    if (!scope.options.backdropOnFocus) {
                        scope.hideLayout();
                    }

                    if (scope.options.callback.onSubmit) {
                        return scope.helper.executeCallback.call(
                            scope,
                            scope.options.callback.onSubmit,
                            [scope.node, this, scope.item || scope.items, e]
                        );
                    }
                })
                .on("reset", function () {
                    // #221 - Reset Typeahead on form reset.
                    // setTimeout to re-queue the `input.typeahead` event at the end
                    setTimeout(function () {
                        scope.node.trigger("input" + scope.namespace);
                        // #243 - minLength: 0 opens the Typeahead results
                        scope.hideLayout();
                    });
                });

            // IE8 fix
            var preventNextEvent = false;

            // IE10/11 fix
            if (this.node.attr("placeholder") && (_isIE10 || _isIE11)) {
                var preventInputEvent = true;

                this.node.on("focusin focusout", function () {
                    preventInputEvent = !!(!this.value && this.placeholder);
                });

                this.node.on("input", function (e) {
                    if (preventInputEvent) {
                        e.stopImmediatePropagation();
                        preventInputEvent = false;
                    }
                });
            }

            this.node
                .off(this.namespace)
                .on(events.join(" "), function (e, data) {
                    switch (e.type) {
                        case "generate":
                            scope.generateSource(Object.keys(scope.options.source));
                            break;
                        case "focus":
                            if (scope.focusOnly) {
                                scope.focusOnly = false;
                                break;
                            }
                            if (scope.options.backdropOnFocus) {
                                scope.buildBackdropLayout();
                                scope.showLayout();
                            }
                            if (scope.options.searchOnFocus && !scope.item) {
                                scope.deferred = $.Deferred();
                                scope.assignQuery();
                                scope.generateSource();
                            }
                            break;
                        case "keydown":
                            if (e.keyCode === 8
                                && scope.options.multiselect
                                && scope.options.multiselect.cancelOnBackspace
                                && scope.query === ''
                                && scope.items.length
                            ) {
                                scope.cancelMultiselectItem(scope.items.length - 1, null, e);
                            } else if (e.keyCode && ~[9, 13, 27, 38, 39, 40].indexOf(e.keyCode)) {
                                preventNextEvent = true;
                                scope.navigate(e);
                            }
                            break;
                        case "keyup":
                            if (
                                _isIE9 &&
                                scope.node[0].value.replace(/^\s+/, "").toString().length <
                                scope.query.length
                            ) {
                                scope.node.trigger("input" + scope.namespace);
                            }
                            break;
                        case "propertychange":
                            if (preventNextEvent) {
                                preventNextEvent = false;
                                break;
                            }
                        case "input":
                            scope.deferred = $.Deferred();
                            scope.assignQuery();

                            // #195 Trigger an onCancel event if the Typeahead is cleared
                            if (scope.rawQuery === "" && scope.query === "") {
                                e.originalEvent = data || {};
                                scope.helper.executeCallback.call(
                                    scope,
                                    scope.options.callback.onCancel,
                                    [scope.node, e]
                                );
                            }

                            scope.options.cancelButton &&
                            scope.toggleCancelButtonVisibility();

                            if (
                                scope.options.hint &&
                                scope.hint.container &&
                                scope.hint.container.val() !== ""
                            ) {
                                if (scope.hint.container.val().indexOf(scope.rawQuery) !== 0) {
                                    scope.hint.container.val("");
                                    if (scope.isContentEditable) {
                                        scope.hint.container.text("");
                                    }
                                }
                            }

                            if (scope.hasDynamicGroups) {
                                scope.helper.typeWatch(function () {
                                    scope.generateSource();
                                }, scope.options.delay);
                            } else {
                                scope.generateSource();
                            }
                            break;
                        case "search":
                            scope.searchResult();
                            scope.buildLayout();

                            if (scope.result.length ||
                                (scope.searchGroups.length &&
                                    scope.options.emptyTemplate &&
                                    scope.query.length)
                            ) {
                                scope.showLayout();
                            } else {
                                scope.hideLayout();
                            }

                            scope.deferred && scope.deferred.resolve();
                            break;
                    }

                    return scope.deferred && scope.deferred.promise();
                });

            if (this.options.generateOnLoad) {
                this.node.trigger("generate" + this.namespace);
            }
        },

        assignQuery: function () {
            if (this.isContentEditable) {
                this.rawQuery = this.node.text();
            } else {
                this.rawQuery = this.node.val().toString();
            }
            this.rawQuery = this.rawQuery.replace(/^\s+/, "");

            if (this.rawQuery !== this.query) {
                this.item = null;
                this.query = this.rawQuery;
            }
        },

        filterGenerateSource: function () {
            this.searchGroups = [];
            this.generateGroups = [];

            if (this.focusOnly && !this.options.multiselect) return;

            for (var group in this.options.source) {
                if (!this.options.source.hasOwnProperty(group)) continue;
                if (
                    this.query.length >= this.options.source[group].minLength &&
                    this.query.length <= this.options.source[group].maxLength
                ) {
                    this.searchGroups.push(group);
                    if (!this.options.source[group].dynamic && this.source[group]) {
                        continue;
                    }
                    this.generateGroups.push(group);
                }
            }
        },

        generateSource: function (generateGroups) {
            this.filterGenerateSource();
            if (Array.isArray(generateGroups) && generateGroups.length) {
                this.generateGroups = generateGroups;
            } else if (!this.generateGroups.length) {
                this.node.trigger("search" + this.namespace);
                return;
            }

            this.requestGroups = [];
            this.generatedGroupCount = 0;
            this.options.loadingAnimation && this.container.addClass("loading");

            if (!this.helper.isEmpty(this.xhr)) {
                for (var i in this.xhr) {
                    if (!this.xhr.hasOwnProperty(i)) continue;
                    this.xhr[i].abort();
                }
                this.xhr = {};
            }

            var scope = this,
                group,
                groupData,
                groupSource,
                cache,
                compression,
                dataInStorage,
                isValidStorage;

            for (var i = 0, ii = this.generateGroups.length; i < ii; ++i) {
                group = this.generateGroups[i];
                groupSource = this.options.source[group];
                cache = groupSource.cache;
                compression = groupSource.compression;

                if (cache) {
                    dataInStorage = window[cache].getItem(
                        "TYPEAHEAD_" + this.selector + ":" + group
                    );
                    if (dataInStorage) {
                        if (compression) {
                            dataInStorage = LZString.decompressFromUTF16(dataInStorage);
                        }

                        isValidStorage = false;
                        try {
                            dataInStorage = JSON.parse(dataInStorage + "");

                            if (
                                dataInStorage.data &&
                                dataInStorage.ttl > new Date().getTime()
                            ) {
                                this.populateSource(dataInStorage.data, group);
                                isValidStorage = true;

                                // {debug}
                                if (this.options.debug) {
                                    _debug.log({
                                        node: this.selector,
                                        function: "generateSource()",
                                        message: 'Source for group "' + group + '" found in ' + cache
                                    });
                                    _debug.print();
                                }
                                // {/debug}
                            } else {
                                window[cache].removeItem(
                                    "TYPEAHEAD_" + this.selector + ":" + group
                                );
                            }
                        } catch (error) {
                        }

                        if (isValidStorage) continue;
                    }
                }

                if (groupSource.data && !groupSource.ajax) {
                    // #198 Add support for async data source
                    if (typeof groupSource.data === "function") {
                        groupData = groupSource.data.call(this);
                        if (Array.isArray(groupData)) {
                            scope.populateSource(groupData, group);
                        } else if (typeof groupData.promise === "function") {
                            (function (group) {
                                $.when(groupData).then(function (deferredData) {
                                    if (deferredData && Array.isArray(deferredData)) {
                                        scope.populateSource(deferredData, group);
                                    }
                                });
                            })(group);
                        }
                    } else {
                        this.populateSource($.extend(true, [], groupSource.data), group);
                    }
                    continue;
                }

                if (groupSource.ajax) {
                    if (!this.requests[group]) {
                        this.requests[group] = this.generateRequestObject(group);
                    }
                    this.requestGroups.push(group);
                }
            }

            if (this.requestGroups.length) {
                this.handleRequests();
            }

            return !!this.generateGroups.length;
        },

        generateRequestObject: function (group) {
            var scope = this,
                groupSource = this.options.source[group];

            var xhrObject = {
                request: {
                    url: groupSource.ajax.url || null,
                    dataType: "json",
                    beforeSend: function (jqXHR, options) {
                        // Important to call .abort() in case of dynamic requests
                        scope.xhr[group] = jqXHR;

                        var beforeSend =
                            scope.requests[group].callback.beforeSend ||
                            groupSource.ajax.beforeSend;
                        typeof beforeSend === "function" &&
                        beforeSend.apply(null, arguments);
                    }
                },
                callback: {
                    beforeSend: null,
                    done: null,
                    fail: null,
                    then: null,
                    always: null
                },
                extra: {
                    path: groupSource.ajax.path || null,
                    group: group
                },
                validForGroup: [group]
            };

            if (typeof groupSource.ajax !== "function") {
                if (groupSource.ajax instanceof Object) {
                    xhrObject = this.extendXhrObject(xhrObject, groupSource.ajax);
                }

                if (Object.keys(this.options.source).length > 1) {
                    for (var _group in this.requests) {
                        if (!this.requests.hasOwnProperty(_group)) continue;
                        if (this.requests[_group].isDuplicated) continue;

                        if (
                            xhrObject.request.url &&
                            xhrObject.request.url === this.requests[_group].request.url
                        ) {
                            this.requests[_group].validForGroup.push(group);
                            xhrObject.isDuplicated = true;
                            delete xhrObject.validForGroup;
                        }
                    }
                }
            }

            return xhrObject;
        },

        extendXhrObject: function (xhrObject, groupRequest) {
            if (typeof groupRequest.callback === "object") {
                xhrObject.callback = groupRequest.callback;
                delete groupRequest.callback;
            }

            // #132 Fixed beforeSend when using a function as the request object
            if (typeof groupRequest.beforeSend === "function") {
                xhrObject.callback.beforeSend = groupRequest.beforeSend;
                delete groupRequest.beforeSend;
            }

            // Fixes #105 Allow user to define their beforeSend function.
            // Fixes #181 IE8 incompatibility
            xhrObject.request = $.extend(true, xhrObject.request, groupRequest);

            // JSONP needs a unique jsonpCallback to run concurrently
            if (
                xhrObject.request.dataType.toLowerCase() === "jsonp" && !xhrObject.request.jsonpCallback
            ) {
                xhrObject.request.jsonpCallback = "callback_" + xhrObject.extra.group;
            }

            return xhrObject;
        },

        handleRequests: function () {
            var scope = this,
                group,
                requestsCount = this.requestGroups.length;

            if (
                this.helper.executeCallback.call(
                    this,
                    this.options.callback.onSendRequest,
                    [this.node, this.query]
                ) === false
            ) {
                return;
            }

            for (var i = 0, ii = this.requestGroups.length; i < ii; ++i) {
                group = this.requestGroups[i];
                if (this.requests[group].isDuplicated) continue;

                (function (group, xhrObject) {
                    if (typeof scope.options.source[group].ajax === "function") {
                        var _groupRequest = scope.options.source[group].ajax.call(
                            scope,
                            scope.query
                        );

                        // Fixes #271 Data is cached inside the xhrObject
                        xhrObject = scope.extendXhrObject(
                            scope.generateRequestObject(group),
                            typeof _groupRequest === "object" ? _groupRequest : {}
                        );

                        if (
                            typeof xhrObject.request !== "object" || !xhrObject.request.url
                        ) {
                            // {debug}
                            if (scope.options.debug) {
                                _debug.log({
                                    node: scope.selector,
                                    function: "handleRequests",
                                    message: 'Source function must return an object containing ".url" key for group "' +
                                    group +
                                    '"'
                                });
                                _debug.print();
                            }
                            // {/debug}
                            scope.populateSource([], group);
                            return;
                        }
                        scope.requests[group] = xhrObject;
                    }

                    var _request,
                        _isExtended = false, // Prevent the main request from being changed
                        _groupData = {};

                    if (~xhrObject.request.url.indexOf("{{query}}")) {
                        if (!_isExtended) {
                            xhrObject = $.extend(true, {}, xhrObject);
                            _isExtended = true;
                        }
                        // #184 Invalid encoded characters on dynamic requests for `{{query}}`
                        xhrObject.request.url = xhrObject.request.url.replace(
                            "{{query}}",
                            encodeURIComponent(scope.query)
                        );
                    }

                    if (xhrObject.request.data) {
                        for (var i in xhrObject.request.data) {
                            if (!xhrObject.request.data.hasOwnProperty(i)) continue;
                            if (~String(xhrObject.request.data[i]).indexOf("{{query}}")) {
                                if (!_isExtended) {
                                    xhrObject = $.extend(true, {}, xhrObject);
                                    _isExtended = true;
                                }
                                // jQuery handles encodeURIComponent when the query is inside the data object
                                xhrObject.request.data[i] = xhrObject.request.data[i].replace(
                                    "{{query}}",
                                    scope.query
                                );
                                break;
                            }
                        }
                    }

                    $.ajax(xhrObject.request)
                        .done(function (data, textStatus, jqXHR) {
                            var _group;

                            for (
                                var i = 0, ii = xhrObject.validForGroup.length;
                                i < ii;
                                i++
                            ) {
                                _group = xhrObject.validForGroup[i];
                                _request = scope.requests[_group];

                                if (_request.callback.done instanceof Function) {
                                    _groupData[_group] = _request.callback.done.call(
                                        scope,
                                        data,
                                        textStatus,
                                        jqXHR
                                    );

                                    // {debug}
                                    if (
                                        !Array.isArray(_groupData[_group]) ||
                                        typeof _groupData[_group] !== "object"
                                    ) {
                                        if (scope.options.debug) {
                                            _debug.log({
                                                node: scope.selector,
                                                function: "Ajax.callback.done()",
                                                message: "Invalid returned data has to be an Array"
                                            });
                                            _debug.print();
                                        }
                                    }
                                    // {/debug}
                                }
                            }
                        })
                        .fail(function (jqXHR, textStatus, errorThrown) {
                            for (
                                var i = 0, ii = xhrObject.validForGroup.length;
                                i < ii;
                                i++
                            ) {
                                _request = scope.requests[xhrObject.validForGroup[i]];
                                _request.callback.fail instanceof Function &&
                                _request.callback.fail.call(
                                    scope,
                                    jqXHR,
                                    textStatus,
                                    errorThrown
                                );
                            }

                            // {debug}
                            if (scope.options.debug) {
                                _debug.log({
                                    node: scope.selector,
                                    function: "Ajax.callback.fail()",
                                    arguments: JSON.stringify(xhrObject.request),
                                    message: textStatus
                                });

                                console.log(errorThrown);

                                _debug.print();
                            }
                            // {/debug}
                        })
                        .always(function (data, textStatus, jqXHR) {
                            var _group;
                            for (
                                var i = 0, ii = xhrObject.validForGroup.length;
                                i < ii;
                                i++
                            ) {
                                _group = xhrObject.validForGroup[i];
                                _request = scope.requests[_group];
                                _request.callback.always instanceof Function &&
                                _request.callback.always.call(scope, data, textStatus, jqXHR);

                                // #248, #303 Aborted requests would call populate with invalid data
                                if (typeof jqXHR !== "object") return;

                                // #265 Modified data from ajax.callback.done is not being registered (use of _groupData[_group])
                                scope.populateSource(
                                    (data !== null && typeof data.promise === "function" && []) ||
                                    _groupData[_group] ||
                                    data,
                                    _request.extra.group,
                                    _request.extra.path || _request.request.path
                                );

                                requestsCount -= 1;
                                if (requestsCount === 0) {
                                    scope.helper.executeCallback.call(
                                        scope,
                                        scope.options.callback.onReceiveRequest,
                                        [scope.node, scope.query]
                                    );
                                }
                            }
                        })
                        .then(function (jqXHR, textStatus) {
                            for (
                                var i = 0, ii = xhrObject.validForGroup.length;
                                i < ii;
                                i++
                            ) {
                                _request = scope.requests[xhrObject.validForGroup[i]];
                                _request.callback.then instanceof Function &&
                                _request.callback.then.call(scope, jqXHR, textStatus);
                            }
                        });
                })(group, this.requests[group]);
            }
        },

        /**
         * Build the source groups to be cycled for matched results
         *
         * @param {Array} data Array of Strings or Array of Objects
         * @param {String} group
         * @param {String} [path]
         * @return {*}
         */
        populateSource: function (data, group, path) {
            var scope = this,
                groupSource = this.options.source[group],
                extraData = groupSource.ajax && groupSource.data;

            if (path && typeof path === "string") {
                data = this.helper.namespace.call(this, path, data);
            }

            if (typeof data === "undefined") {
                // {debug}
                if (this.options.debug) {
                    _debug.log({
                        node: this.selector,
                        function: "populateSource()",
                        arguments: path,
                        message: "Invalid data path."
                    });

                    _debug.print();
                }
                // {/debug}
            }

            if (!Array.isArray(data)) {
                // {debug}
                if (this.options.debug) {
                    _debug.log({
                        node: this.selector,
                        function: "populateSource()",
                        arguments: JSON.stringify({group: group}),
                        message: "Invalid data type, must be Array type."
                    });
                    _debug.print();
                }
                // {/debug}
                data = [];
            }

            if (extraData) {
                if (typeof extraData === "function") {
                    extraData = extraData();
                }

                if (Array.isArray(extraData)) {
                    data = data.concat(extraData);
                } else {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "populateSource()",
                            arguments: JSON.stringify(extraData),
                            message: "WARNING - this.options.source." +
                            group +
                            ".data Must be an Array or a function that returns an Array."
                        });

                        _debug.print();
                    }
                    // {/debug}
                }
            }

            var tmpObj,
                display = groupSource.display
                    ? groupSource.display[0] === "compiled"
                        ? groupSource.display[1]
                        : groupSource.display[0]
                    : this.options.display[0] === "compiled"
                        ? this.options.display[1]
                        : this.options.display[0];

            for (var i = 0, ii = data.length; i < ii; i++) {
                if (data[i] === null || typeof data[i] === "boolean") {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "populateSource()",
                            message: "WARNING - NULL/BOOLEAN value inside " +
                            group +
                            "! The data was skipped."
                        });

                        _debug.print();
                    }
                    // {/debug}
                    continue;
                }
                if (typeof data[i] === "string") {
                    tmpObj = {};
                    tmpObj[display] = data[i];
                    data[i] = tmpObj;
                }
                data[i].group = group;
            }

            if (!this.hasDynamicGroups && this.dropdownFilter.dynamic.length) {
                var key,
                    value,
                    tmpValues = {};

                for (var i = 0, ii = data.length; i < ii; i++) {
                    for (
                        var k = 0, kk = this.dropdownFilter.dynamic.length;
                        k < kk;
                        k++
                    ) {
                        key = this.dropdownFilter.dynamic[k].key;

                        value = data[i][key];
                        if (!value) continue;
                        if (!this.dropdownFilter.dynamic[k].value) {
                            this.dropdownFilter.dynamic[k].value = [];
                        }
                        if (!tmpValues[key]) {
                            tmpValues[key] = [];
                        }
                        if (!~tmpValues[key].indexOf(value.toLowerCase())) {
                            tmpValues[key].push(value.toLowerCase());
                            this.dropdownFilter.dynamic[k].value.push(value);
                        }
                    }
                }
            }

            if (this.options.correlativeTemplate) {
                var template = groupSource.template || this.options.template,
                    compiledTemplate = "";

                if (typeof template === "function") {
                    template = template.call(this, "", {});
                }

                if (!template) {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.selector,
                            function: "populateSource()",
                            arguments: String(group),
                            message: "WARNING - this.options.correlativeTemplate is enabled but no template was found."
                        });

                        _debug.print();
                    }
                    // {/debug}
                } else {
                    // #109 correlativeTemplate can be an array of display keys instead of the complete template
                    if (Array.isArray(this.options.correlativeTemplate)) {
                        for (
                            var i = 0, ii = this.options.correlativeTemplate.length;
                            i < ii;
                            i++
                        ) {
                            compiledTemplate +=
                                "{{" + this.options.correlativeTemplate[i] + "}} ";
                        }
                    } else {
                        // Strip down the html tags, #351 if the template needs "<>" use html entities instead &#60;{{email}}&#62;
                        compiledTemplate = template
                            .replace(/<.+?>/g, " ")
                            .replace(/\s{2,}/, " ")
                            .trim();
                    }

                    for (var i = 0, ii = data.length; i < ii; i++) {
                        // Fix #351, convert htmlEntities from the template string
                        data[i].compiled = $("<textarea />")
                            .html(
                                compiledTemplate
                                    .replace(/\{\{([\w\-\.]+)(?:\|(\w+))?}}/g, function (match,
                                                                                         index) {
                                        return scope.helper.namespace.call(
                                            scope,
                                            index,
                                            data[i],
                                            "get",
                                            ""
                                        );
                                    })
                                    .trim()
                            )
                            .text();
                    }

                    if (groupSource.display) {
                        if (!~groupSource.display.indexOf("compiled")) {
                            groupSource.display.unshift("compiled");
                        }
                    } else if (!~this.options.display.indexOf("compiled")) {
                        this.options.display.unshift("compiled");
                    }
                }
            }

            if (this.options.callback.onPopulateSource) {
                data = this.helper.executeCallback.call(
                    this,
                    this.options.callback.onPopulateSource,
                    [this.node, data, group, path]
                );

                // {debug}
                if (this.options.debug) {
                    if (!data || !Array.isArray(data)) {
                        _debug.log({
                            node: this.selector,
                            function: "callback.populateSource()",
                            message: 'callback.onPopulateSource must return the "data" parameter'
                        });

                        _debug.print();
                    }
                }
                // {/debug}
            }

            // Save the data inside tmpSource to re-order once every requests are completed
            this.tmpSource[group] = (Array.isArray(data) && data) || [];

            var cache = this.options.source[group].cache,
                compression = this.options.source[group].compression,
                ttl = this.options.source[group].ttl || this.options.ttl;

            if (
                cache && !window[cache].getItem("TYPEAHEAD_" + this.selector + ":" + group)
            ) {
                if (this.options.callback.onCacheSave) {
                    data = this.helper.executeCallback.call(
                        this,
                        this.options.callback.onCacheSave,
                        [this.node, data, group, path]
                    );

                    // {debug}
                    if (this.options.debug) {
                        if (!data || !Array.isArray(data)) {
                            _debug.log({
                                node: this.selector,
                                function: "callback.populateSource()",
                                message: 'callback.onCacheSave must return the "data" parameter'
                            });

                            _debug.print();
                        }
                    }
                    // {/debug}
                }

                var storage = JSON.stringify({
                    data: data,
                    ttl: new Date().getTime() + ttl
                });

                if (compression) {
                    storage = LZString.compressToUTF16(storage);
                }

                window[cache].setItem(
                    "TYPEAHEAD_" + this.selector + ":" + group,
                    storage
                );
            }

            this.incrementGeneratedGroup();
        },

        incrementGeneratedGroup: function () {
            this.generatedGroupCount++;
            if (this.generatedGroupCount !== this.generateGroups.length) {
                return;
            }

            this.xhr = {};

            for (var i = 0, ii = this.generateGroups.length; i < ii; i++) {
                this.source[this.generateGroups[i]] = this.tmpSource[
                    this.generateGroups[i]
                    ];
            }

            if (!this.hasDynamicGroups) {
                this.buildDropdownItemLayout("dynamic");
            }

            this.options.loadingAnimation && this.container.removeClass("loading");
            this.node.trigger("search" + this.namespace);
        },

        /**
         * Key Navigation
         * tab 9: if option is enabled, blur Typeahead
         * Up 38: select previous item, skip "group" item
         * Down 40: select next item, skip "group" item
         * Right 39: change charAt, if last char fill hint (if options is true)
         * Esc 27: clears input (is not empty) / blur (if empty)
         * Enter 13: Select item + submit search
         *
         * @param {Object} e Event object
         * @returns {*}
         */
        navigate: function (e) {
            this.helper.executeCallback.call(
                this,
                this.options.callback.onNavigateBefore,
                [this.node, this.query, e]
            );

            if (e.keyCode === 27) {
                // #166 Different browsers do not have the same behaviors by default, lets enforce what we want instead
                e.preventDefault();
                if (this.query.length) {
                    this.resetInput();
                    this.node.trigger("input" + this.namespace, [e]);
                } else {
                    this.node.blur();
                    this.hideLayout();
                }
                return;
            }

            if (!this.result.length) return;

            var itemList = this.resultContainer
                    .find("." + this.options.selector.item)
                    .not("[disabled]"),
                activeItem = itemList.filter(".active"),
                activeItemIndex = activeItem[0] ? itemList.index(activeItem) : null,
                activeDataIndex = activeItem[0] ? activeItem.attr("data-index") : null,
                newActiveItemIndex = null,
                newActiveDataIndex = null;

            this.clearActiveItem();

            this.helper.executeCallback.call(this, this.options.callback.onLeave, [
                this.node,
                (activeItemIndex !== null && itemList.eq(activeItemIndex)) || undefined,
                (activeDataIndex !== null && this.result[activeDataIndex]) || undefined,
                e
            ]);

            if (e.keyCode === 13) {
                // Chrome needs preventDefault else the input search event is triggered
                e.preventDefault();
                if (activeItem.length > 0) {
                    // #311 When href is defined and "enter" is pressed, it needs to act as a "clicked" link
                    if (activeItem.find("a:first")[0].href === "javascript:;") {
                        activeItem.find("a:first").trigger("click", e);
                    } else {
                        activeItem.find("a:first")[0].click();
                    }
                } else {
                    this.node
                        .closest("form")
                        .trigger("submit");
                }
                return;
            }

            if (e.keyCode === 39) {
                if (activeItemIndex !== null) {
                    itemList
                        .eq(activeItemIndex)
                        .find("a:first")[0]
                        .click();
                } else if (
                    this.options.hint &&
                    this.hint.container.val() !== "" &&
                    this.helper.getCaret(this.node[0]) >= this.query.length
                ) {
                    itemList
                        .filter('[data-index="' + this.hintIndex + '"]')
                        .find("a:first")[0]
                        .click();
                }
                return;
            }

            // #284 Blur Typeahead when "Tab" key is pressed
            // #326 Improve Up / Down / Tab navigation to have only 1 "selected" item
            if (e.keyCode === 9) {
                if (this.options.blurOnTab) {
                    this.hideLayout();
                } else {
                    if (activeItem.length > 0) {
                        if (activeItemIndex + 1 < itemList.length) {
                            e.preventDefault();
                            newActiveItemIndex = activeItemIndex + 1;
                            this.addActiveItem(itemList.eq(newActiveItemIndex));
                        } else {
                            this.hideLayout();
                        }
                    } else {
                        if (itemList.length) {
                            e.preventDefault();
                            newActiveItemIndex = 0;
                            this.addActiveItem(itemList.first());
                        } else {
                            this.hideLayout();
                        }
                    }
                }
            } else if (e.keyCode === 38) {
                e.preventDefault();

                if (activeItem.length > 0) {
                    if (activeItemIndex - 1 >= 0) {
                        newActiveItemIndex = activeItemIndex - 1;
                        this.addActiveItem(itemList.eq(newActiveItemIndex));
                    }
                } else if (itemList.length) {
                    newActiveItemIndex = itemList.length - 1;
                    this.addActiveItem(itemList.last());
                }
            } else if (e.keyCode === 40) {
                e.preventDefault();

                if (activeItem.length > 0) {
                    if (activeItemIndex + 1 < itemList.length) {
                        newActiveItemIndex = activeItemIndex + 1;
                        this.addActiveItem(itemList.eq(newActiveItemIndex));
                    }
                } else if (itemList.length) {
                    newActiveItemIndex = 0;
                    this.addActiveItem(itemList.first());
                }
            }

            newActiveDataIndex =
                newActiveItemIndex !== null
                    ? itemList.eq(newActiveItemIndex).attr("data-index")
                    : null;

            this.helper.executeCallback.call(this, this.options.callback.onEnter, [
                this.node,
                (newActiveItemIndex !== null && itemList.eq(newActiveItemIndex)) ||
                undefined,
                (newActiveDataIndex !== null && this.result[newActiveDataIndex]) ||
                undefined,
                e
            ]);

            // #115 Prevent the input from changing when navigating (arrow up / down) the results
            if (e.preventInputChange && ~[38, 40].indexOf(e.keyCode)) {
                this.buildHintLayout(
                    newActiveDataIndex !== null && newActiveDataIndex < this.result.length
                        ? [this.result[newActiveDataIndex]]
                        : null
                );
            }

            if (this.options.hint && this.hint.container) {
                this.hint.container.css(
                    "color",
                    e.preventInputChange
                        ? this.hint.css.color
                        : (newActiveDataIndex === null && this.hint.css.color) ||
                        this.hint.container.css("background-color") ||
                        "fff"
                );
            }

            var nodeValue =
                newActiveDataIndex === null || e.preventInputChange
                    ? this.rawQuery
                    : this.getTemplateValue.call(this, this.result[newActiveDataIndex]);

            this.node.val(nodeValue);
            if (this.isContentEditable) {
                this.node.text(nodeValue);
            }

            this.helper.executeCallback.call(
                this,
                this.options.callback.onNavigateAfter,
                [
                    this.node,
                    itemList,
                    (newActiveItemIndex !== null &&
                        itemList.eq(newActiveItemIndex).find("a:first")) ||
                    undefined,
                    (newActiveDataIndex !== null && this.result[newActiveDataIndex]) ||
                    undefined,
                    this.query,
                    e
                ]
            );
        },

        getTemplateValue: function (item) {
            if (!item) return;
            var templateValue =
                (item.group && this.options.source[item.group].templateValue) ||
                this.options.templateValue;
            if (typeof templateValue === "function") {
                templateValue = templateValue.call(this);
            }
            if (!templateValue) {
                return this.helper.namespace
                    .call(this, item.matchedKey, item)
                    .toString();
            }
            var scope = this;

            return templateValue.replace(/\{\{([\w\-.]+)}}/gi, function (match, index) {
                return scope.helper.namespace.call(scope, index, item, "get", "");
            });
        },

        clearActiveItem: function () {
            this.resultContainer
                .find("." + this.options.selector.item)
                .removeClass("active");
        },

        addActiveItem: function (item) {
            item.addClass("active");
        },

        searchResult: function () {
            this.resetLayout();

            if (
                this.helper.executeCallback.call(this, this.options.callback.onSearch, [
                    this.node,
                    this.query
                ]) === false
            ) return;

            if (
                this.searchGroups.length && !(
                    this.options.multiselect &&
                    this.options.multiselect.limit &&
                    this.items.length >= this.options.multiselect.limit
                )
            ) {
                this.searchResultData();
            }

            this.helper.executeCallback.call(this, this.options.callback.onResult, [
                this.node,
                this.query,
                this.result,
                this.resultCount,
                this.resultCountPerGroup
            ]);

            if (this.isDropdownEvent) {
                this.helper.executeCallback.call(
                    this,
                    this.options.callback.onDropdownFilter,
                    [this.node, this.query, this.filters.dropdown, this.result]
                );
                this.isDropdownEvent = false;
            }
        },

        searchResultData: function () {
            var scope = this,
                group,
                groupBy = this.groupBy,
                groupReference = null,
                item,
                match,
                comparedDisplay,
                comparedQuery = this.query.toLowerCase(),
                maxItem = this.options.maxItem,
                maxItemPerGroup = this.options.maxItemPerGroup,
                hasDynamicFilters =
                    this.filters.dynamic && !this.helper.isEmpty(this.filters.dynamic),
                displayKeys,
                displayValue,
                missingDisplayKey = {},
                groupFilter,
                groupFilterResult,
                groupMatcher,
                groupMatcherResult,
                matcher =
                    typeof this.options.matcher === "function" && this.options.matcher,
                correlativeMatch,
                correlativeQuery,
                correlativeDisplay;

            if (this.options.accent) {
                comparedQuery = this.helper.removeAccent.call(this, comparedQuery);
            }

            for (var i = 0, ii = this.searchGroups.length; i < ii; ++i) {
                group = this.searchGroups[i];

                if (
                    this.filters.dropdown &&
                    this.filters.dropdown.key === "group" &&
                    this.filters.dropdown.value !== group
                )
                    continue;

                groupFilter =
                    typeof this.options.source[group].filter !== "undefined"
                        ? this.options.source[group].filter
                        : this.options.filter;
                groupMatcher =
                    (typeof this.options.source[group].matcher === "function" &&
                        this.options.source[group].matcher) ||
                    matcher;

                for (var k = 0, kk = this.source[group].length; k < kk; k++) {
                    if (this.resultItemCount >= maxItem && !this.options.callback.onResult) break;
                    if (hasDynamicFilters && !this.dynamicFilter.validate.apply(this, [this.source[group][k]])) continue;

                    item = this.source[group][k];
                    // Validation over null item
                    if (item === null || typeof item === "boolean") continue;
                    if (this.options.multiselect && !this.isMultiselectUniqueData(item)) continue;

                    // dropdownFilter by custom groups
                    if (
                        this.filters.dropdown &&
                        (item[this.filters.dropdown.key] || "").toLowerCase() !==
                        (this.filters.dropdown.value || "").toLowerCase()
                    ) {
                        continue;
                    }

                    groupReference =
                        groupBy === "group"
                            ? group
                            : item[groupBy] ? item[groupBy] : item.group;

                    if (groupReference && !this.tmpResult[groupReference]) {
                        this.tmpResult[groupReference] = [];
                        this.resultCountPerGroup[groupReference] = 0;
                    }

                    if (maxItemPerGroup) {
                        if (
                            groupBy === "group" &&
                            this.tmpResult[groupReference].length >= maxItemPerGroup && !this.options.callback.onResult
                        ) {
                            break;
                        }
                    }

                    displayKeys = this.options.source[group].display || this.options.display;
                    for (var v = 0, vv = displayKeys.length; v < vv; ++v) {
                        // #286 option.filter: false shouldn't bother about the option.display keys
                        if (groupFilter !== false) {
                            // #183 Allow searching for deep source object keys
                            displayValue = /\./.test(displayKeys[v])
                                ? this.helper.namespace.call(this, displayKeys[v], item)
                                : item[displayKeys[v]];

                            // #182 Continue looping if empty or undefined key
                            if (typeof displayValue === "undefined" || displayValue === "") {
                                // {debug}
                                if (this.options.debug) {
                                    missingDisplayKey[v] = {
                                        display: displayKeys[v],
                                        data: item
                                    };
                                }
                                // {/debug}
                                continue;
                            }

                            displayValue = this.helper.cleanStringFromScript(displayValue);
                        }

                        if (typeof groupFilter === "function") {
                            groupFilterResult = groupFilter.call(this, item, displayValue);

                            // return undefined to skip to next item
                            // return false to attempt the matching function on the next displayKey
                            // return true to add the item to the result list
                            // return item object to modify the item and add it to the result list

                            if (groupFilterResult === undefined) break;
                            if (!groupFilterResult) continue;
                            if (typeof groupFilterResult === "object") {
                                item = groupFilterResult;
                            }
                        }

                        if (~[undefined, true].indexOf(groupFilter)) {
                            comparedDisplay = displayValue;
                            comparedDisplay = comparedDisplay.toString().toLowerCase();

                            if (this.options.accent) {
                                comparedDisplay = this.helper.removeAccent.call(
                                    this,
                                    comparedDisplay
                                );
                            }

                            match = comparedDisplay.indexOf(comparedQuery);

                            if (
                                this.options.correlativeTemplate &&
                                displayKeys[v] === "compiled" &&
                                match < 0 &&
                                /\s/.test(comparedQuery)
                            ) {
                                correlativeMatch = true;
                                correlativeQuery = comparedQuery.split(" ");
                                correlativeDisplay = comparedDisplay;
                                for (var x = 0, xx = correlativeQuery.length; x < xx; x++) {
                                    if (correlativeQuery[x] === "") continue;
                                    if (!~correlativeDisplay.indexOf(correlativeQuery[x])) {
                                        correlativeMatch = false;
                                        break;
                                    }
                                    correlativeDisplay = correlativeDisplay.replace(
                                        correlativeQuery[x],
                                        ""
                                    );
                                }
                            }

                            if (match < 0 && !correlativeMatch) continue;
                            if (this.options.offset && match !== 0) continue;

                            if (groupMatcher) {
                                groupMatcherResult = groupMatcher.call(
                                    this,
                                    item,
                                    displayValue
                                );

                                // return undefined to skip to next item
                                // return false to attempt the matching function on the next displayKey
                                // return true to add the item to the result list
                                // return item object to modify the item and add it to the result list

                                if (groupMatcherResult === undefined) break;
                                if (!groupMatcherResult) continue;
                                if (typeof groupMatcherResult === "object") {
                                    item = groupMatcherResult;
                                }
                            }
                        }

                        this.resultCount++;
                        this.resultCountPerGroup[groupReference]++;

                        if (this.resultItemCount < maxItem) {
                            if (
                                maxItemPerGroup &&
                                this.tmpResult[groupReference].length >= maxItemPerGroup
                            ) {
                                break;
                            }

                            this.tmpResult[groupReference].push(
                                $.extend(true, {matchedKey: displayKeys[v]}, item)
                            );
                            this.resultItemCount++;
                        }
                        break;
                    }

                    if (!this.options.callback.onResult) {
                        if (this.resultItemCount >= maxItem) {
                            break;
                        }
                        if (
                            maxItemPerGroup &&
                            this.tmpResult[groupReference].length >= maxItemPerGroup
                        ) {
                            if (groupBy === "group") {
                                break;
                            }
                        }
                    }
                }
            }

            // {debug}
            if (this.options.debug) {
                if (!this.helper.isEmpty(missingDisplayKey)) {
                    _debug.log({
                        node: this.selector,
                        function: "searchResult()",
                        arguments: JSON.stringify(missingDisplayKey),
                        message: "Missing keys for display, make sure options.display is set properly."
                    });

                    _debug.print();
                }
            }
            // {/debug}

            if (this.options.order) {
                var displayKeys = [],
                    displayKey;

                for (var group in this.tmpResult) {
                    if (!this.tmpResult.hasOwnProperty(group)) continue;
                    for (var i = 0, ii = this.tmpResult[group].length; i < ii; i++) {
                        displayKey =
                            this.options.source[this.tmpResult[group][i].group].display ||
                            this.options.display;
                        if (!~displayKeys.indexOf(displayKey[0])) {
                            displayKeys.push(displayKey[0]);
                        }
                    }
                    this.tmpResult[group].sort(
                        scope.helper.sort(
                            displayKeys,
                            scope.options.order === "asc",
                            function (a) {
                                return a.toString().toUpperCase();
                            }
                        )
                    );
                }
            }

            var concatResults = [],
                groupOrder = [];

            if (typeof this.options.groupOrder === "function") {
                groupOrder = this.options.groupOrder.apply(this, [
                    this.node,
                    this.query,
                    this.tmpResult,
                    this.resultCount,
                    this.resultCountPerGroup
                ]);
            } else if (Array.isArray(this.options.groupOrder)) {
                groupOrder = this.options.groupOrder;
            } else if (
                typeof this.options.groupOrder === "string" && ~["asc", "desc"].indexOf(this.options.groupOrder)
            ) {
                groupOrder = Object.keys(this.tmpResult).sort(
                    scope.helper.sort([], scope.options.groupOrder === "asc", function (a) {
                        return a.toString().toUpperCase();
                    })
                );
            } else {
                groupOrder = Object.keys(this.tmpResult);
            }

            for (var i = 0, ii = groupOrder.length; i < ii; i++) {
                concatResults = concatResults.concat(this.tmpResult[groupOrder[i]] || []);
            }

            // #286 groupTemplate option was deleting group reference Array
            this.groups = JSON.parse(JSON.stringify(groupOrder));

            this.result = concatResults;
        },

        buildLayout: function () {
            this.buildHtmlLayout();

            this.buildBackdropLayout();

            this.buildHintLayout();

            if (this.options.callback.onLayoutBuiltBefore) {
                this.helper.executeCallback.call(
                    this,
                    this.options.callback.onLayoutBuiltBefore,
                    [this.node, this.query, this.result, this.resultHtml]
                );
            }

            if (this.resultHtml instanceof $) {
                this.resultContainer.html(this.resultHtml);
            }

            if (this.options.callback.onLayoutBuiltAfter) {
                this.helper.executeCallback.call(
                    this,
                    this.options.callback.onLayoutBuiltAfter,
                    [this.node, this.query, this.result]
                );
            }
        },

        buildHtmlLayout: function () {
            // #150 Add the option to have no resultList but still perform the search and trigger the callbacks
            if (this.options.resultContainer === false) return;

            if (!this.resultContainer) {
                this.resultContainer = $("<div/>", {
                    class: this.options.selector.result
                });

                this.container.append(this.resultContainer);
            }

            var emptyTemplate;
            if (!this.result.length) {
                if (
                    this.options.multiselect &&
                    this.options.multiselect.limit &&
                    this.items.length >= this.options.multiselect.limit
                ) {
                    if (this.options.multiselect.limitTemplate) {
                        emptyTemplate =
                            typeof this.options.multiselect.limitTemplate === "function"
                                ? this.options.multiselect.limitTemplate.call(this, this.query)
                                : this.options.multiselect.limitTemplate.replace(
                                /\{\{query}}/gi,
                                $("<div>")
                                    .text(this.helper.cleanStringFromScript(this.query))
                                    .html()
                                );
                    } else {
                        emptyTemplate =
                            "Can't select more than " + this.items.length + " items.";
                    }
                } else if (this.options.emptyTemplate && this.query !== "") {
                    emptyTemplate =
                        typeof this.options.emptyTemplate === "function"
                            ? this.options.emptyTemplate.call(this, this.query)
                            : this.options.emptyTemplate.replace(
                            /\{\{query}}/gi,
                            $("<div>")
                                .text(this.helper.cleanStringFromScript(this.query))
                                .html()
                            );
                } else {
                    return;
                }
            }

            var _query = this.query.toLowerCase();
            if (this.options.accent) {
                _query = this.helper.removeAccent.call(this, _query);
            }

            var scope = this,
                groupTemplate = this.groupTemplate || "<ul></ul>",
                hasEmptyTemplate = false;

            if (this.groupTemplate) {
                groupTemplate = $(
                    groupTemplate.replace(
                        /<([^>]+)>\{\{(.+?)}}<\/[^>]+>/g,
                        function (match, tag, group, offset, string) {
                            var template = "",
                                groups = group === "group" ? scope.groups : [group];

                            if (!scope.result.length) {
                                if (hasEmptyTemplate === true) return "";
                                hasEmptyTemplate = true;

                                return (
                                    "<" + tag + ' class="' + scope.options.selector.empty + '">' + emptyTemplate + "</" + tag + ">"
                                );
                            }

                            for (var i = 0, ii = groups.length; i < ii; ++i) {
                                template += "<" + tag + ' data-group-template="' + groups[i] + '"><ul></ul></' + tag + ">";
                            }

                            return template;
                        }
                    )
                );
            } else {
                groupTemplate = $(groupTemplate);
                if (!this.result.length) {
                    groupTemplate.append(
                        emptyTemplate instanceof $
                            ? emptyTemplate
                            : '<li class="' +
                            scope.options.selector.empty +
                            '">' +
                            emptyTemplate +
                            "</li>"
                    );
                }
            }

            groupTemplate.addClass(
                this.options.selector.list +
                (this.helper.isEmpty(this.result) ? " empty" : "")
            );

            var _group,
                _groupTemplate,
                _item,
                _href,
                _liHtml,
                _template,
                _aHtml,
                _display,
                _displayKeys,
                _displayValue,
                _unusedGroups =
                    (this.groupTemplate && this.result.length && scope.groups) || [],
                _tmpIndexOf;

            for (var i = 0, ii = this.result.length; i < ii; ++i) {
                _item = this.result[i];
                _group = _item.group;
                _href =
                    (!this.options.multiselect &&
                        this.options.source[_item.group].href) ||
                    this.options.href;
                _display = [];
                _displayKeys =
                    this.options.source[_item.group].display || this.options.display;

                if (this.options.group) {
                    _group = _item[this.options.group.key];
                    if (this.options.group.template) {
                        if (typeof this.options.group.template === "function") {
                            _groupTemplate = this.options.group.template.call(this, _item);
                        } else if (typeof this.options.group.template === "string") {
                            _groupTemplate = this.options.group.template.replace(
                                /\{\{([\w\-\.]+)}}/gi,
                                function (match, index) {
                                    return scope.helper.namespace.call(
                                        scope,
                                        index,
                                        _item,
                                        "get",
                                        ""
                                    );
                                }
                            );
                        }
                    }

                    if (!groupTemplate.find('[data-search-group="' + _group + '"]')[0]) {
                        (this.groupTemplate
                            ? groupTemplate.find('[data-group-template="' + _group + '"] ul')
                            : groupTemplate).append(
                            $("<li/>", {
                                class: scope.options.selector.group,
                                html: $("<a/>", {
                                    href: "javascript:;",
                                    html: _groupTemplate || _group,
                                    tabindex: -1
                                }),
                                "data-search-group": _group
                            })
                        );
                    }
                }

                if (this.groupTemplate && _unusedGroups.length) {
                    _tmpIndexOf = _unusedGroups.indexOf(_group || _item.group);
                    if (~_tmpIndexOf) {
                        _unusedGroups.splice(_tmpIndexOf, 1);
                    }
                }

                _liHtml = $("<li/>", {
                    class: scope.options.selector.item + " " + scope.options.selector.group + "-" + this.helper.slugify.call(this, _group),
                    disabled: _item.disabled ? true : false,
                    "data-group": _group,
                    "data-index": i,
                    html: $("<a/>", {
                        href: _href && !_item.disabled
                            ? (function (href, item) {
                                return item.href = scope.generateHref.call(
                                    scope,
                                    href,
                                    item
                                );
                            })(_href, _item)
                            : "javascript:;",
                        html: function () {
                            _template =
                                (_item.group && scope.options.source[_item.group].template) ||
                                scope.options.template;

                            if (_template) {
                                if (typeof _template === "function") {
                                    _template = _template.call(scope, scope.query, _item);
                                }

                                _aHtml = _template.replace(
                                    /\{\{([^\|}]+)(?:\|([^}]+))*}}/gi,
                                    function (match, index, options) {
                                        var value = scope.helper.cleanStringFromScript(
                                            String(
                                                scope.helper.namespace.call(
                                                    scope,
                                                    index,
                                                    _item,
                                                    "get",
                                                    ""
                                                )
                                            )
                                        );

                                        // #151 Slugify should be an option, not enforced
                                        options = (options && options.split("|")) || [];
                                        if (~options.indexOf("slugify")) {
                                            value = scope.helper.slugify.call(scope, value);
                                        }

                                        if (!~options.indexOf("raw")) {
                                            if (
                                                scope.options.highlight === true &&
                                                _query && ~_displayKeys.indexOf(index)
                                            ) {
                                                value = scope.helper.highlight.call(
                                                    scope,
                                                    value,
                                                    _query.split(" "),
                                                    scope.options.accent
                                                );
                                            }
                                        }
                                        return value;
                                    }
                                );
                            } else {
                                for (var i = 0, ii = _displayKeys.length; i < ii; i++) {
                                    _displayValue = /\./.test(_displayKeys[i])
                                        ? scope.helper.namespace.call(
                                            scope,
                                            _displayKeys[i],
                                            _item,
                                            "get",
                                            ""
                                        )
                                        : _item[_displayKeys[i]];

                                    if (
                                        typeof _displayValue === "undefined" ||
                                        _displayValue === ""
                                    )
                                        continue;

                                    _display.push(_displayValue);
                                }

                                _aHtml =
                                    '<span class="' +
                                    scope.options.selector.display +
                                    '">' +
                                    scope.helper.cleanStringFromScript(
                                        String(_display.join(" "))
                                    ) +
                                    "</span>";
                            }

                            if (
                                (scope.options.highlight === true && _query && !_template) ||
                                scope.options.highlight === "any"
                            ) {
                                _aHtml = scope.helper.highlight.call(
                                    scope,
                                    _aHtml,
                                    _query.split(" "),
                                    scope.options.accent
                                );
                            }

                            $(this).append(_aHtml);
                        }
                    })
                });

                (function (i, item, liHtml) {
                    liHtml.on("click", function (e, originalEvent) {
                        if (item.disabled) {
                            e.preventDefault();
                            return;
                        }

                        // #208 - Attach "keyboard Enter" original event
                        if (originalEvent && typeof originalEvent === "object") {
                            e.originalEvent = originalEvent;
                        }

                        if (scope.options.mustSelectItem && scope.helper.isEmpty(item)) {
                            e.preventDefault();
                            return;
                        }

                        if (scope.options.multiselect) {
                            scope.items.push(item);
                            scope.comparedItems.push(scope.getMultiselectComparedData(item));
                        } else {
                            scope.item = item;
                        }

                        if (
                            scope.helper.executeCallback.call(
                                scope,
                                scope.options.callback.onClickBefore,
                                [scope.node, $(this), item, e]
                            ) === false
                        ) return;

                        if (
                            (e.originalEvent && e.originalEvent.defaultPrevented) ||
                            e.isDefaultPrevented()
                        ) return;

                        var templateValue = scope.getTemplateValue.call(scope, item);

                        if (scope.options.multiselect) {
                            scope.query = scope.rawQuery = "";
                            scope.addMultiselectItemLayout(templateValue);
                        } else {
                            scope.focusOnly = true;
                            scope.query = scope.rawQuery = templateValue;
                            if (scope.isContentEditable) {
                                scope.node.text(scope.query);
                                scope.helper.setCaretAtEnd(scope.node[0]);
                            }
                        }

                        scope.hideLayout();

                        scope.node
                            .val(scope.query)
                            .focus();

                        scope.helper.executeCallback.call(
                            scope,
                            scope.options.callback.onClickAfter,
                            [scope.node, $(this), item, e]
                        );
                    });
                    liHtml.on("mouseenter", function (e) {
                        if (!item.disabled) {
                            scope.clearActiveItem();
                            scope.addActiveItem($(this));
                        }
                        scope.helper.executeCallback.call(
                            scope,
                            scope.options.callback.onEnter,
                            [scope.node, $(this), item, e]
                        );
                    });
                    liHtml.on("mouseleave", function (e) {
                        if (!item.disabled) {
                            scope.clearActiveItem();
                        }
                        scope.helper.executeCallback.call(
                            scope,
                            scope.options.callback.onLeave,
                            [scope.node, $(this), item, e]
                        );
                    });
                })(i, _item, _liHtml);

                (this.groupTemplate
                    ? groupTemplate.find('[data-group-template="' + _group + '"] ul')
                    : groupTemplate).append(_liHtml);
            }

            if (this.result.length && _unusedGroups.length) {
                for (var i = 0, ii = _unusedGroups.length; i < ii; ++i) {
                    groupTemplate
                        .find('[data-group-template="' + _unusedGroups[i] + '"]')
                        .remove();
                }
            }

            this.resultHtml = groupTemplate;
        },

        generateHref: function (href, item) {
            var scope = this;

            if (typeof href === "string") {
                href = href.replace(
                    /\{\{([^\|}]+)(?:\|([^}]+))*}}/gi,
                    function (match, index, options) {
                        var value = scope.helper.namespace.call(
                            scope,
                            index,
                            item,
                            "get",
                            ""
                        );

                        // #151 Slugify should be an option, not enforced
                        options = (options && options.split("|")) || [];
                        if (~options.indexOf("slugify")) {
                            value = scope.helper.slugify.call(scope, value);
                        }

                        return value;
                    }
                );
            } else if (typeof href === "function") {
                href = href.call(this, item);
            }

            return href;
        },

        getMultiselectComparedData: function (item) {
            var uniqueComparedItem = "";
            if (Array.isArray(this.options.multiselect.matchOn)) {
                for (
                    var i = 0, ii = this.options.multiselect.matchOn.length;
                    i < ii;
                    ++i
                ) {
                    uniqueComparedItem +=
                        typeof item[this.options.multiselect.matchOn[i]] !== "undefined"
                            ? item[this.options.multiselect.matchOn[i]]
                            : "";
                }
            } else {
                var tmpItem = JSON.parse(JSON.stringify(item)),
                    extraKeys = ["group", "matchedKey", "compiled", "href"];

                for (var i = 0, ii = extraKeys.length; i < ii; ++i) {
                    delete tmpItem[extraKeys[i]];
                }
                uniqueComparedItem = JSON.stringify(tmpItem);
            }
            return uniqueComparedItem;
        },

        buildBackdropLayout: function () {
            if (!this.options.backdrop) return;

            if (!this.backdrop.container) {
                this.backdrop.css = $.extend(
                    {
                        opacity: 0.6,
                        filter: "alpha(opacity=60)",
                        position: "fixed",
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0,
                        "z-index": 1040,
                        "background-color": "#000"
                    },
                    this.options.backdrop
                );

                this.backdrop.container = $("<div/>", {
                    class: this.options.selector.backdrop,
                    css: this.backdrop.css
                }).insertAfter(this.container);
            }
            this.container.addClass("backdrop").css({
                "z-index": this.backdrop.css["z-index"] + 1,
                position: "relative"
            });
        },

        buildHintLayout: function (result) {
            if (!this.options.hint) return;
            // #144 hint doesn't overlap with the input when the query is too long
            if (this.node[0].scrollWidth > Math.ceil(this.node.innerWidth())) {
                this.hint.container && this.hint.container.val("");
                return;
            }

            var scope = this,
                hint = "",
                result = result || this.result,
                query = this.query.toLowerCase();

            if (this.options.accent) {
                query = this.helper.removeAccent.call(this, query);
            }

            this.hintIndex = null;

            if (this.searchGroups.length) {
                if (!this.hint.container) {
                    this.hint.css = $.extend(
                        {
                            "border-color": "transparent",
                            position: "absolute",
                            top: 0,
                            display: "inline",
                            "z-index": -1,
                            float: "none",
                            color: "silver",
                            "box-shadow": "none",
                            cursor: "default",
                            "-webkit-user-select": "none",
                            "-moz-user-select": "none",
                            "-ms-user-select": "none",
                            "user-select": "none"
                        },
                        this.options.hint
                    );

                    this.hint.container = $("<" + this.node[0].nodeName + "/>", {
                        type: this.node.attr("type"),
                        class: this.node.attr("class"),
                        readonly: true,
                        unselectable: "on",
                        "aria-hidden": "true",
                        tabindex: -1,
                        click: function () {
                            // IE8 Fix
                            scope.node.focus();
                        }
                    })
                        .addClass(this.options.selector.hint)
                        .css(this.hint.css)
                        .insertAfter(this.node);

                    this.node.parent().css({
                        position: "relative"
                    });
                }

                this.hint.container.css("color", this.hint.css.color);

                // Do not display hint for empty query
                if (query) {
                    var _displayKeys, _group, _comparedValue;

                    for (var i = 0, ii = result.length; i < ii; i++) {
                        if (result[i].disabled) continue;

                        _group = result[i].group;
                        _displayKeys =
                            this.options.source[_group].display || this.options.display;

                        for (var k = 0, kk = _displayKeys.length; k < kk; k++) {
                            _comparedValue = String(result[i][_displayKeys[k]]).toLowerCase();
                            if (this.options.accent) {
                                _comparedValue = this.helper.removeAccent.call(
                                    this,
                                    _comparedValue
                                );
                            }

                            if (_comparedValue.indexOf(query) === 0) {
                                hint = String(result[i][_displayKeys[k]]);
                                this.hintIndex = i;
                                break;
                            }
                        }
                        if (this.hintIndex !== null) {
                            break;
                        }
                    }
                }

                var hintValue =
                    (hint.length > 0 &&
                        this.rawQuery + hint.substring(this.query.length)) ||
                    "";
                this.hint.container.val(hintValue);

                if (this.isContentEditable) {
                    this.hint.container.text(hintValue);
                }
            }
        },

        buildDropdownLayout: function () {
            if (!this.options.dropdownFilter) return;

            var scope = this;

            $("<span/>", {
                class: this.options.selector.filter,
                html: function () {
                    $(this).append(
                        $("<button/>", {
                            type: "button",
                            class: scope.options.selector.filterButton,
                            style: "display: none;",
                            click: function () {
                                scope.container.toggleClass("filter");

                                var _ns = scope.namespace + "-dropdown-filter";

                                $("html").off(_ns);

                                if (scope.container.hasClass("filter")) {
                                    $("html").on("click" + _ns + " touchend" + _ns, function (e) {
                                        if (
                                            ($(e.target).closest(
                                                "." + scope.options.selector.filter
                                                )[0] &&
                                                $(e.target).closest(scope.container)[0]) ||
                                            scope.hasDragged
                                        )
                                            return;
                                        scope.container.removeClass("filter");

                                        $("html").off(_ns);
                                    });
                                }
                            }
                        })
                    );

                    $(this).append(
                        $("<ul/>", {
                            class: scope.options.selector.dropdown
                        })
                    );
                }
            }).insertAfter(scope.container.find("." + scope.options.selector.query));
        },

        buildDropdownItemLayout: function (type) {
            if (!this.options.dropdownFilter) return;

            var scope = this,
                template,
                all =
                    (typeof this.options.dropdownFilter === "string" &&
                        this.options.dropdownFilter) ||
                    "All",
                ulScope = this.container.find("." + this.options.selector.dropdown),
                filter;

            // Use regular groups defined in options.source
            if (
                type === "static" &&
                (this.options.dropdownFilter === true ||
                    typeof this.options.dropdownFilter === "string")
            ) {
                this.dropdownFilter.static.push({
                    key: "group",
                    template: "{{group}}",
                    all: all,
                    value: Object.keys(this.options.source)
                });
            }

            for (var i = 0, ii = this.dropdownFilter[type].length; i < ii; i++) {
                filter = this.dropdownFilter[type][i];

                if (!Array.isArray(filter.value)) {
                    filter.value = [filter.value];
                }

                if (filter.all) {
                    this.dropdownFilterAll = filter.all;
                }

                for (var k = 0, kk = filter.value.length; k <= kk; k++) {
                    // Only add "all" at the last filter iteration
                    if (k === kk && i !== ii - 1) {
                        continue;
                    } else if (k === kk && i === ii - 1) {
                        if (type === "static" && this.dropdownFilter.dynamic.length) {
                            continue;
                        }
                    }

                    template = this.dropdownFilterAll || all;
                    if (filter.value[k]) {
                        if (filter.template) {
                            template = filter.template.replace(
                                new RegExp("{{" + filter.key + "}}", "gi"),
                                filter.value[k]
                            );
                        } else {
                            template = filter.value[k];
                        }
                    } else {
                        this.container
                            .find("." + scope.options.selector.filterButton)
                            .html(template);
                    }

                    (function (k, filter, template) {
                        ulScope.append(
                            $("<li/>", {
                                class: scope.options.selector.dropdownItem +
                                " " +
                                scope.helper.slugify.call(
                                    scope,
                                    filter.key + "-" + (filter.value[k] || all)
                                ),
                                html: $("<a/>", {
                                    href: "javascript:;",
                                    html: template,
                                    click: function (e) {
                                        e.preventDefault();
                                        _selectFilter.call(scope, {
                                            key: filter.key,
                                            value: filter.value[k] || "*",
                                            template: template
                                        });
                                    }
                                })
                            })
                        );
                    })(k, filter, template);
                }
            }

            if (this.dropdownFilter[type].length) {
                this.container
                    .find("." + scope.options.selector.filterButton)
                    .removeAttr("style");
            }

            /**
             * @private
             * Select the filter and rebuild the result group
             *
             * @param {object} item
             */
            function _selectFilter(item) {
                if (item.value === "*") {
                    delete this.filters.dropdown;
                } else {
                    this.filters.dropdown = item;
                }

                this.container
                    .removeClass("filter")
                    .find("." + this.options.selector.filterButton)
                    .html(item.template);

                this.isDropdownEvent = true;
                this.node.trigger("search" + this.namespace);

                if (this.options.multiselect) {
                    this.adjustInputSize();
                }

                this.node.focus();
            }
        },

        dynamicFilter: {
            isEnabled: false,
            init: function () {
                if (!this.options.dynamicFilter) return;

                this.dynamicFilter.bind.call(this);
                this.dynamicFilter.isEnabled = true;
            },

            validate: function (item) {
                var isValid,
                    softValid = null,
                    hardValid = null,
                    itemValue;

                for (var key in this.filters.dynamic) {
                    if (!this.filters.dynamic.hasOwnProperty(key)) continue;
                    if (!!~key.indexOf(".")) {
                        itemValue = this.helper.namespace.call(this, key, item, "get");
                    } else {
                        itemValue = item[key];
                    }

                    if (this.filters.dynamic[key].modifier === "|" && !softValid) {
                        softValid = itemValue == this.filters.dynamic[key].value || false;
                    }

                    if (this.filters.dynamic[key].modifier === "&") {
                        // Leaving "==" in case of comparing number with string
                        if (itemValue == this.filters.dynamic[key].value) {
                            hardValid = true;
                        } else {
                            hardValid = false;
                            break;
                        }
                    }
                }

                isValid = softValid;
                if (hardValid !== null) {
                    isValid = hardValid;
                    if (hardValid === true && softValid !== null) {
                        isValid = softValid;
                    }
                }

                return !!isValid;
            },

            set: function (key, value) {
                var matches = key.match(/^([|&])?(.+)/);

                if (!value) {
                    delete this.filters.dynamic[matches[2]];
                } else {
                    this.filters.dynamic[matches[2]] = {
                        modifier: matches[1] || "|",
                        value: value
                    };
                }

                if (this.dynamicFilter.isEnabled) {
                    this.generateSource();
                }
            },
            bind: function () {
                var scope = this,
                    filter;

                for (var i = 0, ii = this.options.dynamicFilter.length; i < ii; i++) {
                    filter = this.options.dynamicFilter[i];

                    if (typeof filter.selector === "string") {
                        filter.selector = $(filter.selector);
                    }

                    if (
                        !(filter.selector instanceof $) || !filter.selector[0] || !filter.key
                    ) {
                        // {debug}
                        if (this.options.debug) {
                            _debug.log({
                                node: this.selector,
                                function: "buildDynamicLayout()",
                                message: 'Invalid jQuery selector or jQuery Object for "filter.selector" or missing filter.key'
                            });

                            _debug.print();
                        }
                        // {/debug}
                        continue;
                    }

                    (function (filter) {
                        filter.selector
                            .off(scope.namespace)
                            .on("change" + scope.namespace, function () {
                                scope.dynamicFilter.set.apply(scope, [
                                    filter.key,
                                    scope.dynamicFilter.getValue(this)
                                ]);
                            })
                            .trigger("change" + scope.namespace);
                    })(filter);
                }
            },

            getValue: function (tag) {
                var value;
                if (tag.tagName === "SELECT") {
                    value = tag.value;
                } else if (tag.tagName === "INPUT") {
                    if (tag.type === "checkbox") {
                        value =
                            (tag.checked && tag.getAttribute("value")) || tag.checked || null;
                    } else if (tag.type === "radio" && tag.checked) {
                        value = tag.value;
                    }
                }
                return value;
            }
        },

        buildMultiselectLayout: function () {
            if (!this.options.multiselect) return;
            var scope = this;
            var multiselectData;

            this.label.container = $("<span/>", {
                class: this.options.selector.labelContainer,
                "data-padding-left": parseFloat(this.node.css("padding-left")) || 0,
                "data-padding-right": parseFloat(this.node.css("padding-right")) || 0,
                "data-padding-top": parseFloat(this.node.css("padding-top")) || 0,
                click: function (e) {
                    if ($(e.target).hasClass(scope.options.selector.labelContainer)) {
                        scope.node.focus();
                    }
                }
            });

            this.node
                .closest("." + this.options.selector.query)
                .prepend(this.label.container);

            if (!this.options.multiselect.data) return;

            if (Array.isArray(this.options.multiselect.data)) {
                this.populateMultiselectData(this.options.multiselect.data);
            } else if (typeof this.options.multiselect.data === 'function') {
                multiselectData = this.options.multiselect.data.call(this);
                if (Array.isArray(multiselectData)) {
                    this.populateMultiselectData(multiselectData);
                } else if (typeof multiselectData.promise === "function") {
                    $.when(multiselectData).then(function (deferredData) {
                        if (deferredData && Array.isArray(deferredData)) {
                            scope.populateMultiselectData(deferredData);
                        }
                    });
                }
            }
        },

        isMultiselectUniqueData: function (data) {
            var isUniqueData = true;
            for (var x = 0, xx = this.comparedItems.length; x < xx; ++x) {
                if (
                    this.comparedItems[x] ===
                    this.getMultiselectComparedData(data)
                ) {
                    isUniqueData = false;
                    break;
                }
            }
            return isUniqueData;
        },

        populateMultiselectData: function (data) {
            for (var i = 0, ii = data.length; i < ii; ++i) {
                if (!this.isMultiselectUniqueData(data[i])) continue;

                this.items.push(data[i]);
                this.comparedItems.push(
                    this.getMultiselectComparedData(data[i])
                );
                this.addMultiselectItemLayout(
                    this.getTemplateValue(data[i])
                );
            }

            this.node.trigger("search" + this.namespace, { origin: 'populateMultiselectData' });
        },

        addMultiselectItemLayout: function (templateValue) {
            var scope = this,
                htmlTag = this.options.multiselect.href ? "a" : "span";

            var label = $("<span/>", {
                class: this.options.selector.label,
                html: $("<" + htmlTag + "/>", {
                    text: templateValue,
                    click: function (e) {
                        var currentLabel = $(this).closest(
                            "." + scope.options.selector.label
                            ),
                            index = scope.label.container
                                .find("." + scope.options.selector.label)
                                .index(currentLabel);

                        scope.options.multiselect.callback && scope.helper.executeCallback.call(
                            scope,
                            scope.options.multiselect.callback.onClick,
                            [scope.node, scope.items[index], e]
                        );
                    },
                    href: this.options.multiselect.href
                        ? (function (item) {
                            return scope.generateHref.call(
                                scope,
                                scope.options.multiselect.href,
                                item
                            );
                        })(scope.items[scope.items.length - 1])
                        : null
                })
            });

            label.append(
                $("<span/>", {
                    class: this.options.selector.cancelButton,
                    html: "×",
                    click: function (e) {
                        var label = $(this).closest(
                            "." + scope.options.selector.label
                            ),
                            index = scope.label.container
                                .find("." + scope.options.selector.label)
                                .index(label);

                        scope.cancelMultiselectItem(index, label, e);
                    }
                })
            );

            this.label.container.append(label);
            this.adjustInputSize();
        },

        cancelMultiselectItem: function (index, label, e) {
            var item = this.items[index];

            label = label
                || this.label.container
                    .find('.' + this.options.selector.label)
                    .eq(index);

            label.remove();

            this.items.splice(index, 1);
            this.comparedItems.splice(index, 1);

            this.options.multiselect.callback && this.helper.executeCallback.call(
                this,
                this.options.multiselect.callback.onCancel,
                [this.node, item, e]
            );

            this.adjustInputSize();

            this.focusOnly = true;
            this.node.focus().trigger('input' + this.namespace, { origin: 'cancelMultiselectItem' });
        },

        adjustInputSize: function () {
            var nodeWidth =
                this.node[0].getBoundingClientRect().width -
                (parseFloat(this.label.container.data("padding-right")) || 0) -
                (parseFloat(this.label.container.css("padding-left")) || 0);

            var labelOuterWidth = 0,
                numberOfRows = 0,
                currentRowWidth = 0,
                isRowAdded = false,
                labelOuterHeight = 0;

            this.label.container
                .find("." + this.options.selector.label)
                .filter(function (i, v) {
                    if (i === 0) {
                        labelOuterHeight =
                            $(v)[0].getBoundingClientRect().height +
                            parseFloat($(v).css("margin-bottom") || 0);
                    }

                    // labelOuterWidth = Math.round($(v)[0].getBoundingClientRect().width * 100) / 100 + parseFloat($(v).css('margin-right'));
                    labelOuterWidth =
                        $(v)[0].getBoundingClientRect().width +
                        parseFloat($(v).css("margin-right") || 0);

                    if (
                        currentRowWidth + labelOuterWidth > nodeWidth * 0.7 && !isRowAdded
                    ) {
                        numberOfRows++;
                        isRowAdded = true;
                    }

                    if (currentRowWidth + labelOuterWidth < nodeWidth) {
                        currentRowWidth += labelOuterWidth;
                    } else {
                        isRowAdded = false;
                        currentRowWidth = labelOuterWidth;
                    }
                });

            var paddingLeft =
                parseFloat(this.label.container.data("padding-left") || 0) +
                (isRowAdded ? 0 : currentRowWidth);
            var paddingTop =
                numberOfRows * labelOuterHeight +
                parseFloat(this.label.container.data("padding-top") || 0);

            this.container
                .find("." + this.options.selector.query)
                .find("input, textarea, [contenteditable], .typeahead__hint")
                .css({
                    paddingLeft: paddingLeft,
                    paddingTop: paddingTop
                });
        },

        showLayout: function () {
            if (this.container.hasClass("result") ||
                (
                    !this.result.length && !this.options.emptyTemplate && !this.options.backdropOnFocus
                )
            ) return;

            _addHtmlListeners.call(this);

            this.container.addClass(
                [
                    this.result.length ||
                    (this.searchGroups.length &&
                        this.options.emptyTemplate &&
                        this.query.length)
                        ? "result "
                        : "",
                    this.options.hint && this.searchGroups.length ? "hint" : "",
                    this.options.backdrop || this.options.backdropOnFocus
                        ? "backdrop"
                        : ""
                ].join(" ")
            );

            this.helper.executeCallback.call(
                this,
                this.options.callback.onShowLayout,
                [this.node, this.query]
            );

            function _addHtmlListeners() {
                var scope = this;

                // If Typeahead is blured by pressing the "Tab" Key, hide the results
                $("html")
                    .off("keydown" + this.namespace)
                    .on("keydown" + this.namespace, function (e) {
                        if (!e.keyCode || e.keyCode !== 9) return;
                        setTimeout(function () {
                            if (
                                !$(":focus")
                                    .closest(scope.container)
                                    .find(scope.node)[0]
                            ) {
                                scope.hideLayout();
                            }
                        }, 0);
                    });

                // If Typeahead is blured by clicking outside, hide the results
                $("html")
                    .off("click" + this.namespace + " touchend" + this.namespace)
                    .on("click" + this.namespace + " touchend" + this.namespace, function (e) {
                        if ($(e.target).closest(scope.container)[0] ||
                            $(e.target).closest('.' + scope.options.selector.item)[0] ||
                            e.target.className === scope.options.selector.cancelButton ||
                            scope.hasDragged
                        ) return;

                        scope.hideLayout();
                    });
            }
        },

        hideLayout: function () {
            // Means the container is already hidden
            if (!this.container.hasClass("result") && !this.container.hasClass("backdrop")) return;

            this.container.removeClass(
                "result hint filter" +
                (this.options.backdropOnFocus && $(this.node).is(":focus")
                    ? ""
                    : " backdrop")
            );

            if (this.options.backdropOnFocus && this.container.hasClass("backdrop"))
                return;

            // Make sure the event HTML gets cleared
            $("html").off(this.namespace);

            this.helper.executeCallback.call(
                this,
                this.options.callback.onHideLayout,
                [this.node, this.query]
            );
        },

        resetLayout: function () {
            this.result = [];
            this.tmpResult = {};
            this.groups = [];
            this.resultCount = 0;
            this.resultCountPerGroup = {};
            this.resultItemCount = 0;
            this.resultHtml = null;

            if (this.options.hint && this.hint.container) {
                this.hint.container.val("");
                if (this.isContentEditable) {
                    this.hint.container.text("");
                }
            }
        },

        resetInput: function () {
            this.node.val("");
            if (this.isContentEditable) {
                this.node.text("");
            }
            this.item = null;
            this.query = "";
            this.rawQuery = "";
        },

        buildCancelButtonLayout: function () {
            if (!this.options.cancelButton) return;
            var scope = this;

            $("<span/>", {
                class: this.options.selector.cancelButton,
                html: "×",
                mousedown: function (e) {
                    // Don't blur the input
                    e.stopImmediatePropagation();
                    e.preventDefault();

                    scope.resetInput();
                    scope.node.trigger("input" + scope.namespace, [e]);
                }
            }).insertBefore(this.node);
        },

        toggleCancelButtonVisibility: function () {
            this.container.toggleClass("cancel", !!this.query.length);
        },

        __construct: function () {
            this.extendOptions();

            if (!this.unifySourceFormat()) {
                return;
            }

            this.dynamicFilter.init.apply(this);

            this.init();
            this.buildDropdownLayout();
            this.buildDropdownItemLayout("static");

            this.buildMultiselectLayout();

            this.delegateEvents();
            this.buildCancelButtonLayout();

            this.helper.executeCallback.call(this, this.options.callback.onReady, [
                this.node
            ]);
        },

        helper: {
            isEmpty: function (obj) {
                for (var prop in obj) {
                    if (obj.hasOwnProperty(prop)) return false;
                }

                return true;
            },

            /**
             * Remove every accent(s) from a string
             *
             * @param {String} string
             * @returns {*}
             */
            removeAccent: function (string) {
                if (typeof string !== "string") {
                    return;
                }

                var accent = _accent;

                if (typeof this.options.accent === "object") {
                    accent = this.options.accent;
                }

                string = string
                    .toLowerCase()
                    .replace(new RegExp("[" + accent.from + "]", "g"), function (match) {
                        return accent.to[accent.from.indexOf(match)];
                    });

                return string;
            },

            /**
             * Creates a valid url from string
             *
             * @param {String} string
             * @returns {string}
             */
            slugify: function (string) {
                string = String(string);

                if (string !== "") {
                    string = this.helper.removeAccent.call(this, string);
                    string = string
                        .replace(/[^-a-z0-9]+/g, "-")
                        .replace(/-+/g, "-")
                        .replace(/^-|-$/g, "");
                }

                return string;
            },

            /**
             * Sort list of object by key
             *
             * @param {String|Array} field
             * @param {Boolean} reverse
             * @param {Function} primer
             * @returns {Function}
             */
            sort: function (field, reverse, primer) {
                var key = function (x) {
                    for (var i = 0, ii = field.length; i < ii; i++) {
                        if (typeof x[field[i]] !== "undefined") {
                            return primer(x[field[i]]);
                        }
                    }
                    return x;
                };

                reverse = [-1, 1][+!!reverse];

                return function (a, b) {
                    return (a = key(a)), (b = key(b)), reverse * ((a > b) - (b > a));
                };
            },

            /**
             * Replace a string from-to index
             *
             * @param {String} string The complete string to replace into
             * @param {Number} offset The cursor position to start replacing from
             * @param {Number} length The length of the replacing string
             * @param {String} replace The replacing string
             * @returns {String}
             */
            replaceAt: function (string, offset, length, replace) {
                return (
                    string.substring(0, offset) +
                    replace +
                    string.substring(offset + length)
                );
            },

            /**
             * Adds <strong> html around a matched string
             *
             * @param {String} string The complete string to match from
             * @param {String} key
             * @param {Boolean} [accents]
             * @returns {*}
             */
            highlight: function (string, keys, accents) {
                string = String(string);

                var searchString =
                    (accents && this.helper.removeAccent.call(this, string)) || string,
                    matches = [];

                if (!Array.isArray(keys)) {
                    keys = [keys];
                }

                keys.sort(function (a, b) {
                    return b.length - a.length;
                });

                // Make sure the '|' join will be safe!
                for (var i = keys.length - 1; i >= 0; i--) {
                    if (keys[i].trim() === "") {
                        keys.splice(i, 1);
                        continue;
                    }
                    keys[i] = keys[i].replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
                }

                searchString.replace(
                    new RegExp("(?:" + keys.join("|") + ")(?!([^<]+)?>)", "gi"),
                    function (match, index, offset) {
                        matches.push({
                            offset: offset,
                            length: match.length
                        });
                    }
                );

                for (var i = matches.length - 1; i >= 0; i--) {
                    string = this.helper.replaceAt(
                        string,
                        matches[i].offset,
                        matches[i].length,
                        "<strong>" +
                        string.substr(matches[i].offset, matches[i].length) +
                        "</strong>"
                    );
                }

                return string;
            },

            /**
             * Get caret position, used for right arrow navigation
             * when hint option is enabled
             * @param {Node} element
             * @returns {Number} Caret position
             */
            getCaret: function (element) {
                var caretPos = 0;

                if (element.selectionStart) {
                    // Input & Textarea
                    return element.selectionStart;
                } else if (document.selection) {
                    var r = document.selection.createRange();
                    if (r === null) {
                        return caretPos;
                    }

                    var re = element.createTextRange(),
                        rc = re.duplicate();
                    re.moveToBookmark(r.getBookmark());
                    rc.setEndPoint("EndToStart", re);

                    caretPos = rc.text.length;
                } else if (window.getSelection) {
                    // Contenteditable
                    var sel = window.getSelection();
                    if (sel.rangeCount) {
                        var range = sel.getRangeAt(0);
                        if (range.commonAncestorContainer.parentNode == element) {
                            caretPos = range.endOffset;
                        }
                    }
                }
                return caretPos;
            },

            /**
             * For [contenteditable] typeahead node only,
             * when an item is clicked set the cursor at the end
             * @param {Node} element
             */
            setCaretAtEnd: function (element) {
                if (
                    typeof window.getSelection !== "undefined" &&
                    typeof document.createRange !== "undefined"
                ) {
                    var range = document.createRange();
                    range.selectNodeContents(element);
                    range.collapse(false);
                    var sel = window.getSelection();
                    sel.removeAllRanges();
                    sel.addRange(range);
                } else if (typeof document.body.createTextRange !== "undefined") {
                    var textRange = document.body.createTextRange();
                    textRange.moveToElementText(element);
                    textRange.collapse(false);
                    textRange.select();
                }
            },

            /**
             * Clean strings from possible XSS (script and iframe tags)
             * @param string
             * @returns {string}
             */
            cleanStringFromScript: function (string) {
                return (
                    (typeof string === "string" &&
                        string.replace(/<\/?(?:script|iframe)\b[^>]*>/gm, "")) ||
                    string
                );
            },

            /**
             * Executes an anonymous function or a string reached from the window scope.
             *
             * @example
             * Note: These examples works with every configuration callbacks
             *
             * // An anonymous function inside the "onInit" option
             * onInit: function() { console.log(':D'); };
             *
             * // myFunction() located on window.coucou scope
             * onInit: 'window.coucou.myFunction'
             *
             * // myFunction(a,b) located on window.coucou scope passing 2 parameters
             * onInit: ['window.coucou.myFunction', [':D', ':)']];
             *
             * // Anonymous function to execute a local function
             * onInit: function () { myFunction(':D'); }
             *
             * @param {String|Array} callback The function to be called
             * @param {Array} [extraParams] In some cases the function can be called with Extra parameters (onError)
             * @returns {*}
             */
            executeCallback: function (callback, extraParams) {
                if (!callback) {
                    return;
                }

                var _callback;

                if (typeof callback === "function") {
                    _callback = callback;
                } else if (typeof callback === "string" || Array.isArray(callback)) {
                    if (typeof callback === "string") {
                        callback = [callback, []];
                    }

                    _callback = this.helper.namespace.call(this, callback[0], window);

                    if (typeof _callback !== "function") {
                        // {debug}
                        if (this.options.debug) {
                            _debug.log({
                                node: this.selector,
                                function: "executeCallback()",
                                arguments: JSON.stringify(callback),
                                message: 'WARNING - Invalid callback function"'
                            });

                            _debug.print();
                        }
                        // {/debug}
                        return;
                    }
                }

                return _callback.apply(
                    this,
                    (callback[1] || []).concat(extraParams ? extraParams : [])
                );
            },

            namespace: function (string, object, method, defaultValue) {
                if (typeof string !== "string" || string === "") {
                    // {debug}
                    if (this.options.debug) {
                        _debug.log({
                            node: this.options.input || this.selector,
                            function: "helper.namespace()",
                            arguments: string,
                            message: 'ERROR - Missing string"'
                        });

                        _debug.print();
                    }
                    // {/debug}
                    return false;
                }

                var value = typeof defaultValue !== "undefined"
                    ? defaultValue
                    : undefined;

                // Exit before looping if the string doesn't contain an object reference
                if (!~string.indexOf(".")) {
                    return object[string] || value;
                }

                var parts = string.split("."),
                    parent = object || window,
                    method = method || "get",
                    currentPart = "";

                for (var i = 0, length = parts.length; i < length; i++) {
                    currentPart = parts[i];

                    if (typeof parent[currentPart] === "undefined") {
                        if (~["get", "delete"].indexOf(method)) {
                            return typeof defaultValue !== "undefined"
                                ? defaultValue
                                : undefined;
                        }
                        parent[currentPart] = {};
                    }

                    if (~["set", "create", "delete"].indexOf(method)) {
                        if (i === length - 1) {
                            if (method === "set" || method === "create") {
                                parent[currentPart] = value;
                            } else {
                                delete parent[currentPart];
                                return true;
                            }
                        }
                    }

                    parent = parent[currentPart];
                }
                return parent;
            },

            typeWatch: (function () {
                var timer = 0;
                return function (callback, ms) {
                    clearTimeout(timer);
                    timer = setTimeout(callback, ms);
                };
            })()
        }
    };

    /**
     * @public
     * Implement Typeahead on the selected input node.
     *
     * @param {Object} options
     * @return {Object} Modified DOM element
     */
    $.fn.typeahead = $.typeahead = function (options) {
        return _api.typeahead(this, options);
    };

    /**
     * @private
     * API to handles Typeahead methods via jQuery.
     */
    var _api = {
        /**
         * Enable Typeahead
         *
         * @param {Object} node
         * @param {Object} options
         * @returns {*}
         */
        typeahead: function (node, options) {
            if (!options || !options.source || typeof options.source !== "object") {
                // {debug}
                _debug.log({
                    node: node.selector || (options && options.input),
                    function: "$.typeahead()",
                    arguments: JSON.stringify((options && options.source) || ""),
                    message: 'Undefined "options" or "options.source" or invalid source type - Typeahead dropped'
                });

                _debug.print();
                // {/debug}

                return;
            }

            if (typeof node === "function") {
                if (!options.input) {
                    // {debug}
                    _debug.log({
                        node: node.selector,
                        function: "$.typeahead()",
                        //'arguments': JSON.stringify(options),
                        message: 'Undefined "options.input" - Typeahead dropped'
                    });

                    _debug.print();
                    // {/debug}

                    return;
                }

                node = $(options.input);
            }
            if (typeof node[0].value === "undefined") {
                node[0].value = node.text();
            }
            if (!node.length) {
                // {debug}
                _debug.log({
                    node: node.selector,
                    function: "$.typeahead()",
                    arguments: JSON.stringify(options.input),
                    message: "Unable to find jQuery input element - Typeahead dropped"
                });

                _debug.print();
                // {/debug}

                return;
            }

            // #270 Forcing node.selector, the property was deleted from jQuery3
            // In case of multiple init, each of the instances needs it's own selector!
            if (node.length === 1) {
                node[0].selector =
                    node.selector || options.input || node[0].nodeName.toLowerCase();

                /*jshint boss:true */
                return (window.Typeahead[node[0].selector] = new Typeahead(node, options));
            } else {
                var instances = {},
                    instanceName;

                for (var i = 0, ii = node.length; i < ii; ++i) {
                    instanceName = node[i].nodeName.toLowerCase();
                    if (typeof instances[instanceName] !== "undefined") {
                        instanceName += i;
                    }
                    node[i].selector = instanceName;

                    window.Typeahead[instanceName] = instances[instanceName] = new Typeahead(node.eq(i), options);
                }

                return instances;
            }
        }
    };

    // {debug}
    var _debug = {
        table: {},
        log: function (debugObject) {
            if (!debugObject.message || typeof debugObject.message !== "string") {
                return;
            }

            this.table[debugObject.message] = $.extend(
                {
                    node: "",
                    function: "",
                    arguments: ""
                },
                debugObject
            );
        },
        print: function () {
            if (
                Typeahead.prototype.helper.isEmpty(this.table) || !console || !console.table
            ) {
                return;
            }

            if (console.group !== undefined || console.table !== undefined) {
                console.groupCollapsed("--- jQuery Typeahead Debug ---");
                console.table(this.table);
                console.groupEnd();
            }

            this.table = {};
        }
    };
    _debug.log({
        message: "WARNING - You are using the DEBUG version. Use /dist/jquery.typeahead.min.js in production."
    });

    _debug.print();
    // {/debug}

    // IE8 Shims
    window.console = window.console || {
        log: function () {
        }
    };

    if (!Array.isArray) {
        Array.isArray = function (arg) {
            return Object.prototype.toString.call(arg) === "[object Array]";
        };
    }

    if (!("trim" in String.prototype)) {
        String.prototype.trim = function () {
            return this.replace(/^\s+/, "").replace(/\s+$/, "");
        };
    }
    if (!("indexOf" in Array.prototype)) {
        Array.prototype.indexOf = function (find, i /*opt*/) {
            if (i === undefined) i = 0;
            if (i < 0) i += this.length;
            if (i < 0) i = 0;
            for (var n = this.length; i < n; i++)
                if (i in this && this[i] === find) return i;
            return -1;
        };
    }
    if (!Object.keys) {
        Object.keys = function (obj) {
            var keys = [],
                k;
            for (k in obj) {
                if (Object.prototype.hasOwnProperty.call(obj, k)) {
                    keys.push(k);
                }
            }
            return keys;
        };
    }

    return Typeahead;
});
