CREATE TABLE IF NOT EXISTS `{prefix}modlog` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `action` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `data` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
