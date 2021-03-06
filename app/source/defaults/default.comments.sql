####################################################################################################
# post_comments
####################################################################################################

DROP TABLE IF EXISTS `postleaf_comments`;
DROP TABLE IF EXISTS `leafpub_comments`;

CREATE TABLE `leafpub_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent` int(11) NOT NULL,
  `post` int(11) NOT NULL,
  `author` int(11) NOT NULL,
  `comment` longtext NOT NULL,
  `pub_date` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent` (`parent`),
  KEY `post` (`post`),
  KEY `pub_date` (`pub_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4;
