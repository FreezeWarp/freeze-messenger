CREATE TABLE IF NOT EXISTS `{prefix}roomStats` (
  `userId` int(10) NOT NULL,
  `roomid` int(10) NOT NULL,
  `messages` int(10) NOT NULL,
  PRIMARY KEY (`userId`,`roomid`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
