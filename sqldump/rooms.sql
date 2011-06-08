CREATE TABLE IF NOT EXISTS `{prefix}rooms` (
  `roomId` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `title` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `allowedGroups` TEXT(10000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Comma-separated',
  `allowedUsers` TEXT(10000) NOT NULL COMMENT 'Comma-separated',
  `owner` int(10) NOT NULL,
  `moderators` TEXT(10000) NOT NULL,
  `options` int(10) NOT NULL,
  `bbcode` int(2) NOT NULL,
  `lastMessageTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastMessageId` int(10) NOT NULL,
  PRIMARY KEY (`roomId`),
  KEY `options` (`options`),
  KEY `owner` (`owner`),
  KEY `lastMessageId` (`lastMessageId`),
  KEY `lastMessageTime` (`lastMessageTime`)
) ENGINE={engine} DEFAULT CHARSET=utf8;

-- DIVIDE

INSERT INTO `{prefix}rooms` (`roomId`, `name`, `title`, `allowedGroups`, `allowedUsers`, `options`) VALUES
(1, 'Your Room!', 'Hit the Edit Room Button to Change Things or Use /topic to Change the Topic', '*', '*', 1)