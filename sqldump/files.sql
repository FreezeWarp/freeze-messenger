CREATE TABLE IF NOT EXISTS `{prefix}files` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `userId` int(10) NOT NULL,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `size` int(10) NOT NULL,
  `mime` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `rating` enum('6','10','13','16','18') NOT NULL,
  `flags` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` enum('1','0') NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
