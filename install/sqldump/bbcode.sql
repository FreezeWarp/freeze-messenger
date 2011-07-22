/* FreezeMessenger Copyright © 2011 Joseph Todd Parsons

 * This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>. */

CREATE TABLE IF NOT EXISTS `{prefix}bbcode` (
  `bbcodeId` int(10) NOT NULL AUTO_INCREMENT COMMENT 'A unique identifier for the word.',
  `bbcodeName` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The name of the BBcode for display purposes.',
  `options` int(4) NOT NULL DEFAULT 1 COMMENT 'A bitfield containing column options:\n\n1 - Enabled (TRUE) | Disabled (FALSE)\n2 - Can be Toggled by Rooms (TRUE) | Cannot be Toggled by Rooms (FALSE)\n4 - Default to On For Rooms (TRUE) | Default to Off for Rooms (FALSE)',
  `searchRegex` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The regex to use for BBcode searching.',
  `replacement` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'The replacement text to be used.',
  PRIMARY KEY (`bbcodeId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}bbcode` (`bbcodeName`, `searchRegex`, `replacement`) VALUES
('Bold', '/\[(b|strong)\](.+?)\[\/(b|strong)\]/is', '<span style="font-weight: bold;">$2</span>'),
('Strikethrough', '/\[(s|strike)\](.+?)\[\/(s|strike)\]/is', '<span style="text-decoration: line-through;">$2</span>'),
('Italics', '/\[(i|em)\](.+?)\[\/(i|em)\]/is', '<span style="font-style: oblique;">$2</span>'),
('Underline', '/\[(u)\](.+?)\[\/(u)\]/is', '<span style="text-decoration: underline;">$2</span>'),
('Link','/\[url\]([^\"\<\>]*?)\[\/url\]/is', '<a href="$1" target="_BLANK">$1</a>'),
('Email', '/\[email\](.*?)\[\/email\]/is', '<a href="mailto:$1">$1</a>'),
('Colour', '/\[(color|colour)=("|)(.*?)("|)\](.*?)\[\/(color|colour)\]/is', '<span style="color: $3;">$5</span>'),
('Highlight', '/\[(hl|highlight|bg|background)=("|)(.*?)("|)\](.*?)\[\/(hl|highlight|bg|background)\]/is', '<span style="background-color: $3;">$5</span>'),
('Image', '/\[img\](.*?)\[\/img\]/is', '<a href="$1" target="_BLANK"><img src="$1" alt="image" class="embedImage" /></a>'),
('Image (with Alternate Text)', '/\[img=("|)(.*?)("|)\](.*?)\[\/img\]/is','<a href="$4" target="_BLANK"><img src="$4" alt="$2" class="embedImage" /></a>'),
('Youtube Embed', '/\[youtube\](.*?)\[\/youtube\]/is', '<object width="420" height="255" wmode="transparent"><param name="movie" value="http://www.youtube.com/v/$1=en&amp;fs=1&amp;rel=0&amp;border=0"></param><param name="allowFullScreen" value="true"></param><embed src="http://www.youtube.com/v/$1&amp;hl=en&amp;fs=1&amp;rel=0&amp;border=0" type="application/x-shockwave-flash" allowfullscreen="true" width="420" height="255" wmode="opaque"></embed></object>');