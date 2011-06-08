CREATE TABLE IF NOT EXISTS `{prefix}sessions` (
  `userId` int(10) NOT NULL,
  `time` timestamp NOT NULL,
  `browser` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `magicHash` varchar(1000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL, -- Combines with defined IV and config salt.
  `magicIv` varchar(15) CHARACTER SET utf8 COLLATE utf8_bin  NOT NULL,
  PRIMARY KEY (`userId`)
) ENGINE={engine} DEFAULT CHARSET=utf8;