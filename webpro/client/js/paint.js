/* START WebPro
 * Note that: WebPro is not optimised for large sets of rooms. It can handle around 1,000 "normal" rooms. */

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
            if (false === ($2 in window.phrases)) {
              console.log('Missing phrase "' + $2 + '" in template "' + i + '"');
              return '~~' + $2;
            }
            else {
                return window.phrases[$2];
            }
          }
        );
      }

      window.templates = data;

      $(document).ready(function() {
        $('body').append(window.templates.main);
        $('body').append(window.templates.chatTemplate);
        $('body').append(window.templates.contextMenu);

        $.getScript('client/js/fim-ie.js');
        $.getScript('client/js/fim-dev/fim-popup.js');
        $.getScript('client/js/fim-dev/fim-standard.js');
        $.getScript('client/js/fim-dev/fim-loader.js');
      });
    },
    async: false,
    cache: true
  });
});
