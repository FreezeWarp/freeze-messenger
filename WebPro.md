**Note: Some of the features described are not present in Beta 3, and will need to be found in the nightlies. Also note that Chrome is the only browser accessibility features are tested on initially -- they will later be confirmed to work in all fully supported browsers.**

The WebPro interface was designed as something of a reference spec to the API, as well as a way to test out different emerging HTML5 standards. Support archaic browsers of yesterday is perhaps the very last priority, and as such the browser requirement may be fairly high: IE8 is absolutely required as a base (IE9 or IE10 is highly encouraged), while otherwise the most recent of any other browser is encouraged.

WebPro has some cool features of sorts that are documented below, largely filed under "misc":


# Accessibility #
## Access Keys ##
  * a - Archive
  * r - Room List
  * c - Create Room
  * p - Private Room
  * o - Active Users
  * t - Stats
  * l - My Uploads
  * s - Settings
  * k - My Kicks
  * x - Logout
  * i - Login

  * e - Edit Room
  * m - Manage Kicked Users
  * b - Kick a User
  * n - AdminCP

  * u - Upload File
  * ? - Help

  * v - Key Menu
  * w - Mod Menu
  * x - Room Menu
  * y - AUser Menu
  * z - Copyright Menu


## Standard Keyboard Controls ##
  * Tab navigation can be used throughout (i.e. dialogs, tabbed dialogs, context menus, and the main layout).
  * Context Menus can be scrolled via Up+Down, items selected with Enter, and the menu closed with escape.
  * Context Menus can be accessed via the Menu Key in both the normal mode and the "Disable Right Click" mode, as well as via the Enter key in "Disabled Right Click" mode.
  * The text entry box can be focused with "Space" while the first item in the message list can be focused using either "`" or "1" **without** any modifier key (i.e. alt, ctrl, shift, meta).
    * Note: These both require that no textarea, input field, or button is currently focused.
  * Messages and usernames can be navigated using the arrow keys. Left and right will move between the username and message, while up and down will scroll through the messages/users.

## Alternative Right-Click Mode ##
The alternative right-click mode allows items to be selected by left-clicking them instead of right clicking them. You can also use "enter" to show the menu in these circumstances.

The alternate right-click mode can be useful, but also has an number of drawbacks. Certainly, you are encouraged to use it if:
  * You are not able to use the right-click, due to browser or hardware issues.
  * You wish to have the right-click for traditional purposes (e.g. "Open Link in New Tab", "Save Page As").
  * Your device is without a right-click or is touch-based (e.g. Smartphones, Tablet Computers).

However, it does come with drawbacks:
  * Images, links, and so-on will not click. However, the content can be accessed by selecting the appropriate context-menu item in both situations.
  * The context menu of flash elements will fail to activate (or, at least, this is the expected behaviour). To workaround this you could select the menu using the keyboard.
  * In general, the workflow is less consistent.

## Translations ##
All WebPro phrases should be stored in a JSON file that contains all phrases for the software (these could theoretically be loaded on-demand, but that would be stupid).  These should be parsed with a `$l()` function call.

## Templates ##
All HTML code blocks should be stored in some template, including data that is dynamically appended. This is still a WIP.