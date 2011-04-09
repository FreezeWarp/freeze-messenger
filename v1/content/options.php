<script type="text/javascript" src="/client/jscolor/jscolor.js"></script>
<?php
$phase = $_GET['phase'];
if (!$phase) $phase = '1'; // Default to phase 1.

$fontData = sqlArr("SELECT * FROM {$sqlPrefix}fonts ORDER BY category, name",'id');
foreach($fontData AS $font) {
  $fontBox .= "<option value=\"$font[id]\" style=\"font-family: $font[data]\">$font[name]</option>";
}

if ($phase == '1') {
  echo container('<h3>User Options</h3>','<form action="/index.php?action=options&phase=2" method="post">
  <label for="reverse">Show Old Posts First:</label> <input type="checkbox" name="reverse" id="reverse" value="true"' . ($user['settings'] & 32 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will show newer posts at the bottom instead of the top, as is common with many instant messenging programs.</span></small><br /><br />

  <label for="disableding">Disable Ding:</label> <input type="checkbox" name="disableding" id="disableding" value="true" ' . ($user['settings'] & 128 ? ' checked="checked"' : '') . '" /><br />
  <small><span style="margin-left: 10px;">If checked, the ding will be completely disabled in the chat.</span></small><br /><br />

  <label for="mature">Disable Parental Controls:</label> <input type="checkbox" name="mature" id="mature" value="true"' . ($user['settings'] & 64 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">By default parental controls are enabled that help keep younger users safe. Check this to disable these features, however we take no responsibility for any reprecussions.</span></small><br /><br />

  <label for="disableFormatting">Disable Formatting:</label> <input type="checkbox" name="disableFormatting" id="disableFormatting" value="true"' . ($user['settings'] & 512 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will disable default formatting some users use on their messages.</span></small><br /><br />

  <label for="disableVideo">Disable Video Embeds:</label> <input type="checkbox" name="disableVideo" id="disableVideo" value="true"' . ($user['settings'] & 1024 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will disable video embeds in rooms that allow them, replaced with a "click to activate" link.</span></small><br /><br />

<!--  <label for="disableImage">Disable Image Embeds:</label> <input type="checkbox" name="disableImage" id="disableImage" value="true"' . ($user['settings'] & 2048 ? ' checked="checked"' : '') . ' /><br />
  <small><span style="margin-left: 10px;">This will disable image embeds in rooms that allow them, replaced with a link or alternate text.</span></small><br /><br />-->

  ' . ($enableDF ? '
  Default Formatting:<br />

  ' . ($enableDF['font'] ? '
  <select name="defaultFace" id="defaultFace" onchange="var fontFace = $(\'#defaultFace option:selected\').val(); $(\'#fontPreview\').css(\'font-family\',fontFace);" style="margin-left: 10px;">
    <option value="">Font</option>
    <optgroup label="Serif">
      <option value="serif" style="font-family: serif">Generic</option>
      <option value="\'Times New Roman\', serif" style="font-family: \'Times New Roman\', serif;">Times New Roman</option>
      <option value="Garmond, serif" style="font-family: Garmond, serif;">Garmond</option>
      <option value="Georgia, serif" style="font-family: Georgia, serif;">Georgia</option>
      <option value="\'Bauhaus 93\', serif" style="font-family: \'Bauhaus 93\', serif;">Bauhaus 93</option>
    </optgroup>
    <optgroup label="Sans-Serif">
      <option value="sans-serif" style="font-family: sans-serif;">Generic</option>
      <option value="\'Century Gothic\', sans-serif" style="font-family: \'Century Gothic\', sans-serif;">Century Gothic</option>
      <option value="Trebuchet, sans-serif" style="font-family: Trebuchet, sans-serif;">Trebuchet</option>
      <option value="Arial, sans-serif" style="font-family: Arial, Helvetica, sans-serif;">Arial</option>
      <option value="Verdana, sans-serif" style="font-family: Verdana, sans-serif;">Verdana</option>
      <option value="Tahoma, sans-serif" style="font-family: Tahoma, sans-serif;">Tahoma</option>
    </optgroup>
    <optgroup label="Cursive">
      <option value="cursive" style="font-family: cursive;">Generic</option>
    </optgroup>
    <optgroup label="Fantasy">
      <option value="fantasy" style="font-family: fantasy;">Generic</option>
    </optgroup>
    <optgroup label="Monospace">
      <option value="monospace" style="font-family: monospace;">Generic</option>
      <option value="Courier, monospace" style="font-family: Courier, monospace;">Courier</option>
      <option value="Lucida Console, monospace" style="font-family: Lucida Console, monospace;">Lucida Console</option>
      <option value="\'Courier New\', monospace" style="font-family: \'Courier New\', monospace">MS Courier New</option>
    </optgorup>
  </select>' : '') . ($enableDF['colour'] ? '

  <input class="color {pickerMode:\'HVS\', adjust:false}" style="width: 100px;" id="defaultColour" name="defaultColour" onchange="$(\'#fontPreview\').css(\'color\',\'#\' + $(\'#defaultColour\').val());" placeholder="Colour" ' . ($user['defaultColour'] ? 'value="' . rgb2html($user['defaultColour']) . '"' : '') . ' />'  : '') . ($enableDF['highlight'] ? '

  <input class="color {pickerMode:\'HVS\', adjust:false}" style="width: 100px;" id="defaultHighlight" name="defaultHighlight" onchange="$(\'#fontPreview\').css(\'background-color\',\'#\' + $(\'#defaultHighlight\').val());" placeholder="Highlight" ' . ($user['defaultHighlight'] ? 'value="' . rgb2html($user['defaultHighlight']) . '"' : '') . ' />' : '') . ($enableDF['general'] ? 

  '<label for="defaultBold">Bold</label><input type="checkbox" name="defaultBold" id="defaultBold" onchange="if ($(this).is(\':checked\')) { $(\'#fontPreview\').css(\'font-weight\',\'bold\'); } else { $(\'#fontPreview\').css(\'font-weight\',\'normal\'); }" value="true"' . ($user['defaultFormatting'] & 256 ? ' checked="checked"' : '') . ' />

  <label for="defaultItalics">Italics</label><input type="checkbox" name="defaultItalics" id="defaultItalics" value="true"' . ($user['defaultFormatting'] & 256 ? ' checked="checked"' : '') . ' onchange="if ($(this).is(\':checked\')) { $(\'#fontPreview\').css(\'font-style\',\'italic\'); } else { $(\'#fontPreview\').css(\'font-style\',\'normal\'); }" /><br />' : '') . '
  <small><span style="margin-left: 10px;" id="fontPreview">Here\'s a preview!</span></small><br /><br />' : '') . '

  <label for="defaultRoom">Default Room:</label>   <select name="defaultRoom">
  ' . mysqlReadThrough(mysqlQuery('SELECT * FROM ' . $sqlPrefix . 'rooms WHERE options & 1 ORDER BY id'),'<option value="$id"{{$id == ' . $user['defaultRoom']. '}}{{selected="selected"}{}}>$name</option>') . '
  </select><br />
  <small><span style="margin-left: 10px;">This changes what room defaults when you first visit VRIM. For now, only official rooms can be selected.</span></small><br /><br />

  <input type="submit" value="Save Settings" /><input type="reset" value="Reset" /><br /><br />

<!--  Dummy Font Selector: <select>' . $fontBox . '</select>-->
</form>

<script type="text/javascript">
if ($(\'#defaultColour\').val()) { $(\'#fontPreview\').css(\'color\',$(\'#defaultColour\').val()); }
if ($(\'#defaultHighlight\').val()) { $(\'#fontPreview\').css(\'background-color\',$(\'#defaultHighlight\').val()); }

if ($(\'#defaultItalics\').is(\':checked\')) { $(\'#fontPreview\').css(\'font-style\',\'italic\'); }
else { $(\'#fontPreview\').css(\'font-style\',\'normal\'); }

if ($(\'#defaultBold\').is(\':checked\')) { $(\'#fontPreview\').css(\'font-weight\',\'bold\'); }
else { $(\'#fontPreview\').css(\'font-style\',\'normal\'); }
</script>');
}
elseif ($phase == '2') {
  $reverse = ($_POST['reverse'] ? true : false);
  $mature = ($_POST['mature'] ? true : false);
  $disableFormatting = ($_POST['disableFormatting'] ? true : false);
  $disableVideo = ($_POST['disableVideo'] ? true : false);
  $disableImage = ($_POST['disableImage'] ? true : false);
  $disableDing = ($_POST['disableding'] ? true : false);

  $settings = ($user['settings'] & 1) + ($user['settings'] & 2) + ($user['settings'] & 4) + ($user['settings'] & 8) + ($user['settings'] & 16) + ($reverse ? 32 : 0) + ($mature ? 64 : 0) + ($disableDing ? 128 : 0) + ($disableFormatting ? 512 : 0) + ($disableVideo ? 1024 : 0) + ($disableImage ? 2048 : 0);

  if ($enableDF['font']) {
    if (in_array($_POST['defaultFace'],array('','serif','\'Century Gothic\', serif','\'Times New Roman\', serif','Garmond, serif','Georgia, serif','\'Bauhaus 93\', serif','sans-serif','Trebuchet, sans-serif','Arial, sans-serif','Verdana, sans-serif','Tahoma, sans-serif','cursive','fantasy','monospace','Courier, monospace','Lucida Console, monospace','\'Courier New\', monospace'))) {
      $fontface = mysqlEscape($_POST['defaultFace']);
    }
    else {
      trigger_error('Unknown Font "' . $_POST['defaultFace'] . '"',E_USER_WARNING);
    }
  }

  if ($enableDF['colour']) {
    if (preg_match('/(0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F){3,6}/',$_POST['defaultColour'])) {
      $colour = mysqlEscape(implode(',',html2rgb($_POST['defaultColour'])));
    }
    else {
      trigger_error('Unknown Color "' . $_POST['defaultColour'] . '"',E_USER_WARNING);
    }
  }

  if ($enableDF['highlight']) {
    if (preg_match('/(0|1|2|3|4|5|6|7|8|9|A|B|C|D|E|F){3,6}/',$_POST['defaultHighlight']) || $_POST['defaultHighlight'] == 'transparent') {
      $highlight = mysqlEscape(implode(',',html2rgb($_POST['defaultHighlight'])));
    }
    else {
      trigger_error('Unknown Highlight "' . $_POST['defaultHighlight'] . '"',E_USER_WARNING);
    }
  }

  if ($enableDF['general']) {
    $defaultFormatting = ($_POST['defaultBold'] ? 256 : 0) + ($_POST['defaultItalics'] ? 512 : 0);
  }

  if ($_POST['defaultRoom']) $defaultRoom = $_POST['defaultRoom'];
  else $defaultRoom = 1;

  mysqlQuery("UPDATE {$sqlPrefix}users SET settings = $settings, defaultFormatting = $defaultFormatting, defaultHighlight = \"$highlight\", defaultColour = \"$colour\", defaultFontface = \"$fontface\", defaultRoom = $defaultRoom WHERE userid = $user[userid]");

  echo container('Settings Updated','Your settings have been updated successfully.' . button('Return','/index.php'));
}
?>