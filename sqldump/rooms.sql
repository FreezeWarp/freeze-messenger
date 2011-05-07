-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Host: 10.10.10.1
-- Generation Time: May 07, 2011 at 01:48 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.3-1ubuntu9.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `vbulletin`
--

-- --------------------------------------------------------

--
-- Table structure for table `vrc_rooms`
--

CREATE TABLE IF NOT EXISTS `vrc_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `title` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `allowedGroups` varchar(10000) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL COMMENT 'Comma-separated',
  `allowedUsers` varchar(10000) NOT NULL COMMENT 'Comma-separated',
  `owner` int(10) NOT NULL,
  `moderators` varchar(10000) NOT NULL,
  `options` int(10) NOT NULL,
  `bbcode` int(2) NOT NULL COMMENT '1 - Everything. 4 - No IMG, YOUTUBE. 6 - Nothing.',
  `lastMessageTime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `lastMessageId` int(10) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `Options` (`options`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=453 ;
