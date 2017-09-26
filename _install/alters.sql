-- 21/11/2013
-- Main menu template type
-- version: 2.1.0
INSERT INTO `template_type` (`id`, `title`) VALUES ('typemenu', 'Menu');

-- 19/12/2013
-- User attributes table added
-- version: 2.1.1
CREATE TABLE IF NOT EXISTS `user_attributes` (
  `user_id` int(10) unsigned NOT NULL,
  `attribute` tinytext COLLATE utf8_unicode_ci NOT NULL,
  `value` text COLLATE utf8_unicode_ci,
  PRIMARY KEY (`user_id`,`attribute`(20)),
  CONSTRAINT `user_attributes_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 26/12/2013
-- SEO Intro fields for optimized table
-- version: 2.1.2
ALTER TABLE `optimized` ADD `seo_intro` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `teaser_text` ,
ADD `seo_intro_target` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `seo_intro`;

-- 29/01/2014
-- reCAPTCHA refactoring
-- version: 2.1.3
UPDATE `config` SET `name` = 'recaptchaPublicKey' WHERE `name` = 'recapthaPublicKey';
UPDATE `config` SET `name` = 'recaptchaPrivateKey' WHERE `name` = 'recapthaPrivateKey';

-- 07/04/2014
-- Add unique index into email_triggers
-- version: 2.2.0
ALTER TABLE `email_triggers` ADD UNIQUE INDEX(`trigger_name`, `observer`);


-- 22.05.2014
-- Action triggers e-mail or SMS service type
-- version: 2.2.1
ALTER TABLE `email_triggers_actions` ADD `service` ENUM( 'email', 'sms' ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL AFTER `id`;

UPDATE `email_triggers_actions` SET `service` = 'email' WHERE `service` IS NULL;


-- 29.08.2014
-- Extend plugin table
-- version: 2.2.2
ALTER TABLE `plugin` ADD `version` varchar(20) COLLATE 'utf8_unicode_ci' NULL, COMMENT='';

-- 12.09.2014
-- Extend user table
-- version: 2.2.3
ALTER TABLE `user` ADD `mobile_phone` varchar(20) COLLATE 'utf8_unicode_ci' NULL, COMMENT='';

-- 19.09.2014
-- Extend page / optimized header_title field type to TEXT
-- version: 2.2.4
ALTER TABLE `page` CHANGE `header_title` `header_title` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;
ALTER TABLE `optimized` CHANGE `header_title` `header_title` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

-- 19.09.2014
-- Extend page header_title field type to TEXT
-- version: 2.2.5
INSERT INTO `template_type` (`id`, `title`) VALUES ('type_partial_template', 'Partial template');

-- 12.09.2014
-- Extend user table
-- version: 2.2.6
 ALTER TABLE `form` ADD COLUMN `mobile` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL;
 ALTER TABLE `form` ADD COLUMN `enable_sms` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0';

 -- 29.09.2014
-- Add options quantity
-- version: 2.2.7
ALTER TABLE `page_option` ADD COLUMN `option_usage` ENUM('once', 'many') CHARACTER SET utf8 COLLATE utf8_unicode_ci DEFAULT 'many';
UPDATE `page_option` SET `option_usage`='once' WHERE `page_option`.`id` = 'option_member_landing';
UPDATE `page_option` SET `option_usage`='once' WHERE `page_option`.`id` = 'option_member_loginerror';
UPDATE `page_option` SET `option_usage`='once' WHERE `page_option`.`id` = 'option_member_signuplanding';
UPDATE `page_option` SET `option_usage`='once' WHERE `page_option`.`id` = 'option_search';


-- 21.11.2014
-- Rename titles for templates
-- version: 2.3.0
UPDATE `template_type` SET `title` = 'Regular' WHERE `id` = 'typeregular';
UPDATE `template_type` SET `title` = 'E-mail' WHERE `id` = 'typemail';


-- 05.01.2015
-- Change columnt type
-- version: 2.3.1
ALTER TABLE `page` CHANGE COLUMN `order` `order` int(10) unsigned DEFAULT NULL;


 -- 14.11.2014
-- Add external links to pages
-- version: 2.3.2
ALTER TABLE `page` ADD COLUMN `external_link_status` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0';
ALTER TABLE `page` ADD COLUMN `external_link` TEXT COLLATE utf8_unicode_ci DEFAULT NULL;

-- 21.05.2015
-- version: 2.4.0
-- update version

-- 05.08.2015
-- version: 2.4.1
-- Add page type
ALTER TABLE `page` ADD COLUMN `page_type` TINYINT(3) unsigned NOT NULL DEFAULT '1';
CREATE TABLE IF NOT EXISTS `page_types` (
  `page_type_id` TINYINT(3) unsigned NOT NULL,
  `page_type_name` VARCHAR(60),
  PRIMARY KEY (`page_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
INSERT INTO `page_types` (`page_type_id`, `page_type_name`)
VALUES ('1', 'page');

-- 23.10.2015
-- version: 2.4.2
-- Add page type
ALTER TABLE `user` ADD COLUMN `notes` TEXT COLLATE utf8_unicode_ci DEFAULT NULL;

-- 09/02/2015
-- version: 2.4.3

-- 31/05/2016
-- version: 2.5.0
ALTER TABLE `form` ADD COLUMN `admin_subject` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `form` ADD COLUMN `admin_mail_template` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `form` ADD COLUMN `admin_from` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `form` ADD COLUMN `admin_from_name` VARCHAR (255) DEFAULT NULL;
ALTER TABLE `form` ADD COLUMN `admin_text` TEXT DEFAULT NULL;

-- 23/09/2016
-- version: 2.5.1
-- Add timezone for users
ALTER TABLE `user` ADD COLUMN `timezone` VARCHAR(40) COLLATE utf8_unicode_ci DEFAULT NULL;

-- 03/01/2017
-- greCAPTCHA implement
-- version: 2.5.2
INSERT INTO `config` (`name`, `value`) VALUES
('grecaptchaPublicKey', '6LdZLBQUAAAAAGkmICdj_M7bsgYV68HgUAQzUi1o'),
('grecaptchaPrivateKey', '6LdZLBQUAAAAAPrpbakuqApNJlyonUsVN_bm_Pcx');


-- 20/04/2017
-- version: 2.5.3
-- Add mobile and phone masks table
CREATE TABLE IF NOT EXISTS `masks_list` (
  `country_code` CHAR(2) COLLATE utf8_unicode_ci NOT NULL,
  `mask_type` ENUM('mobile', 'desktop') DEFAULT 'mobile' NOT NULL,
  `mask_value` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
  `full_mask_value` VARCHAR(20) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`country_code`, `mask_type`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `masks_list` (`country_code`, `mask_type`, `mask_value`, `full_mask_value`) VALUES
('FR', 'mobile', '(999)999-999', '(999)999-999'),
('FR', 'desktop', '(999)999-999', '(999)999-999'),
('ES', 'mobile', '(999)999-999', '(999)999-999'),
('ES', 'desktop', '(999)999-999', '(999)999-999'),
('GB', 'mobile', '99-9999-9999', '99-9999-9999'),
('GB', 'desktop', '99-9999-9999', '99-9999-9999'),
('US', 'mobile', '(999)999-9999', '(999)999-9999'),
('US', 'desktop', '(999)999-9999', '(999)999-9999'),
('CA', 'mobile', '(999)999-9999', '(999)999-9999'),
('CA', 'desktop', '(999)999-9999', '(999)999-9999');

-- 17/05/2017
-- version: 2.5.4
-- Change column type for the code field in the forms table
ALTER TABLE `form` MODIFY COLUMN `code` mediumtext COLLATE utf8_unicode_ci NOT NULL;

-- 06/06/2017
-- version: 2.5.5
-- Add mobile and desktop phone country code
ALTER TABLE `user` ADD COLUMN `mobile_country_code` CHAR(2) COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN `mobile_country_code_value` VARCHAR(16) COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN `desktop_phone` VARCHAR(20) COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN `desktop_country_code` CHAR(2) COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN `desktop_country_code_value` VARCHAR(16) COLLATE utf8_unicode_ci DEFAULT NULL;

-- 16/06/2017
-- version: 2.5.6
-- Add invitation email
INSERT INTO `email_triggers` (`enabled`, `trigger_name`, `observer`) VALUES ('1',	't_userinvitation',	'Tools_Mail_SystemMailWatchdog');

-- 29/08/2017
-- version: 2.5.7
-- Add default timezone and mobile/desktop country code
INSERT IGNORE INTO `config` (`name`, `value`) VALUES ('userDefaultTimezone', 'America/New_York');
INSERT IGNORE INTO `config` (`name`, `value`) VALUES ('userDefaultPhoneMobileCode', 'US');

-- 07/09/2017
-- version: 2.5.8
-- Add signature field
ALTER TABLE `user` ADD COLUMN `signature` TEXT COLLATE utf8_unicode_ci DEFAULT NULL;

-- 11/09/2017
-- version: 2.5.9
-- Add subscribed field
ALTER TABLE `user` ADD COLUMN `subscribed` ENUM('0', '1') DEFAULT '0';

-- These alters are always the latest and updated version of the database
UPDATE `config` SET `value`='2.6.0' WHERE `name`='version';
SELECT value FROM `config` WHERE name = 'version';
