# Formatting Bitfield #
The formatting bitfield is used in several locations; clients should interpret it as following:

  * 256 - Bold
  * 512 - Oblique / Italics
  * 1024 - Underline
  * 2048 - Strikethrough
  * 4096 - Overline

Any entry may be left out/not parsed.

# Message Contexts #
  * image - The message contains a URI suitable for an image container.
  * video - The message contains a URI suitable for an video container.
  * audio - The message contains a URI suitable for an audio container.
  * email - The message is an email address.
  * url - The message is a general URI.
  * html - The message contains a URI suitable for an HTML container.
  * text - The message contains a URI suitable for a text container.
  * archive - The message contains a URI suitable for an archive container (e.g. .zip, .tgz).
  * other - The message contains a URI that can not be classified as above. In this case, clients may wish to link it depending on the file extension (.nes would be a NES ROM, .exe would be a Windows Executable).
  * source - The message contains a URI that must be interpreted based on its domain, for instance a youtube video.

Note that:
  * All URIs should be compatible with the [Data URI scheme](http://en.wikipedia.org/wiki/Data_URI_scheme) if possible, with the exception of the `url` context (because of the message length limit, however, these can never be very large). Additionally, URLs are not escaped for HTML output. Starting in FIMB4, however, they are validated.
  * No context is required for parsing. HTML should largely be ignored in some cases unless it can be properly filtered, for instance.

# Escaping Data #
The following actions should be performed by the client in appropropriate circumstances:
  * Message data should be properly sanitized where valid. HTML, etc. is not escaped by the backend.

# Character Codes #
Various character codes are used by the different API formats:
| **character** | **xml** | **json** |
|:--------------|:--------|:---------|
| new line | `&#xA;` | `\n` |