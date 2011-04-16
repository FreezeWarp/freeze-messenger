
    <!-- END content -->
  </div>
<?php if ($_GET['popup'] == false) { ?>
  <div id="footer">
    <div id="leftFooter">
<?php if (($mode == 'normal' || $mode == 'simple') && ($_GET['popup'] == false)) { ?>
<script type="text/javascript"><!--
google_ad_client = "ca-pub-7506451009235269";
/* 468x60, created 7/16/10 (VictoryBattles) */
google_ad_slot = "5433269492";
google_ad_width = 468;
google_ad_height = 60;
//-->
</script>
<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
<?php } ?>
    </div>

    <div id="rightFooter">
      <div style="vertical-align: middle;">
        <span class="pseudolink" onclick="alert(&quot;VRIM Backend and API © 2010-2011 Joseph T. Parsons. Some Rights Reserved.\n\nVRIM Internet Client © 2010-2011 Joseph T. Parsons. Some Rights Reserved.\n\nVRIM Windows® Cleint © 2010-2011 Scott Cheney. Some Rights Reserved.\n\n\Source Code from Victory Road's VictoryBattles © 2009-2011 Joseph T. Parsons. Some Rights Reserved\n\nSource Code from the Fliler Project © 2008-2011 Joseph T. Parsons and Licensed Under GPLv3.\n\njQuery © 2010 The jQuery Team.\n\njQuery Plugins © Their Respective Owners.&quot;);">© Joseph T. Parsons</span><br />
        <select onchange="var date = new Date(); date.setTime(date.getTime() + (1000 * 60 * 60 * 24 * 365)); document.cookie = 'vrim-styleid=' + this.options[this.selectedIndex].value + '; expires=' + date.toGMTString() + '; path=/; domain=.victoryroad.net'; location.reload(true);" name="styleid" id="styleid">
          <option value="">Select a Skin</option>
          <option value="20">Dark Illusionist</option>
          <option value="19">Quilava's Inferno</option>
          <option value="21">VictoryBattles</option>
          <option value="12">Floatzel Resort</option>
          <option value="9">Winter Nights</option>
          <option value="2">Fblue</option>
          <option value="8">Diamond Dialga</option>
          <option value="4">Steel Pokémon Pride</option>
          <option value="10">Pokémon Christmas</option>
          <option value="11">Bottom of the Sea</option>
          <option value="1">vBulletin Default Style</option>
        </select>
      </div>
    </div>
  </div>
</div>
<?php } ?>

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-20772732-1']);
  _gaq.push(['_trackPageview']);
<?php if ($chat && $room['id']) { echo "
  _gaq.push(['_setCustomVar',
      1,
      'room',
      '$room[id]',
      3
   ]);"; } ?>

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>
</body>
</html>