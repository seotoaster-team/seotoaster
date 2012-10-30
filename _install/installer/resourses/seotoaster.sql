CREATE TABLE IF NOT EXISTS `config` (
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `config` (`name`, `value`) VALUES
('currentTheme', 'default'),
('imgSmall', '250'),
('imgMedium', '350'),
('imgLarge', '500'),
('useSmtp', '0'),
('smtpHost', ''),
('smtpLogin', ''),
('smtpPassword', ''),
('language', 'us'),
('newsFolder', 'news'),
('teaserSize', '200'),
('smtpPort', ''),
('memPagesInMenu', '1'),
('mediaServers', '0'),
('smtpSsl', '0'),
('codeEnabled', '0'),
('inlineEditor', '0');

CREATE TABLE IF NOT EXISTS `container` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `container_type` int(10) unsigned NOT NULL,
  `page_id` int(11) unsigned DEFAULT NULL,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `published` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '1',
  `publishing_date` date DEFAULT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indPublished` (`published`),
  KEY `indContainerType` (`container_type`),
  KEY `indPageId` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `deeplink` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `email_triggers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `enabled` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
  `trigger_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `observer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `trigger_name` (`trigger_name`),
  KEY `observer` (`observer`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO `email_triggers` (`id`, `enabled`, `trigger_name`, `observer`) VALUES
(1, '1', 't_feedbackform', 'Tools_Mail_SystemMailWatchdog'),
(2, '1', 't_passwordreset', 'Tools_Mail_SystemMailWatchdog'),
(3, '1', 't_passwordchange', 'Tools_Mail_SystemMailWatchdog'),
(4, '1', 't_membersignup', 'Tools_Mail_SystemMailWatchdog'),
(5, '1', 't_systemnotification', 'Tools_Mail_SystemMailWatchdog');

CREATE TABLE IF NOT EXISTS `email_triggers_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `trigger` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `template` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8_unicode_ci,
  `from` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'can be used in the From field of e-mail',
  `subject` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'can be used in the "Subject" field of e-mail',
  PRIMARY KEY (`id`),
  KEY `trigger` (`trigger`),
  KEY `template` (`template`),
  KEY `recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `email_triggers_recipient` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Recipient Name',
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

INSERT INTO `email_triggers_recipient` (`id`, `recipient`) VALUES
(4, 'admin'),
(3, 'copywriter'),
(1, 'guest'),
(2, 'member'),
(5, 'superadmin');

CREATE TABLE IF NOT EXISTS `featured_area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(164) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

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
  `reply_text` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `link_container` (
  `id_container` int(10) unsigned NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_container`,`link`),
  KEY `indContainerId` (`id_container`),
  KEY `indLink` (`link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `text` text COLLATE utf8_unicode_ci NOT NULL,
  `archived` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `featured` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `page_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indFeatured` (`featured`),
  KEY `page_id` (`page_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `news_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `news_rel_category` (
  `news_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`news_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `observers_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `observable` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Observable Class Name',
  `observer` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Observer Class Name',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `optimized` (
  `page_id` int(10) unsigned NOT NULL COMMENT 'Foreign key to page table',
  `url` tinytext COLLATE utf8_unicode_ci,
  `h1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `header_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `nav_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `targeted_key_phrase` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8_unicode_ci,
  `meta_keywords` text COLLATE utf8_unicode_ci,
  `modified` bit(1) NOT NULL DEFAULT b'0',
  `status` enum('tweaked','on') COLLATE utf8_unicode_ci DEFAULT NULL,
  `seo_rule_id` int(10) DEFAULT NULL,
  `url_rule_id` int(10) DEFAULT NULL,
  UNIQUE KEY `page_id` (`page_id`),
  KEY `h1` (`h1`),
  KEY `modified` (`modified`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `page` (
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
  `last_update` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_404page` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `show_in_menu` enum('0','1','2') COLLATE utf8_unicode_ci DEFAULT '0',
  `order` tinyint(3) unsigned DEFAULT NULL,
  `weight` tinyint(3) unsigned DEFAULT '0',
  `silo_id` int(10) unsigned DEFAULT NULL,
  `targeted_key_phrase` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `protected` enum('0','1') CHARACTER SET utf8 DEFAULT '0',
  `system` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `draft` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `publish_at` date DEFAULT NULL,
  `news` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `err_login_landing` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `mem_landing` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `signup_landing` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  `checkout` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `indParentId` (`parent_id`),
  KEY `indUrl` (`url`),
  KEY `indMenu` (`show_in_menu`),
  KEY `indOrder` (`order`),
  KEY `indProtected` (`protected`),
  KEY `draft` (`draft`),
  KEY `news` (`news`),
  KEY `nav_name` (`nav_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=2 ;

INSERT INTO `page` (`id`, `template_id`, `parent_id`, `nav_name`, `meta_description`, `meta_keywords`, `header_title`, `h1`, `url`, `teaser_text`, `last_update`, `is_404page`, `show_in_menu`, `order`, `weight`, `silo_id`, `targeted_key_phrase`, `protected`, `system`, `draft`, `publish_at`, `news`, `err_login_landing`, `mem_landing`, `signup_landing`, `checkout`) VALUES
(1, 'index', 0, 'Home', '', '', 'Home', 'Home', 'index.html', '', '2012-06-20 14:30:39', '0', '1', 0, 0, NULL, '', '0', '0', '0', NULL, '0', '0', '0', '0', '0');

CREATE TABLE IF NOT EXISTS `page_fa` (
  `page_id` int(10) unsigned NOT NULL,
  `fa_id` int(10) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`page_id`,`fa_id`),
  KEY `indPageId` (`page_id`),
  KEY `indFaId` (`fa_id`),
  KEY `indOrder` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `token_hash` varchar(100) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Password reset token. Unique hash string.',
  `user_id` int(10) unsigned NOT NULL,
  `status` enum('new','used','expired') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'new' COMMENT 'Recovery link status',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expired_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `status` (`status`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('enabled','disabled') COLLATE utf8_unicode_ci DEFAULT 'disabled',
  `tags` text COLLATE utf8_unicode_ci COMMENT 'comma separated words',
  `license` blob,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`),
  KEY `indStatus` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `redirect` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `page_id` int(10) unsigned DEFAULT NULL,
  `from_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `to_url` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `domain_to` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `domain_from` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indPageId` (`page_id`),
  KEY `indFromUrl` (`from_url`),
  KEY `indToUrl` (`to_url`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `seo_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seo_top` longtext COLLATE utf8_unicode_ci,
  `seo_bottom` longtext COLLATE utf8_unicode_ci,
  `seo_head` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `silo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `template` (
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `template_type` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Template type name: For example ''quote'', ''regularpage'', etc...',
  `title` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'Alias for the template "Product listing", etc...',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `template_type` (`id`, `title`) VALUES
('typecheckout', 'Checkout page'),
('typelisting', 'Product listing'),
('typemail', 'E-mail sending'),
('typeproduct', 'Product page'),
('typeregular', 'Regular page');

CREATE TABLE IF NOT EXISTS `user` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` varchar(15) COLLATE utf8_unicode_ci NOT NULL,
  `password` varchar(35) COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'user password',
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `full_name` varchar(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `ipaddress` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `reg_date` timestamp NULL DEFAULT NULL,
  `referer` tinytext COLLATE utf8_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `indEmail` (`email`),
  KEY `indPassword` (`password`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `page_has_option` (
  `page_id` int(10) unsigned NOT NULL,
  `option_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`page_id`,`option_id`),
  KEY `option_id` (`option_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `page_option` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `context` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'In which context this option is used. E.g. option_newsindex used in News system context',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `page_option` (`id`, `title`, `context`, `active`) VALUES
('option_404page', 'Our error 404 "Not found" page', 'Seotoaster pages', 1),
('option_member_landing', 'Where members land after logging-in', 'Seotoaster membership', 1),
('option_member_loginerror', 'Our membership login error page', 'Seotoaster membership', 1),
('option_member_signuplanding', 'Where members land after signed-up', 'Seotoaster membership', 1),
('option_protected', 'Accessible only to logged-in members', 'Seotoaster pages', 1);

ALTER TABLE `page_has_option`
  ADD CONSTRAINT `page_has_option_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `page_has_option_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `page_option` (`id`) ON DELETE CASCADE;

ALTER TABLE `container`
  ADD CONSTRAINT `container_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

ALTER TABLE `deeplink`
  ADD CONSTRAINT `deeplink_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

ALTER TABLE `email_triggers_actions`
  ADD CONSTRAINT `email_triggers_actions_ibfk_1` FOREIGN KEY (`trigger`) REFERENCES `email_triggers` (`trigger_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `email_triggers_actions_ibfk_2` FOREIGN KEY (`recipient`) REFERENCES `email_triggers_recipient` (`recipient`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `email_triggers_actions_ibfk_3` FOREIGN KEY (`template`) REFERENCES `template` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;

ALTER TABLE `link_container`
  ADD CONSTRAINT `FK_link_container` FOREIGN KEY (`id_container`) REFERENCES `container` (`id`) ON DELETE CASCADE;

ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

ALTER TABLE `news_rel_category`
  ADD CONSTRAINT `news_rel_category_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `news_rel_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `news_category` (`id`);

ALTER TABLE `optimized`
  ADD CONSTRAINT `optimized_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

ALTER TABLE `page_fa`
  ADD CONSTRAINT `page_fa_ibfk_1` FOREIGN KEY (`fa_id`) REFERENCES `featured_area` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_page_fa` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

ALTER TABLE `redirect`
  ADD CONSTRAINT `FK_redirect` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

ALTER TABLE `template`
  ADD CONSTRAINT `template_ibfk_1` FOREIGN KEY (`type`) REFERENCES `template_type` (`id`) ON DELETE CASCADE;