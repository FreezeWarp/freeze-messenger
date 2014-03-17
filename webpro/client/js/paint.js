/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */

//$q($l('errorQuitMessage', 'errorGenericQuit'));
function $q(message, error) {
  $('body').replaceWith(message);
  throw new Error(error ? error : message); 
}

/** Returns a localised string.
 * Note that this currently is using "window.phrase", as that is how I did things prior to creating this function, but I will likely change this later.
 * (Also, this framework is decidedly original and custom-made. That said, if you like it, you are free to take it, assuming you follow WebPro's GPL licensing guidelines.)
 * 
 * @param stringName - The name of the string we will return.
 * @param substitutions - Strings can contain simple substitutions of their own. Strange though this is, we feel it is better than using a template when no HTML is involved.
 * @param extra - Additional replacements values, in addition to those stored in window.phrases.
 * 
 * @todo No optimisation has yet been made. We will need to do at least some profiling later on.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function $l(stringName, substitutions, extra) {
  var phrase = false,
    stringParsed = '',
    eachBreak = false;
  
  // We start be breaking up the stringName (which needs to be seperated with periods), to use the [] format. This is mainly neccissary because of integer indexes (JS does not support "a.b.1"), but these indexes are better anyway for arrays.
  stringNameParts = stringName.split('.');
  
  $.each(stringNameParts, function(index, value) {
    stringParsed += ('[\'' + value + '\']');
    
    if (undefined === eval("window.phrases" + stringParsed + " || extra" + stringParsed)) {
      eachBreak = true;
      return false;
    }
  });

  if ((eachBreak === false) && (phrase = eval("window.phrases" + stringParsed + " || extra" + stringParsed))) {
    if (substitutions) {
      $.each(substitutions, function(index, value) {
        phrase = phrase.replace('{{{{' + index + '}}}}', value);
      });
    }
    
    return phrase;
  }
  else {
    console.log('Missing phrase "' + stringName + '"');
    return '~~' + stringName;
  }
}

/** Returns a formatted template.
 * 
 * @param stringName - The name of the template we will return.
 * @param substitutions - A list of "additional" template substitutions. Those included in the language.json files are automatically included.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function $t(templateName, substitutions) {
  if (undefined === eval("window.templates." + templateName)) {
    $q('Template Error: ' + templateName + ' not found.');
    
    return false;
  }
  else {
    templateData = window.templates[templateName];
    
    return templateData = templateData.replace(/\{\{\{\{([a-zA-Z0-9\.]+)\}\}\}\}/g, function($1, $2) {
      return $l($2, false, substitutions);
    });
  }
}

$.when(
  $.ajax({
    url: 'client/data/config.json',
    dataType: 'json',
    success: function(data) {
      window.fim_config = data;
    }
  }),
  $.ajax({
    url: 'client/data/language_enGB.json',
    dataType: 'json',
    success: function(data) {
      window.phrases = data;
    }
  }),
  $.ajax({
    url: 'client/data/templates.json',
    dataType: 'json',
    success: function(data) {
      window.templates = data;
    }
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-api.js',
    dataType: 'script'
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-standard.js',
    dataType: 'script'
  }),
  $.ajax({
    url: 'client/js/fim-dev/fim-popup.js',
    dataType: 'script'
  })
 ).then(function() {
  $(document).ready(function() {
    $('body').append($t('main'));
    $('body').append($t('contextMenu'));

    $.ajax({
      async: false,
      url: 'client/js/fim-dev/fim-loader.js',
      dataType:'script'
    });
  });

//    $.getScript('client/js/fim-dev/fim-popup.js');
//    $.getScript('client/js/fim-dev/fim-standard.js');
//    $.getScript('client/js/fim-dev/fim-loader.js');

});
