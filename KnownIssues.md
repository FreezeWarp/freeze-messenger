# Platform Issues #
## Windows ##
  * In a number of PHP versions, setting the (unlisted) configuration variable `serverSentTimeLimit` to anything but `0` may cause huge CPU usage in the serverSentEvent script.

## FastCGI ##
  * **default** By default, FastCGI buffers will cause the serverSentEvent script to fail, rendering it useless. Set the configuration variable `serverSentFastCGI` to `true` to fix this.

## nginx ##
  * **default** By default, nginx buffers will cause the serverSentEvent script to fail, rendering it useless. If you are using FastCGI, you may be fine setting the configuration variable `serverSentFastCGI` to `true`, though otherwise the best method is to disable the `gzip` and `proxy_buffering` nginx directives (set them both to `off`).

# Login Managers #
## vBulletin 3 & vBulletin 4 ##
  * Users with non-Latin characters will not be able to login. This is a result of some weird character encoding vBulletin uses (we just use UTF-8...) and on attempt an error will be thrown. In theory changing the vBulletin database to UTF-8, and subsequently replacing all codes in usernames with the proper character would fix it. **Patches Welcome from Contributors!**
  * When defining usergroup formats, the WebPro API assumes a format of `<span style="color: #fff;">`. It will correctly interpret in different areas (notably the BBCode data export) user format start tags that use a span as the root and a CSS color property. Otherwise, this effect will not be achieved. Other supported conversions, to achieve the desired effect, include:
    * `font-weight: bold` = `[B][/B]`
    * `text-decoration: underline` = `[U][/U]`
    * `text-decoration: line-through` = `[S][/S]`

## PHPBB 3 ##

## Vanilla ##

# Web Pro Interface #
## Browser Issues ##
  * Internet Explorer 6 & Internet Explorer 7 do not work, and will not be supported. **Patches Welcome from Contributors _if_ There is No Added Overhead or Code Complexity**
  * Many recent browsers (e.g. IE9, Opera) do not support file uploads. A workaround may have in theory been possible, but we decided against making one. **Patches Welcome if it Uses the iFrame+PHP+cURL method**

## Other Issues ##

# API #

# Web Lite #
  * PHPBB styling integration is non-existent. It may be added in a future version, though no plans currently exist. **Patches Welcome**
  * Mobile platforms are generally buggy. **Patches Welcome**