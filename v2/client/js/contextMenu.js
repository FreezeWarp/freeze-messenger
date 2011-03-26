if(jQuery)( function() {
$.extend($.fn, {
contextMenu: function(o, callback) {
if( o.menu == undefined ) return false;
if( o.inSpeed == undefined ) o.inSpeed = 150;
if( o.outSpeed == undefined ) o.outSpeed = 75;
if( o.inSpeed == 0 ) o.inSpeed = -1;
if( o.outSpeed == 0 ) o.outSpeed = -1;
$(this).each( function() {
var el = $(this);
var offset = $(el).offset();
$('#' + o.menu).addClass('contextMenu');
$(this).mousedown( function(e) {
var evt = e;
evt.stopPropagation();
$(this).mouseup( function(e) {
e.stopPropagation();
var srcElement = $(this);
$(this).unbind('mouseup');
if( evt.button == 2 ) {
$(".contextMenu").hide();
var menu = $('#' + o.menu);
if( $(el).hasClass('disabled') ) return false;
var d = {}, x, y;
if( self.innerHeight ) {
d.pageYOffset = self.pageYOffset;
d.pageXOffset = self.pageXOffset;
d.innerHeight = self.innerHeight;
d.innerWidth = self.innerWidth;
} else if( document.documentElement &&
document.documentElement.clientHeight ) {
d.pageYOffset = document.documentElement.scrollTop;
d.pageXOffset = document.documentElement.scrollLeft;
d.innerHeight = document.documentElement.clientHeight;
d.innerWidth = document.documentElement.clientWidth;
} else if( document.body ) {
d.pageYOffset = document.body.scrollTop;
d.pageXOffset = document.body.scrollLeft;
d.innerHeight = document.body.clientHeight;
d.innerWidth = document.body.clientWidth;
}
(e.pageX) ? x = e.pageX : x = e.clientX + d.scrollLeft;
(e.pageY) ? y = e.pageY : y = e.clientY + d.scrollTop;
$(document).unbind('click');
$(menu).css({ top: y, left: x }).fadeIn(o.inSpeed);
$(menu).find('A').mouseover( function() {
$(menu).find('LI.hover').removeClass('hover');
$(this).parent().addClass('hover');
}).mouseout( function() {
$(menu).find('LI.hover').removeClass('hover');
});

// Keyboard
$(document).keypress( function(e) {
switch( e.keyCode ) {
case 38: // up
if( $(menu).find('LI.hover').size() == 0 ) {
$(menu).find('LI:last').addClass('hover');
} else {
$(menu).find('LI.hover').removeClass('hover').prevAll('LI:not(.disabled)').eq(0).addClass('hover');
if( $(menu).find('LI.hover').size() == 0 ) $(menu).find('LI:last').addClass('hover');
}
break;
case 40: // down
if( $(menu).find('LI.hover').size() == 0 ) {
$(menu).find('LI:first').addClass('hover');
} else {
$(menu).find('LI.hover').removeClass('hover').nextAll('LI:not(.disabled)').eq(0).addClass('hover');
if( $(menu).find('LI.hover').size() == 0 ) $(menu).find('LI:first').addClass('hover');
}
break;
case 13: // enter
$(menu).find('LI.hover A').trigger('click');
break;
case 27: // esc
$(document).trigger('click');
break
}
});

$('#' + o.menu).find('A').unbind('click');
$('#' + o.menu).find('LI:not(.disabled) A').click( function() {
$(document).unbind('click').unbind('keypress');
$(".contextMenu").hide();
if( callback ) callback( $(this).attr('data-action'), $(srcElement), {x: x - offset.left, y: y - offset.top, docX: x, docY: y} );
return false;
});
setTimeout( function() {
$(document).click( function() {
$(document).unbind('click').unbind('keypress');
$(menu).fadeOut(o.outSpeed);
return false;
});
}, 0);
}
});
});
if( $.browser.mozilla ) {
$('#' + o.menu).each( function() { $(this).css({ 'MozUserSelect' : 'none' }); });
} else if( $.browser.msie ) {
$('#' + o.menu).each( function() { $(this).bind('selectstart.disableTextSelect', function() { return false; }); });
} else {
$('#' + o.menu).each(function() { $(this).bind('mousedown.disableTextSelect', function() { return false; }); });
}
$(el).add($('UL.contextMenu')).bind('contextmenu', function() { return false; });
});
return $(this);
},
disableContextMenuItems: function(o) {
if( o == undefined ) {
// Disable all
$(this).find('LI').addClass('disabled');
return( $(this) );
}
$(this).each( function() {
if( o != undefined ) {
var d = o.split(',');
for( var i = 0; i < d.length; i++ ) {
$(this).find('A[href="' + d[i] + '"]').parent().addClass('disabled');
}
}
});
return( $(this) );
},
enableContextMenuItems: function(o) {
if( o == undefined ) {
$(this).find('LI.disabled').removeClass('disabled');
return( $(this) );
}
$(this).each( function() {
if( o != undefined ) {
var d = o.split(',');
for( var i = 0; i < d.length; i++ ) {
$(this).find('A[href="' + d[i] + '"]').parent().removeClass('disabled');

}
}
});
return( $(this) );
},
disableContextMenu: function() {
$(this).each( function() {
$(this).addClass('disabled');
});
return( $(this) );
},
enableContextMenu: function() {
$(this).each( function() {
$(this).removeClass('disabled');
});
return( $(this) );
},
destroyContextMenu: function() {
$(this).each( function() {
$(this).unbind('mousedown').unbind('mouseup');
});
return( $(this) );
}

});
})(jQuery);