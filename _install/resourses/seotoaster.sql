-- phpMyAdmin SQL Dump
-- version 3.4.3
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 26, 2012 at 12:23 PM
-- Server version: 5.0.77
-- PHP Version: 5.3.6

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `iamne_velvetcoua`
--

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `name` varchar(100) collate utf8_unicode_ci NOT NULL default '',
  `value` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `container`
--

CREATE TABLE IF NOT EXISTS `container` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `container_type` int(10) unsigned NOT NULL,
  `page_id` int(11) unsigned default NULL,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `published` enum('0','1') collate utf8_unicode_ci default '1',
  `publishing_date` date default NULL,
  `content` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `indPublished` (`published`),
  KEY `indContainerType` (`container_type`),
  KEY `indPageId` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `deeplink`
--

CREATE TABLE IF NOT EXISTS `deeplink` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `page_id` int(10) unsigned default NULL,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  `url` varchar(255) collate utf8_unicode_ci NOT NULL,
  `type` enum('int','ext') collate utf8_unicode_ci default 'int',
  `ban` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `nofollow` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `indName` (`name`),
  KEY `indType` (`type`),
  KEY `indUrl` (`url`),
  KEY `indDplPageId` (`page_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=52 ;

-- --------------------------------------------------------

--
-- Table structure for table `featured_area`
--

CREATE TABLE IF NOT EXISTS `featured_area` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(164) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `indName` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=34 ;

-- --------------------------------------------------------

--
-- Table structure for table `form`
--

CREATE TABLE IF NOT EXISTS `form` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) COLLATE utf8_unicode_ci NOT NULL,
  `code` text COLLATE utf8_unicode_ci NOT NULL,
  `contact_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `message_success` text COLLATE utf8_unicode_ci NOT NULL,
  `message_error` text COLLATE utf8_unicode_ci NOT NULL,
  `reply_subject` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reply_mail_template` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `reply_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `reply_from_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `tracking_code` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

-- --------------------------------------------------------

--
-- Table structure for table `link_container`
--

CREATE TABLE IF NOT EXISTS `link_container` (
  `id_container` int(10) unsigned NOT NULL,
  `link` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id_container`,`link`),
  KEY `indContainerId` (`id_container`),
  KEY `indLink` (`link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `category_id` int(10) unsigned NOT NULL,
  `title` varchar(255) collate utf8_unicode_ci NOT NULL,
  `page_url` varchar(255) collate utf8_unicode_ci default NULL,
  `intro` text collate utf8_unicode_ci NOT NULL,
  `text` text collate utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `archived` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `featured` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `disable_archive` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `last_update` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `meta_description` text collate utf8_unicode_ci NOT NULL,
  `meta_keywords` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `indUrl` (`page_url`),
  KEY `indFeatured` (`featured`),
  KEY `indNewsCat` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `news_category`
--

CREATE TABLE IF NOT EXISTS `news_category` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `observers_queue`
--

CREATE TABLE IF NOT EXISTS `observers_queue` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `namespace` text collate utf8_unicode_ci NOT NULL,
  `observer` text collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

CREATE TABLE IF NOT EXISTS `page` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `template_id` varchar(45) collate utf8_unicode_ci NOT NULL,
  `parent_id` int(11) NOT NULL default '0',
  `nav_name` varchar(255) collate utf8_unicode_ci default NULL,
  `meta_description` text collate utf8_unicode_ci,
  `meta_keywords` text collate utf8_unicode_ci,
  `header_title` varchar(255) collate utf8_unicode_ci default NULL,
  `h1` varchar(255) collate utf8_unicode_ci default NULL,
  `url` varchar(255) collate utf8_unicode_ci default NULL,
  `teaser_text` text collate utf8_unicode_ci,
  `last_update` timestamp NULL default CURRENT_TIMESTAMP,
  `is_404page` enum('0','1') collate utf8_unicode_ci default '0',
  `show_in_menu` enum('0','1','2') collate utf8_unicode_ci default '0',
  `order` tinyint(3) unsigned default NULL,
  `weight` tinyint(3) unsigned default '0',
  `silo_id` int(10) unsigned default NULL,
  `targeted_key_phrase` varchar(255) collate utf8_unicode_ci default NULL,
  `protected` enum('0','1') character set utf8 default '0',
  `system` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `draft` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `publish_at` date default NULL,
  `news` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `err_login_landing` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `mem_landing` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `signup_landing` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  `checkout` enum('0','1') collate utf8_unicode_ci NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `indParentId` (`parent_id`),
  KEY `indUrl` (`url`),
  KEY `indMenu` (`show_in_menu`),
  KEY `indOrder` (`order`),
  KEY `indProtected` (`protected`),
  KEY `draft` (`draft`),
  KEY `news` (`news`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `page_fa`
--

CREATE TABLE IF NOT EXISTS `page_fa` (
  `page_id` int(10) unsigned NOT NULL,
  `fa_id` int(10) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY  (`page_id`,`fa_id`),
  KEY `indPageId` (`page_id`),
  KEY `indFaId` (`fa_id`),
  KEY `indOrder` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `plugin`
--

CREATE TABLE IF NOT EXISTS `plugin` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `status` enum('enabled','disabled') collate utf8_unicode_ci default 'disabled',
  `cache` enum('0','1') collate utf8_unicode_ci default '0',
  `tag` varchar(255) collate utf8_unicode_ci default NULL,
  `license` blob,
  PRIMARY KEY  (`id`),
  KEY `indName` (`name`),
  KEY `indStatus` (`status`),
  KEY `indCache` (`cache`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `redirect`
--

CREATE TABLE IF NOT EXISTS `redirect` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `page_id` int(10) unsigned default NULL,
  `from_url` varchar(255) collate utf8_unicode_ci NOT NULL,
  `to_url` varchar(255) collate utf8_unicode_ci NOT NULL,
  `domain_to` varchar(255) collate utf8_unicode_ci NOT NULL,
  `domain_from` varchar(255) collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `indPageId` (`page_id`),
  KEY `indFromUrl` (`from_url`),
  KEY `indToUrl` (`to_url`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=68 ;

-- --------------------------------------------------------

--
-- Table structure for table `seo_data`
--

CREATE TABLE IF NOT EXISTS `seo_data` (
  `id` int(11) NOT NULL auto_increment,
  `seo_top` longtext collate utf8_unicode_ci,
  `seo_bottom` longtext collate utf8_unicode_ci,
  `seo_head` longtext collate utf8_unicode_ci NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `silo`
--

CREATE TABLE IF NOT EXISTS `silo` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  PRIMARY KEY  (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `template`
--

CREATE TABLE IF NOT EXISTS `template` (
  `name` varchar(45) collate utf8_unicode_ci NOT NULL,
  `content` text collate utf8_unicode_ci NOT NULL,
  `preview_image` varchar(255) collate utf8_unicode_ci default NULL,
  `type` enum('typeregular','typeproduct','typelisting','typemail','typecheckout','typequote') collate utf8_unicode_ci NOT NULL default 'typeregular',
  PRIMARY KEY  (`name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `role_id` varchar(15) collate utf8_unicode_ci NOT NULL,
  `password` varchar(35) collate utf8_unicode_ci default NULL COMMENT 'user password',
  `email` varchar(255) collate utf8_unicode_ci default NULL,
  `full_name` varchar(40) collate utf8_unicode_ci default NULL,
  `last_login` timestamp NULL default CURRENT_TIMESTAMP,
  `ipaddress` varchar(30) collate utf8_unicode_ci default NULL,
  `reg_date` timestamp NULL default NULL,
  PRIMARY KEY  (`id`),
  KEY `indEmail` (`email`),
  KEY `indPassword` (`password`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=6 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `link_container`
--
ALTER TABLE `link_container`
  ADD CONSTRAINT `FK_link_container` FOREIGN KEY (`id_container`) REFERENCES `container` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_fa`
--
ALTER TABLE `page_fa`
  ADD CONSTRAINT `FK_page_fa` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `redirect`
--
ALTER TABLE `redirect`
  ADD CONSTRAINT `FK_redirect` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;