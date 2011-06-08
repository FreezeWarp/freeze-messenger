CREATE TABLE IF NOT EXISTS `{prefix}sessions` (
  `userId` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `browser` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `magicHash` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`magicHash`)
) ENGINE={engine} DEFAULT CHARSET=utf8;