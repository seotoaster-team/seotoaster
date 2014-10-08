SET NAMES utf8;
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `config` (`name`, `value`) VALUES
('currentTheme',	'default'),
('imgSmall',	'250'),
('imgMedium',	'350'),
('imgLarge',	'500'),
('useSmtp',	'0'),
('smtpHost',	''),
('smtpLogin',	''),
('smtpPassword',	''),
('language',	'us'),
('teaserSize',	'200'),
('smtpPort',	''),
('memPagesInMenu',	'1'),
('mediaServers',	'0'),
('smtpSsl',	'0'),
('codeEnabled',	'0'),
('inlineEditor',	'0'),
('recaptchaPublicKey',	'6LcaJdASAAAAADyAWIdBYytJMmYPEykb3Otz4pp6'),
('recaptchaPrivateKey',	'6LcaJdASAAAAAH-e1dWpk96PACf3BQG1OGGvh5hK'),
('enableMobileTemplates',	'1'),
('version',	'2.3.0');

DROP TABLE IF EXISTS `container`;
CREATE TABLE `container` (
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
  KEY `indPageId` (`page_id`),
  CONSTRAINT `container_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


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
  KEY `indDplPageId` (`page_id`),
  CONSTRAINT `deeplink_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `email_triggers`;
CREATE TABLE `email_triggers` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `enabled` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
  `trigger_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `observer` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trigger_name_2` (`trigger_name`,`observer`),
  KEY `trigger_name` (`trigger_name`),
  KEY `observer` (`observer`),
  KEY `enabled` (`enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `email_triggers` (`id`, `enabled`, `trigger_name`, `observer`) VALUES
(1,	'1',	't_feedbackform',	'Tools_Mail_SystemMailWatchdog'),
(2,	'1',	't_passwordreset',	'Tools_Mail_SystemMailWatchdog'),
(3,	'1',	't_passwordchange',	'Tools_Mail_SystemMailWatchdog'),
(4,	'1',	't_membersignup',	'Tools_Mail_SystemMailWatchdog'),
(5,	'1',	't_systemnotification',	'Tools_Mail_SystemMailWatchdog');

DROP TABLE IF EXISTS `email_triggers_actions`;
CREATE TABLE `email_triggers_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `service` enum('email','sms') COLLATE utf8_unicode_ci DEFAULT NULL,
  `trigger` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `template` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `recipient` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8_unicode_ci,
  `from` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'can be used in the From field of e-mail',
  `subject` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'can be used in the "Subject" field of e-mail',
  PRIMARY KEY (`id`),
  KEY `trigger` (`trigger`),
  KEY `template` (`template`),
  KEY `recipient` (`recipient`),
  CONSTRAINT `email_triggers_actions_ibfk_1` FOREIGN KEY (`trigger`) REFERENCES `email_triggers` (`trigger_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `email_triggers_actions_ibfk_2` FOREIGN KEY (`recipient`) REFERENCES `email_triggers_recipient` (`recipient`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `email_triggers_actions_ibfk_3` FOREIGN KEY (`template`) REFERENCES `template` (`name`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `email_triggers_recipient`;
CREATE TABLE `email_triggers_recipient` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Recipient Name',
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `email_triggers_recipient` (`id`, `recipient`) VALUES
(4,	'admin'),
(3,	'copywriter'),
(1,	'guest'),
(2,	'member'),
(5,	'superadmin');

DROP TABLE IF EXISTS `featured_area`;
CREATE TABLE `featured_area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(164) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `form`;
CREATE TABLE `form` (
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
  `reply_text` text COLLATE utf8_unicode_ci,
  `captcha` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  `mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `enable_sms` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `form_page_conversion`;
CREATE TABLE `form_page_conversion` (
  `page_id` int(10) unsigned NOT NULL,
  `form_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `conversion_code` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`page_id`,`form_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `link_container`;
CREATE TABLE `link_container` (
  `id_container` int(10) unsigned NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_container`,`link`),
  KEY `indContainerId` (`id_container`),
  KEY `indLink` (`link`),
  CONSTRAINT `FK_link_container` FOREIGN KEY (`id_container`) REFERENCES `container` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `observers_queue`;
CREATE TABLE `observers_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `observable` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Observable Class Name',
  `observer` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Observer Class Name',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `optimized`;
CREATE TABLE `optimized` (
  `page_id` int(10) unsigned NOT NULL COMMENT 'Foreign key to page table',
  `url` tinytext COLLATE utf8_unicode_ci,
  `h1` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `header_title` text COLLATE utf8_unicode_ci DEFAULT NULL,
  `nav_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `targeted_key_phrase` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8_unicode_ci,
  `meta_keywords` text COLLATE utf8_unicode_ci,
  `teaser_text` text COLLATE utf8_unicode_ci,
  `seo_intro` text COLLATE utf8_unicode_ci,
  `seo_intro_target` text COLLATE utf8_unicode_ci,
  `status` enum('tweaked','on') COLLATE utf8_unicode_ci DEFAULT NULL,
  `seo_rule_id` int(10) DEFAULT NULL,
  `url_rule_id` int(10) DEFAULT NULL,
  UNIQUE KEY `page_id` (`page_id`),
  KEY `h1` (`h1`),
  KEY `status` (`status`),
  KEY `nav_name` (`nav_name`),
  KEY `url` (`url`(30)),
  CONSTRAINT `optimized_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `page`;
CREATE TABLE `page` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_id` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` int(11) NOT NULL DEFAULT '0',
  `nav_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `meta_description` text COLLATE utf8_unicode_ci,
  `meta_keywords` text COLLATE utf8_unicode_ci,
  `header_title` text COLLATE utf8_unicode_ci DEFAULT NULL,
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
  `preview_image` text COLLATE utf8_unicode_ci COMMENT 'Page Preview Image',
  PRIMARY KEY (`id`),
  KEY `indParentId` (`parent_id`),
  KEY `indUrl` (`url`),
  KEY `indMenu` (`show_in_menu`),
  KEY `indOrder` (`order`),
  KEY `indProtected` (`protected`),
  KEY `draft` (`draft`),
  KEY `news` (`news`),
  KEY `nav_name` (`nav_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `page` (`id`, `template_id`, `parent_id`, `nav_name`, `meta_description`, `meta_keywords`, `header_title`, `h1`, `url`, `teaser_text`, `last_update`, `is_404page`, `show_in_menu`, `order`, `weight`, `silo_id`, `targeted_key_phrase`, `protected`, `system`, `draft`, `publish_at`, `news`, `err_login_landing`, `mem_landing`, `signup_landing`, `checkout`, `preview_image`) VALUES
(1,	'index',	0,	'Home',	'',	'',	'Home',	'Home',	'index.html',	'',	'2012-06-20 11:30:39',	'0',	'1',	0,	0,	NULL,	'',	'0',	'0',	'0',	NULL,	'0',	'0',	'0',	'0',	'0',	NULL);

DROP TABLE IF EXISTS `page_fa`;
CREATE TABLE `page_fa` (
  `page_id` int(10) unsigned NOT NULL,
  `fa_id` int(10) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`page_id`,`fa_id`),
  KEY `indPageId` (`page_id`),
  KEY `indFaId` (`fa_id`),
  KEY `indOrder` (`order`),
  CONSTRAINT `FK_page_fa` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_fa_ibfk_1` FOREIGN KEY (`fa_id`) REFERENCES `featured_area` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `page_has_option`;
CREATE TABLE `page_has_option` (
  `page_id` int(10) unsigned NOT NULL,
  `option_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`page_id`,`option_id`),
  KEY `option_id` (`option_id`),
  CONSTRAINT `page_has_option_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE,
  CONSTRAINT `page_has_option_ibfk_2` FOREIGN KEY (`option_id`) REFERENCES `page_option` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `page_option`;
CREATE TABLE `page_option` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `title` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `context` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'In which context this option is used. E.g. option_newsindex used in News system context',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `option_usage` ENUM('once', 'many') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT 'many',
  PRIMARY KEY (`id`),
  KEY `active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `page_option` (`id`, `title`, `context`, `active`, `option_usage`) VALUES
('option_404page',	'Our error 404 "Not found" page',	'Seotoaster pages',	1, 'many'),
('option_member_landing',	'Where members land after logging-in',	'Seotoaster membership',	1, 'once'),
('option_member_loginerror',	'Our membership login error page',	'Seotoaster membership',	1, 'once'),
('option_member_signuplanding',	'Where members land after signed-up',	'Seotoaster membership',	1, 'once'),
('option_protected',	'Accessible only to logged-in members',	'Seotoaster pages',	1, 'many'),
('option_search',	'Search landing page',	'Seotoaster pages',	1, 'once');

DROP TABLE IF EXISTS `password_reset_log`;
CREATE TABLE `password_reset_log` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `plugin`;
CREATE TABLE `plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('enabled','disabled') COLLATE utf8_unicode_ci DEFAULT 'disabled',
  `tags` text COLLATE utf8_unicode_ci COMMENT 'comma separated words',
  `license` blob,
  `version` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`),
  KEY `indStatus` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `seo_data`;
CREATE TABLE `seo_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seo_top` longtext COLLATE utf8_unicode_ci,
  `seo_bottom` longtext COLLATE utf8_unicode_ci,
  `seo_head` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `silo`;
CREATE TABLE `silo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `template`;
CREATE TABLE `template` (
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `content` longtext COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`),
  KEY `type` (`type`),
  CONSTRAINT `template_ibfk_1` FOREIGN KEY (`type`) REFERENCES `template_type` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `template` (`name`, `content`, `type`) VALUES
('category',	'<!DOCTYPE html>\n<html lang="en">\n<head>\n    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>\n    <title>{$page:title}</title>\n    <meta name="keywords" content="{$meta:keywords}"/>\n    <meta name="description" content="{$meta:description}"/>\n    <meta name="generator" content="seotoaster"/>\n\n    <link href="reset.css" rel="stylesheet" type="text/css" media="screen"/>\n    <link href="style.css" rel="stylesheet" type="text/css" media="screen"/>\n    <link href="content.css" rel="stylesheet" type="text/css" media="screen"/>\n\n    <!--[if IE]>\n    <script src="html5.js" type="text/javascript"></script>\n    <![endif]-->\n\n</head>\n\n<body>\n<div class="container_12">\n    <header>\n        <div class="grid_3">\n            <div class="logo">\n                <a href="{$website:url}" title="{$page:h1}" class="logo">\n                    <img src="images/logo-small.jpg" width="110" alt="seotoaster">\n                </a>\n            </div>\n        </div>\n        <div class="grid_9">\n            <h2 class="mt30px mb20px xlarge"><strong>Welcome to SEOTOASTER V2 !</strong></h2>\n            <nav>{$menu:main}</nav>\n        </div>\n    </header>\n    <hr/>\n    <aside class="grid_3">\n    <h2>Flat menu</h2>\n    {$menu:flat}\n    </aside>\n    <section class="grid_9">\n        <h1>{$page:h1}</h1>\n        <article>\n            <h3>Header widgets</h3>\n            {$header:header}\n            {$header:header1:static}\n        </article>\n        <article>\n            <h3>Content widgets</h3>\n            {$content:header}\n            {$content:header1:static}\n        </article>\n        <article>\n            <h3>Image Only widget</h3>\n            {$imageonly:photo:200}\n        </article>\n        <article>\n            <h3>Gallery Only widget</h3>\n            {$galleryonly:uniq_name}\n        </article>\n        <article>\n            <h3>Text Only widget</h3>\n            {$textonly:uniq_name}\n        </article>\n        <article>\n            <h3>Featured Area Only widget</h3>\n            {$featuredonly:name}\n        </article>\n        <article>\n            <h3>DirectUpload widget</h3>\n            {$directupload:foldername:imagename:100::crop}\n        </article>\n    </section>\n    <hr/>\n    <footer class="mt10px">\n        <p>Powered by Free &amp; Open Source Ecommerce Website Builder <a href="http://www.seotoaster.com" target="_blank">SEOTOASTER</a>, Courtesy of <a href="http://www.seosamba.com" target="_blank">SEO Samba</a>.</p>\n    </footer>\n</div>\n{$content:newContent}\n</body>\n</html>',	'typeregular'),
('default',	'<!DOCTYPE html>\n<html lang="en">\n<head>\n    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>\n    <title>{$page:title}</title>\n    <meta name="keywords" content="{$meta:keywords}"/>\n    <meta name="description" content="{$meta:description}"/>\n    <meta name="generator" content="seotoaster"/>\n\n    <link href="reset.css" rel="stylesheet" type="text/css" media="screen"/>\n    <link href="style.css" rel="stylesheet" type="text/css" media="screen"/>\n    <link href="content.css" rel="stylesheet" type="text/css" media="screen"/>\n\n    <!--[if IE]>\n    <script src="html5.js" type="text/javascript"></script>\n    <![endif]-->\n\n</head>\n\n<body>\n<div class="container_12">\n    <header>\n        <div class="grid_3">\n            <div class="logo">\n                <a href="{$website:url}" title="{$page:h1}" class="logo">\n                    <img src="images/logo-small.jpg" width="110" alt="seotoaster">\n                </a>\n            </div>\n        </div>\n        <div class="grid_9">\n            <h2 class="mt30px mb20px xlarge"><strong>Welcome to SEOTOASTER V2 !</strong></h2>\n            <nav>{$menu:main}</nav>\n        </div>\n    </header>\n    <hr/>\n    <aside class="grid_3">\n    <h2>Flat menu</h2>\n    {$menu:flat}\n    </aside>\n    <section class="grid_9">\n        <h1>{$page:h1}</h1>\n        <article>\n            <h3>Header widgets</h3>\n            {$header:header}\n            {$header:header1:static}\n        </article>\n        <article>\n            <h3>Content widgets</h3>\n            {$content:header}\n            {$content:header1:static}\n        </article>\n        <article>\n            <h3>Image Only widget</h3>\n            {$imageonly:photo:200}\n        </article>\n        <article>\n            <h3>Gallery Only widget</h3>\n            {$galleryonly:uniq_name}\n        </article>\n        <article>\n            <h3>Text Only widget</h3>\n            {$textonly:uniq_name}\n        </article>\n        <article>\n            <h3>FeaturedArea Only widget</h3>\n            {$featuredonly:name}\n        </article>\n        <article>\n            <h3>DirectUpload widget</h3>\n            {$directupload:foldername:imagename:100::crop}\n        </article>\n    </section>\n    <hr/>\n    <footer class="mt10px">\n        <p>Powered by Free &amp; Open Source Ecommerce Website Builder <a href="http://www.seotoaster.com" target="_blank">SEOTOASTER</a>, Courtesy of <a href="http://www.seosamba.com" target="_blank">SEO Samba</a>.</p>\n    </footer>\n</div>\n{$content:newContent}\n</body>\n</html>',	'typeregular'),
('index',	'<!DOCTYPE html>\n<html lang="en">\n<head>\n    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>\n    <title>{$page:title}</title>\n    <meta name="keywords" content="{$meta:keywords}"/>\n    <meta name="description" content="{$meta:description}"/>\n    <meta name="generator" content="seotoaster"/>\n\n    <link href="reset.css" rel="stylesheet" type="text/css" media="screen"/>\n    <link href="style.css" rel="stylesheet" type="text/css" media="screen"/>\n    <link href="content.css" rel="stylesheet" type="text/css" media="screen"/>\n\n    <!--[if IE]>\n    <script src="html5.js" type="text/javascript"></script>\n    <![endif]-->\n\n</head>\n\n<body>\n<div class="container_12">\n\n    <header>\n        <div class="grid_4">\n            <h1 class="logo">\n                <a href="{$website:url}" title="{$page:h1}" class="logo">\n                    <img src="images/logo-small.jpg" width="215" height="275" alt="seotoaster">\n                </a>\n            </h1>\n        </div>\n\n        <div class="grid_8">\n            <h2 class="mt40px mb50px xlarge">Congratulations,<br/>you have successfully installed<br/><strong>SEOTOASTER\n                V2 !</strong></h2>\n\n            <div class="log_in">Now log into your admin console at <a href="{$website:url}go">{$website:url}go</a></div>\n        </div>\n    </header>\n    {adminonly}\n    <script>\n        $(document).ready(function () {\n            $(\'.log_in\').hide();\n        });\n    </script>\n    <section>\n        <h3><span class="number">1</span>Hit the ground running: get your website on the map now</h3>\n\n        <p>Complete the website ID [WID] card below. It\'s a great time saver, and when you use one of our free premium\n            themes your information shows up in <strong>all the right places</strong> throughout your website.</p>\n\n        <p>In addition, SEOTOASTER build a kml file to help <strong>search engines and geolocation services locate your\n            business </strong>while plug-ins work better and provide you with a pre-built <strong>mobile\n            version</strong> of your website for instance.</p>\n        <hr/>\n\n        {$plugin:widcard:landing}\n        <h3 id="step2" class="mt10px"><span class="number">2</span>Look like a million bucks: download a FREE premium\n            theme</h3>\n        <iframe id="themesList" scrolling-y="yes" frameborder="0" style="width: 100%; height: 660px;" runat="server"\n                src="http://www.seotoaster.com/themes-for-mojo.html" allowtransparency="true"></iframe>\n        <h3 class="mt10px"><span class="number">3</span>Use the easy-to-follow assembly instructions</h3>\n        <a class="_lbox" title="1 click themes" href="images/how-to-add-theme/1-click-themes-big.jpg"><img\n                src="images/how-to-add-theme/1-click-themes.jpg" border="0" alt="1 click themes" width="315"\n                height="221"/></a>\n        <a class="_lbox" title="2 upload theme" href="images/how-to-add-theme/2-upload-theme-big.jpg"><img\n                src="images/how-to-add-theme/2-upload-theme.jpg" border="0" alt="2 upload theme" width="315"\n                height="221"/></a>\n        <a class="_lbox" title="3 select theme" href="images/how-to-add-theme/3-select-theme-big.jpg"><img\n                src="images/how-to-add-theme/3-select-theme.jpg" border="0" alt="3 select theme" width="315"\n                height="221"/></a>\n\n        <h3 class="mt40px"><span class="number">4</span>Explore the plug-ins marketplace: buy or lease it\'s up to you !\n        </h3>\n        <iframe id="themesList" scrolling-y="yes" frameborder="0" style="width: 100%; height: 700px;" runat="server"\n                src="http://www.seotoaster.com/plugins-for-mojo.html" allowtransparency="true"></iframe>\n    </section>\n    {/adminonly}\n    <hr/>\n    <footer class="mt10px">\n        <p>Powered by Free &amp; Open Source Ecommerce Website Builder <a href="http://www.seotoaster.com"\n                                                                          target="_blank">SEOTOASTER</a>, Courtesy of <a\n                href="http://www.seosamba.com" target="_blank">SEO Samba</a>.</p></footer>\n</div>\n{$content:newContent}\n</body>\n</html>',	'typeregular');

DROP TABLE IF EXISTS `template_type`;
CREATE TABLE `template_type` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Template type name: For example ''quote'', ''regularpage'', etc...',
  `title` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'Alias for the template "Product listing", etc...',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `template_type` (`id`, `title`) VALUES
('typemail',	'E-mail sending'),
('typemenu',	'Menu'),
('typemobile',	'Mobile page'),
('typeregular',	'Regular page'),
('type_partial_template',	'Partial template');

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
  `referer` tinytext COLLATE utf8_unicode_ci,
  `gplus_profile` tinytext COLLATE utf8_unicode_ci,
  `mobile_phone` varchar(20) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indEmail` (`email`),
  KEY `indPassword` (`password`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `user_attributes`;
CREATE TABLE `user_attributes` (
  `user_id` int(10) unsigned NOT NULL,
  `attribute` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`user_id`,`attribute`(20)),
  CONSTRAINT `user_attributes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

