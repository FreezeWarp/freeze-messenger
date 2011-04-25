$(document).ready(function() {
  $('table > thead > tr:first-child > td:first-child, table > tr:first-child > td:first-child').css({'-moz-border-radius-topleft' : '10px', 'border-top-left-radius' : '10px'});
  $('table > thead > tr:first-child > td:last-child, table > tr:first-child > td:last-child').css({'-moz-border-radius-topright' : '10px', 'border-top-right-radius' : '10px'});
  $('table > tbody > tr:last-child > td:first-child, table > tr:last-child > td:first-child').css({'-moz-border-radius-bottomleft' : '10px', 'border-bottom-left-radius' : '10px'});
  $('table > tbody > tr:last-child > td:last-child, table > tr:last-child > td:last-child').css({'-moz-border-radius-bottomright' : '10px', 'border-bottom-right-radius' : '10px'});

  // This reduces the border width for proper border radius styling. Its pretty nasty, but works.
  $('table').find('tr:first td').css({'border-top-width' : '0px'});
  $('table tr').find('td:first').css({'border-left-width' : '0px'});
  $('table tr').find('td:last').css({'border-right-width' : '0px'});
  $('table').find('tr:last td').css({'border-bottom-width' : '0px'});

  if (isMobile()) {
    $('.cssdropdown>li.headlink').click(function() {
      $('ul', this).toggle();
    });
  }
  else {
    $('.cssdropdown>li.headlink').hoverIntent(function() {
      $('ul', this).slideDown('medium');
    },
    function() {
      $('ul', this).slideUp('fast');
    });
  }
});

function showAllRooms() {
  $.ajax({
    url: '/ajax/fim-roomList.php?rooms=*',
    timeout: 5000,
    type: 'GET',
    cache: false,
    success: function(html) {
      $('#rooms').html(html);
    },
    error: function() {
      alert('Failed to show all rooms');
    }
  });
}

function isMobile() {
  if (navigator.appVersion.indexOf("iPhone") != -1 || navigator.userAgent.indexOf("iPhone") != -1) return true;
  else if (navigator.appVersion.indexOf("DSi") != -1 || navigator.userAgent.indexOf("DSi") != -1) return true;
  else if (navigator.appVersion.indexOf("PSP") != -1 || navigator.userAgent.indexOf("PSP") != -1) return true;

  return false;
}

/**
* hoverIntent r6 // 2011.02.26 // jQuery 1.5.1+
* <http://cherne.net/brian/resources/jquery.hoverIntent.html>
* 
* @param  f  onMouseOver function || An object with configuration options
* @param  g  onMouseOut function  || Nothing (use configuration options object)
* @author    Brian Cherne brian(at)cherne(dot)net
*/
(function($){$.fn.hoverIntent=function(f,g){var cfg={sensitivity:7,interval:100,timeout:0};cfg=$.extend(cfg,g?{over:f,out:g}:f);var cX,cY,pX,pY;var track=function(ev){cX=ev.pageX;cY=ev.pageY;};var compare=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);if((Math.abs(pX-cX)+Math.abs(pY-cY))<cfg.sensitivity){$(ob).unbind("mousemove",track);ob.hoverIntent_s=1;return cfg.over.apply(ob,[ev]);}else{pX=cX;pY=cY;ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}};var delay=function(ev,ob){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);ob.hoverIntent_s=0;return cfg.out.apply(ob,[ev]);};var handleHover=function(e){var p=(e.type=="mouseover"?e.fromElement:e.toElement)||e.relatedTarget;while(p&&p!=this){try{p=p.parentNode;}catch(e){p=this;}}if(p==this){return false;}var ev=jQuery.extend({},e);var ob=this;if(ob.hoverIntent_t){ob.hoverIntent_t=clearTimeout(ob.hoverIntent_t);}if(e.type=="mouseover"){pX=ev.pageX;pY=ev.pageY;$(ob).bind("mousemove",track);if(ob.hoverIntent_s!=1){ob.hoverIntent_t=setTimeout(function(){compare(ev,ob);},cfg.interval);}}else{$(ob).unbind("mousemove",track);if(ob.hoverIntent_s==1){ob.hoverIntent_t=setTimeout(function(){delay(ev,ob);},cfg.timeout);}}};return this.mouseover(handleHover).mouseout(handleHover);};})(jQuery);