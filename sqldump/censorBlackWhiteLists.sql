CREATE TABLE IF NOT EXISTS {prefix}censorBlackWhiteLists(
  `listid` int(10) NOT NULL,
  `roomid` int(10) NOT NULL,
  `status` enum('block','unblock') NOT NULL,
  PRIMARY KEY (`listid`,`roomid`)
) ENGINE={engine}  DEFAULT CHARSET=utf8;