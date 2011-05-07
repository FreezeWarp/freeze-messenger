-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Host: 10.10.10.1
-- Generation Time: May 07, 2011 at 01:47 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.3-1ubuntu9.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `vbulletin`
--

-- --------------------------------------------------------

--
-- Table structure for table `vrc_users`
--

CREATE TABLE IF NOT EXISTS `vrc_users` (
  `userid` int(10) NOT NULL,
  `settings` int(10) NOT NULL DEFAULT '32',
  `defaultRoom` int(10) NOT NULL DEFAULT '0',
  `favRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '1,3,4,7,9,10',
  `watchRooms` varchar(300) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `status` int(3) NOT NULL,
  `defaultFormatting` int(10) NOT NULL,
  `defaultHighlight` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultColour` varchar(20) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `defaultFontface` varchar(100) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`userid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;