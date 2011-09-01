DROP TABLE IF EXISTS `config`;

CREATE TABLE `config` (
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

insert  into `config`(`name`,`value`) values ('currentTheme','newthemesST2'),('imgSmall','250'),('imgMedium','350'),('imgLarge','450'),('newsFolder','news');

DROP TABLE IF EXISTS `container`;

CREATE TABLE `container` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `container_type` int(10) unsigned NOT NULL,
  `page_id` int(11) unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '1',
  `publishing_date` date DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indPublished` (`published`),
  KEY `indContainerType` (`container_type`),
  KEY `indPageId` (`page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=88 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `deeplink`;

CREATE TABLE `deeplink` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `type` enum('int','ext') COLLATE utf8_unicode_ci DEFAULT 'int',
  `ban` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `nofollow` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indName` (`name`),
  KEY `indType` (`type`),
  KEY `indUrl` (`url`),
  KEY `indDplPageId` (`page_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `featured_area`;

CREATE TABLE `featured_area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(164) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=34 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `link_container`;

CREATE TABLE `link_container` (
  `id_container` int(10) unsigned NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_container`,`link`),
  KEY `indContainerId` (`id_container`),
  KEY `indLink` (`link`),
  CONSTRAINT `FK_link_container` FOREIGN KEY (`id_container`) REFERENCES `container` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `news`;

CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `id_newscategory` int(10) unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `page_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `intro` text COLLATE utf8_unicode_ci NOT NULL,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `date` datetime NOT NULL,
  `order` int(10) unsigned NOT NULL,
  `is_archived` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `is_featured` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `disable_archive` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `meta_description` text COLLATE utf8_unicode_ci NOT NULL,
  `meta_keywords` text COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indUrl` (`page_url`),
  KEY `indFeatured` (`is_featured`),
  KEY `indNewsCat` (`id_newscategory`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `news_category`;

CREATE TABLE `news_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=MyISAM AUTO_INCREMENT=23 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `page`;

CREATE TABLE `page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `nav_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8_unicode_ci,
  `meta_keywords` text COLLATE utf8_unicode_ci,
  `header_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `h1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `teaser_text` text COLLATE utf8_unicode_ci,
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `is_404page` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `show_in_menu` enum('0','1','2') COLLATE utf8_unicode_ci DEFAULT '0',
  `order` tinyint(3) unsigned DEFAULT NULL,
  `weight` tinyint(3) unsigned DEFAULT '0',
  `silo_id` int(10) unsigned DEFAULT NULL,
  `targeted_key_phrase` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `protected` enum('0','1') CHARACTER SET utf8 DEFAULT '0',
  `raw_content` longtext COLLATE utf8_unicode_ci,
  `system` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indParentId` (`parent_id`),
  KEY `indUrl` (`url`),
  KEY `indMenu` (`show_in_menu`),
  KEY `indOrder` (`order`),
  KEY `indProtected` (`protected`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `page_fa`;

CREATE TABLE `page_fa` (
  `page_id` int(10) unsigned NOT NULL,
  `fa_id` int(10) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`page_id`,`fa_id`),
  KEY `indPageId` (`page_id`),
  KEY `indFaId` (`fa_id`),
  KEY `indOrder` (`order`),
  CONSTRAINT `FK_page_fa` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `plugin`;

CREATE TABLE `plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('enabled','disabled') COLLATE utf8_unicode_ci DEFAULT 'disabled',
  `cache` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `tag` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`),
  KEY `indStatus` (`status`),
  KEY `indCache` (`cache`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `redirect`;

CREATE TABLE `redirect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned DEFAULT NULL,
  `from_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `to_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `domain_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `domain_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indPageId` (`page_id`),
  KEY `indFromUrl` (`from_url`),
  KEY `indToUrl` (`to_url`),
  CONSTRAINT `FK_redirect` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `sculpting`;

CREATE TABLE `sculpting` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `global` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indName` (`name`),
  KEY `indGlobal` (`global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `seo_data`;

CREATE TABLE `seo_data` (
  `seo_top` longtext COLLATE utf8_unicode_ci,
  `seo_bottom` longtext COLLATE utf8_unicode_ci
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `template`;

CREATE TABLE `template` (
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `preview_image` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`name`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(35) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user password',
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `full_name` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ipaddress` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reg_date` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indEmail` (`email`),
  KEY `indPassword` (`password`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;