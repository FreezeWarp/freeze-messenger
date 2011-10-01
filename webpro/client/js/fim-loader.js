$.getJSON('client/data/config.json', function(data) {
  window.fim_config = data;
});

$.getJSON('client/data/templates.json', function(data) {
  window.templates = data;

  $(data.main).appendTo('body');
  $(data.chatTemplate).appendTo('body');
});

$.getJSON('client/data/language_enGB.json', function(data) {
  window.phrases = data;
});