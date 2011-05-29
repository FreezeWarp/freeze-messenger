CREATE TABLE IF NOT EXISTS `{prefix}censorLists` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(400) NOT NULL,
  `type` enum('black','white') NOT NULL,
  `options` int(4) NOT NULL DEFAULT '3',
  PRIMARY KEY (`id`)
) ENGINE={engine}  DEFAULT CHARSET=utf8;