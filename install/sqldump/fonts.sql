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

CREATE TABLE IF NOT EXISTS `{prefix}fonts` (
  `fontId` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(20) NOT NULL,
  `data` varchar(500) NOT NULL,
  `category` varchar(20) NOT NULL,
  PRIMARY KEY (`fontId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}fonts` (`fontId`, `name`, `data`, `category`) VALUES
(1, 'FreeMono', 'FreeMono, TwlgMono, ''Courier New'', Consolas, monospace', 'monospace'),
(2, 'Courier New', '''Courier New'', FreeMono, TwlgMono, Consolas, Courier, monospace', 'monospace'),
(3, 'Consolas', 'Consolas, ''Courier New'', FreeMono, TwlgMono, monospace', 'monospace'),
(4, 'Courier', 'Courier, ''Courier New'', Consolas, monospace', 'monospace'),
(5, 'Liberation Mono', '''Liberation Mono'', monospace', 'monospace'),
(6, 'Lucida Console', '''Lucida Console'', ''Lucida Sans Typewriter'', monospace', 'monospace'),
(7, 'Times New Roman', '''Times New Roman'', ''Liberation Serif'', Georgia, FreeSerif, Cambria, serif', 'serif'),
(8, 'Liberation Serif', '''Liberation Serif'', FreeSerif, ''Times New Roman'', Georgia, Cambria, serif', 'serif'),
(9, 'Georgia', 'Georgia, Cambria, ''Liberation Serif'', ''Times New Roman'', serif', 'serif'),
(10, 'Cambria', 'Cambria, Georgia, ''Liberation Serif'', ''Times New Roman'', serif', 'serif'),
(11, 'Segoe UI', '''Segoe UI'', serif', 'serif'),
(12, 'Garamond', 'Garamond, serif', 'serif'),
(13, 'Century Gothic', '''Century Gothic'', Ubuntu, sans-serif', 'sans-serif'),
(14, 'Trebuchet MS', '''Trebuchet MS'', Arial, Tahoma, Verdana, FreeSans, sans-serif', 'sans-serif'),
(15, 'Arial', 'Arial, ''Trebuchet MS'', Tahoma, Verdana, FreeSans, sans-serif', 'sans-serif'),
(16, 'Verdana', 'Arial, Verdana, ''Trebuchet MS'', Tahoma, Arial, sans-serif', 'sans-serif'),
(17, 'Tahoma', 'Tahoma, Verdana, ''Trebuchet MS'', Arial, FreeSans, sans-serif', 'sans-serif'),
(18, 'Ubuntu', 'Ubuntu, FreeSans, Tahoma, sans-serif', 'sans-serif'),
(19, 'Liberation Sans', 'Liberation Sans, sans-serif', 'sans-serif'),
(20, 'Bauhaus 93', '''Bauhaus 93'', fantasy', 'fantasy'),
(21, 'Jokerman', 'Jokerman, fantasy', 'fantasy'),
(22, 'Impact', 'Impact, fantasy', 'fantasy'),
(23, 'Papyrus', 'Papyrus, fantasy', 'fantasy'),
(24, 'Copperplate Gothic B', '''Copperplate Gothic Bold'', fantasy', 'fantasy'),
(25, 'Rockwell Extra Bold', '''Rockwell Extra Bold'', fantasy', 'fantasy'),
(67, 'Lucida Sans Handwrit', '''Lucida Sans Handwritten'', cursive', 'cursive'),
(68, 'Comic Sans MS', '''Comic Sans MS'', cursive', 'cursive'),
(69, 'Lucida Sans Handwrit', '''Lucida Sans Handwritten'', cursive', 'cursive'),
(70, 'Curlz MT', '''Curlz MT'', cursive', 'cursive'),
(71, 'Freestyle Script', '''Freestyle Script'', cursive', 'cursive'),
(72, 'Edwardian Script ITC', '''Edwardian Script ITC'', cursive', 'cursive');
