CREATE TABLE IF NOT EXISTS `{prefix}fileVersions` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `fileid` int(10) NOT NULL,
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `md5hash` varchar(255) NOT NULL,
  `salt` int(10) NOT NULL,
  `iv` varchar(255) NOT NULL,
  `contents` longtext NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE={engine} DEFAULT CHARSET=utf8;
