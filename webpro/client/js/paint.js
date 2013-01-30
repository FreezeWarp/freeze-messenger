/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */

/* Returns a localised string.
 * Note that this currently is using "window.phrase", as that is how I did things prior to creating this function, but I will likely change this later.
 * 
 * @param stringName - The name of the string we will return.
 * 
 * @author Jospeph T. Parsons <josephtparsons@gmail.com>
 * @copyright Joseph T. Parsons 2012
 */
function $l(stringName, substitutions) {
  var phrase = false,
    stringParsed = '';
  
  // We start be breaking up the stringName (which needs to be seperated with periods), to use the [] format. This is mainly neccissary because of integer indexes (JS does not support "a.b.1"), but these indexes are better anyway for arrays.
  stringNameParts = stringName.split('.');
  
  $.each(stringNameParts, function(index, value) {
    stringParsed += ('[\'' + value + '\']');
  });
  
  if (phrase = eval("window.phrases" + stringParsed)) {
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
      },
      async: false,
      cache: true,
    })
).then(function() {

  $.ajax({
    url: 'client/data/templates.json',
    dataType: 'json',
    success: function(data) {
      for (i in data) {
        data[i] = data[i].replace(/\{\{\{\{([a-zA-Z0-9]+)\}\}\}\}/g, function($1, $2) {
            return $l($2);
          }
        );
      }

      window.templates = data;

      $(document).ready(function() {
        $('body').append(window.templates.main);
        $('body').append(window.templates.chatTemplate);
        $('body').append(window.templates.contextMenu);

        $.getScript('client/js/fim-dev/fim-popup.js');
        $.getScript('client/js/fim-dev/fim-standard.js');
        $.getScript('client/js/fim-dev/fim-loader.js');
      });
    },
    async: false,
    cache: true
  });
});
