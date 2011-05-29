CREATE TABLE IF NOT EXISTS `{prefix}roomStats` (
  `userid` int(10) NOT NULL,
  `roomid` int(10) NOT NULL,
  `messages` int(10) NOT NULL,
  PRIMARY KEY (`userid`,`roomid`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
