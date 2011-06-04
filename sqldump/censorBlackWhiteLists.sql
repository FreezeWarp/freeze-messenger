CREATE TABLE IF NOT EXISTS {prefix}censorBlackWhiteLists(
  `listId` int(10) NOT NULL,
  `roomId` int(10) NOT NULL,
  `status` enum('block','unblock') NOT NULL,
  PRIMARY KEY (`listId`,`roomId`)
) ENGINE={engine}  DEFAULT CHARSET=utf8;