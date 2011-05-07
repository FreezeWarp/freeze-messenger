-- phpMyAdmin SQL Dump
-- version 3.3.7deb5build0.10.10.1
-- http://www.phpmyadmin.net
--
-- Host: 10.10.10.1
-- Generation Time: May 07, 2011 at 01:51 PM
-- Server version: 5.1.41
-- PHP Version: 5.3.3-1ubuntu9.5

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Database: `vbulletin`
--

-- --------------------------------------------------------

--
-- Table structure for table `vrc_messages`
--

CREATE TABLE IF NOT EXISTS `vrc_messages` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `user` int(10) NOT NULL,
  `room` int(10) NOT NULL,
  `rawText` varchar(5000) COLLATE utf8_bin NOT NULL,
  `htmlText` varchar(5000) COLLATE utf8_bin NOT NULL,
  `vbText` varchar(5000) COLLATE utf8_bin NOT NULL,
  `salt` int(10) NOT NULL,
  `iv` varchar(15) COLLATE utf8_bin NOT NULL,
  `deleted` int(1) NOT NULL,
  `flaggedUser` int(10) NOT NULL COMMENT 'When this value is set (and thus evalutates to true), it will contain the username who flagged a post.',
  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `microtime` double NOT NULL COMMENT 'For proper processing of posts.',
  `ip` varchar(20) CHARACTER SET latin1 NOT NULL,
  `flag` varchar(10) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `deleted` (`deleted`),
  KEY `time` (`time`),
  KEY `room` (`room`),
  KEY `user` (`user`),
  KEY `microtime` (`microtime`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin COMMENT='Contains VRC messages.' AUTO_INCREMENT=883773 ;
