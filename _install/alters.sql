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
('FR', 'mobile', '9 99 99 99 99', '9 99 99 99 99'),
('FR', 'desktop', '9 99 99 99 99', '9 99 99 99 99'),
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

-- 02/11/2017
-- version: 2.6.0
-- Add inlineEditor status 1 by default
UPDATE `config` SET `value` = '1' WHERE `name` = 'inlineEditor';

-- 30/11/2017
-- version: 2.6.1
-- Add phone masks
INSERT IGNORE INTO `masks_list` (`country_code`, `mask_type`, `mask_value`, `full_mask_value`) VALUES
('AC',	'mobile',	'9999',	'9999'),
('AC',	'desktop',	'9999',	'9999'),
('AD',	'mobile',	'999-999',	'999-999'),
('AD',	'desktop',	'999-999',	'999-999'),
('AE',	'mobile',	'59-999-9999',	'59-999-9999'),
('AE',	'desktop',	'59-999-9999',	'59-999-9999'),
('AF',	'mobile',	'99-999-9999',	'99-999-9999'),
('AF',	'desktop',	'99-999-9999',	'99-999-9999'),
('AG',	'mobile',	'(268)999-9999',	'(268)999-9999'),
('AG',	'desktop',	'(268)999-9999',	'(268)999-9999'),
('AI',	'mobile',	'(264)999-9999',	'(264)999-9999'),
('AI',	'desktop',	'(264)999-9999',	'(264)999-9999'),
('AL',	'mobile',	'(999)999-999',	'(999)999-999'),
('AL',	'desktop',	'(999)999-999',	'(999)999-999'),
('AM',	'mobile',	'99-999-999',	'99-999-999'),
('AM',	'desktop',	'99-999-999',	'99-999-999'),
('AN',	'mobile',	'999-9999',	'999-9999'),
('AN',	'desktop',	'999-9999',	'999-9999'),
('AO',	'mobile',	'(999)999-999',	'(999)999-999'),
('AO',	'desktop',	'(999)999-999',	'(999)999-999'),
('AQ',	'mobile',	'199-999',	'199-999'),
('AQ',	'desktop',	'199-999',	'199-999'),
('AR',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('AR',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('AS',	'mobile',	'(684)999-9999',	'(684)999-9999'),
('AS',	'desktop',	'(684)999-9999',	'(684)999-9999'),
('AT',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('AT',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('AU',	'mobile',	'9-9999-9999',	'9-9999-9999'),
('AU',	'desktop',	'9-9999-9999',	'9-9999-9999'),
('AW',	'mobile',	'999-9999',	'999-9999'),
('AW',	'desktop',	'999-9999',	'999-9999'),
('AZ',	'mobile',	'99-999-99-99',	'99-999-99-99'),
('AZ',	'desktop',	'99-999-99-99',	'99-999-99-99'),
('BA',	'mobile',	'99-99999',	'99-99999'),
('BA',	'desktop',	'99-99999',	'99-99999'),
('BB',	'mobile',	'(246)999-9999',	'(246)999-9999'),
('BB',	'desktop',	'(246)999-9999',	'(246)999-9999'),
('BD',	'mobile',	'99-999-999',	'99-999-999'),
('BD',	'desktop',	'99-999-999',	'99-999-999'),
('BE',	'mobile',	'9 99 99 99 99',	'9 99 99 99 99'),
('BE',	'desktop',	'9 99 99 99 99',	'9 99 99 99 99'),
('BF',	'mobile',	'99-99-9999',	'99-99-9999'),
('BF',	'desktop',	'99-99-9999',	'99-99-9999'),
('BG',	'mobile',	'(999)999-999',	'(999)999-999'),
('BG',	'desktop',	'(999)999-999',	'(999)999-999'),
('BH',	'mobile',	'9999-9999',	'9999-9999'),
('BH',	'desktop',	'9999-9999',	'9999-9999'),
('BI',	'mobile',	'99-99-9999',	'99-99-9999'),
('BI',	'desktop',	'99-99-9999',	'99-99-9999'),
('BJ',	'mobile',	'99-99-9999',	'99-99-9999'),
('BJ',	'desktop',	'99-99-9999',	'99-99-9999'),
('BM',	'mobile',	'(441)999-9999',	'(441)999-9999'),
('BM',	'desktop',	'(441)999-9999',	'(441)999-9999'),
('BN',	'mobile',	'999-9999',	'999-9999'),
('BN',	'desktop',	'999-9999',	'999-9999'),
('BO',	'mobile',	'9-999-9999',	'9-999-9999'),
('BO',	'desktop',	'9-999-9999',	'9-999-9999'),
('BR',	'mobile',	'(99)9999-9999',	'(99)9999-9999'),
('BR',	'desktop',	'(99)9999-9999',	'(99)9999-9999'),
('BS',	'mobile',	'(242)999-9999',	'(242)999-9999'),
('BS',	'desktop',	'(242)999-9999',	'(242)999-9999'),
('BT',	'mobile',	'17-999-999',	'17-999-999'),
('BT',	'desktop',	'17-999-999',	'17-999-999'),
('BW',	'mobile',	'99-999-999',	'99-999-999'),
('BW',	'desktop',	'99-999-999',	'99-999-999'),
('BY',	'mobile',	'(99)999-99-99',	'(99)999-99-99'),
('BY',	'desktop',	'(99)999-99-99',	'(99)999-99-99'),
('BZ',	'mobile',	'999-9999',	'999-9999'),
('BZ',	'desktop',	'999-9999',	'999-9999'),
('CD',	'mobile',	'(999)999-999',	'(999)999-999'),
('CD',	'desktop',	'(999)999-999',	'(999)999-999'),
('CF',	'mobile',	'99-99-9999',	'99-99-9999'),
('CF',	'desktop',	'99-99-9999',	'99-99-9999'),
('CG',	'mobile',	'99-999-9999',	'99-999-9999'),
('CG',	'desktop',	'99-999-9999',	'99-999-9999'),
('CH',	'mobile',	'99 999 99 99',	'99 999 99 99'),
('CH',	'desktop',	'99 999 99 99',	'99 999 99 99'),
('CI',	'mobile',	'99-999-999',	'99-999-999'),
('CI',	'desktop',	'99-999-999',	'99-999-999'),
('CK',	'mobile',	'99-999',	'99-999'),
('CK',	'desktop',	'99-999',	'99-999'),
('CL',	'mobile',	'9-9999-9999',	'9-9999-9999'),
('CL',	'desktop',	'9-9999-9999',	'9-9999-9999'),
('CM',	'mobile',	'9999-9999',	'9999-9999'),
('CM',	'desktop',	'9999-9999',	'9999-9999'),
('CN',	'mobile',	'(999)9999-9999',	'(999)9999-9999'),
('CN',	'desktop',	'(999)9999-9999',	'(999)9999-9999'),
('CO',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('CO',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('CR',	'mobile',	'9999-9999',	'9999-9999'),
('CR',	'desktop',	'9999-9999',	'9999-9999'),
('CU',	'mobile',	'9-999-9999',	'9-999-9999'),
('CU',	'desktop',	'9-999-9999',	'9-999-9999'),
('CV',	'mobile',	'(999)99-99',	'(999)99-99'),
('CV',	'desktop',	'(999)99-99',	'(999)99-99'),
('CW',	'mobile',	'999-9999',	'999-9999'),
('CW',	'desktop',	'999-9999',	'999-9999'),
('CY',	'mobile',	'99-999-999',	'99-999-999'),
('CY',	'desktop',	'99-999-999',	'99-999-999'),
('CZ',	'mobile',	'(999)999-999',	'(999)999-999'),
('CZ',	'desktop',	'(999)999-999',	'(999)999-999'),
('DE',	'mobile',	'(9999)999-9999',	'(9999)999-9999'),
('DE',	'desktop',	'(9999)999-9999',	'(9999)999-9999'),
('DJ',	'mobile',	'99-99-99-99',	'99-99-99-99'),
('DJ',	'desktop',	'99-99-99-99',	'99-99-99-99'),
('DK',	'mobile',	'99-99-99-99',	'99-99-99-99'),
('DK',	'desktop',	'99-99-99-99',	'99-99-99-99'),
('DM',	'mobile',	'(767)999-9999',	'(767)999-9999'),
('DM',	'desktop',	'(767)999-9999',	'(767)999-9999'),
('DO',	'mobile',	'(809)999-9999',	'(809)999-9999'),
('DO',	'desktop',	'(809)999-9999',	'(809)999-9999'),
('DZ',	'mobile',	'99-999-9999',	'99-999-9999'),
('DZ',	'desktop',	'99-999-9999',	'99-999-9999'),
('EC',	'mobile',	'99-999-9999',	'99-999-9999'),
('EC',	'desktop',	'99-999-9999',	'99-999-9999'),
('EE',	'mobile',	'9999-9999',	'9999-9999'),
('EE',	'desktop',	'9999-9999',	'9999-9999'),
('EG',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('EG',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('ER',	'mobile',	'9-999-999',	'9-999-999'),
('ER',	'desktop',	'9-999-999',	'9-999-999'),
('ET',	'mobile',	'99-999-9999',	'99-999-9999'),
('ET',	'desktop',	'99-999-9999',	'99-999-9999'),
('FI',	'mobile',	'(999)999-99-99',	'(999)999-99-99'),
('FI',	'desktop',	'(999)999-99-99',	'(999)999-99-99'),
('FJ',	'mobile',	'99-99999',	'99-99999'),
('FJ',	'desktop',	'99-99999',	'99-99999'),
('FK',	'mobile',	'99999',	'99999'),
('FK',	'desktop',	'99999',	'99999'),
('FM',	'mobile',	'999-9999',	'999-9999'),
('FM',	'desktop',	'999-9999',	'999-9999'),
('FO',	'mobile',	'999-999',	'999-999'),
('FO',	'desktop',	'999-999',	'999-999'),
('GA',	'mobile',	'9-99-99-99',	'9-99-99-99'),
('GA',	'desktop',	'9-99-99-99',	'9-99-99-99'),
('GD',	'mobile',	'(473)999-9999',	'(473)999-9999'),
('GD',	'desktop',	'(473)999-9999',	'(473)999-9999'),
('GE',	'mobile',	'(999)999-999',	'(999)999-999'),
('GE',	'desktop',	'(999)999-999',	'(999)999-999'),
('GF',	'mobile',	'99999-9999',	'99999-9999'),
('GF',	'desktop',	'99999-9999',	'99999-9999'),
('GH',	'mobile',	'(999)999-999',	'(999)999-999'),
('GH',	'desktop',	'(999)999-999',	'(999)999-999'),
('GI',	'mobile',	'999-99999',	'999-99999'),
('GI',	'desktop',	'999-99999',	'999-99999'),
('GL',	'mobile',	'99-99-99',	'99-99-99'),
('GL',	'desktop',	'99-99-99',	'99-99-99'),
('GM',	'mobile',	'(999)99-99',	'(999)99-99'),
('GM',	'desktop',	'(999)99-99',	'(999)99-99'),
('GN',	'mobile',	'99-999-999',	'99-999-999'),
('GN',	'desktop',	'99-999-999',	'99-999-999'),
('GQ',	'mobile',	'99-999-9999',	'99-999-9999'),
('GQ',	'desktop',	'99-999-9999',	'99-999-9999'),
('GR',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('GR',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('GT',	'mobile',	'9-999-9999',	'9-999-9999'),
('GT',	'desktop',	'9-999-9999',	'9-999-9999'),
('GU',	'mobile',	'(671)999-9999',	'(671)999-9999'),
('GU',	'desktop',	'(671)999-9999',	'(671)999-9999'),
('GW',	'mobile',	'9-999999',	'9-999999'),
('GW',	'desktop',	'9-999999',	'9-999999'),
('GY',	'mobile',	'999-9999',	'999-9999'),
('GY',	'desktop',	'999-9999',	'999-9999'),
('HK',	'mobile',	'9999-9999',	'9999-9999'),
('HK',	'desktop',	'9999-9999',	'9999-9999'),
('HN',	'mobile',	'9999-9999',	'9999-9999'),
('HN',	'desktop',	'9999-9999',	'9999-9999'),
('HR',	'mobile',	'99-999-999',	'99-999-999'),
('HR',	'desktop',	'99-999-999',	'99-999-999'),
('HT',	'mobile',	'99-99-9999',	'99-99-9999'),
('HT',	'desktop',	'99-99-9999',	'99-99-9999'),
('HU',	'mobile',	'(999)999-999',	'(999)999-999'),
('HU',	'desktop',	'(999)999-999',	'(999)999-999'),
('ID',	'mobile',	'(899)999-9999',	'(899)999-9999'),
('ID',	'desktop',	'(899)999-9999',	'(899)999-9999'),
('IE',	'mobile',	'(999)999-999',	'(999)999-999'),
('IE',	'desktop',	'(999)999-999',	'(999)999-999'),
('IL',	'mobile',	'59-999-9999',	'59-999-9999'),
('IL',	'desktop',	'59-999-9999',	'59-999-9999'),
('IN',	'mobile',	'(9999)999-999',	'(9999)999-999'),
('IN',	'desktop',	'(9999)999-999',	'(9999)999-999'),
('IO',	'mobile',	'999-9999',	'999-9999'),
('IO',	'desktop',	'999-9999',	'999-9999'),
('IQ',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('IQ',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('IR',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('IR',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('IS',	'mobile',	'999-9999',	'999-9999'),
('IS',	'desktop',	'999-9999',	'999-9999'),
('IT',	'mobile',	'(999)9999-999',	'(999)9999-999'),
('IT',	'desktop',	'(999)9999-999',	'(999)9999-999'),
('JM',	'mobile',	'(876)999-9999',	'(876)999-9999'),
('JM',	'desktop',	'(876)999-9999',	'(876)999-9999'),
('JO',	'mobile',	'9-9999-9999',	'9-9999-9999'),
('JO',	'desktop',	'9-9999-9999',	'9-9999-9999'),
('JP',	'mobile',	'99-9999-9999',	'99-9999-9999'),
('JP',	'desktop',	'99-9999-9999',	'99-9999-9999'),
('KE',	'mobile',	'999-999999',	'999-999999'),
('KE',	'desktop',	'999-999999',	'999-999999'),
('KG',	'mobile',	'(999)999-999',	'(999)999-999'),
('KG',	'desktop',	'(999)999-999',	'(999)999-999'),
('KH',	'mobile',	'99-999-999',	'99-999-999'),
('KH',	'desktop',	'99-999-999',	'99-999-999'),
('KI',	'mobile',	'99-999',	'99-999'),
('KI',	'desktop',	'99-999',	'99-999'),
('KM',	'mobile',	'99-99999',	'99-99999'),
('KM',	'desktop',	'99-99999',	'99-99999'),
('KN',	'mobile',	'(869)999-9999',	'(869)999-9999'),
('KN',	'desktop',	'(869)999-9999',	'(869)999-9999'),
('KP',	'mobile',	'191-999-9999',	'191-999-9999'),
('KP',	'desktop',	'191-999-9999',	'191-999-9999'),
('KR',	'mobile',	'99-999-9999',	'99-999-9999'),
('KR',	'desktop',	'99-999-9999',	'99-999-9999'),
('KW',	'mobile',	'9999-9999',	'9999-9999'),
('KW',	'desktop',	'9999-9999',	'9999-9999'),
('KY',	'mobile',	'(345)999-9999',	'(345)999-9999'),
('KY',	'desktop',	'(345)999-9999',	'(345)999-9999'),
('KZ',	'mobile',	'(699)999-99-99',	'(699)999-99-99'),
('KZ',	'desktop',	'(699)999-99-99',	'(699)999-99-99'),
('LA',	'mobile',	'(2099)999-999',	'(2099)999-999'),
('LA',	'desktop',	'(2099)999-999',	'(2099)999-999'),
('LB',	'mobile',	'99-999-999',	'99-999-999'),
('LB',	'desktop',	'99-999-999',	'99-999-999'),
('LC',	'mobile',	'999-9999',	'999-9999'),
('LC',	'desktop',	'999-9999',	'999-9999'),
('LI',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('LI',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('LK',	'mobile',	'99-999-9999',	'99-999-9999'),
('LK',	'desktop',	'99-999-9999',	'99-999-9999'),
('LR',	'mobile',	'99-999-999',	'99-999-999'),
('LR',	'desktop',	'99-999-999',	'99-999-999'),
('LS',	'mobile',	'9-999-9999',	'9-999-9999'),
('LS',	'desktop',	'9-999-9999',	'9-999-9999'),
('LT',	'mobile',	'(999)99-999',	'(999)99-999'),
('LT',	'desktop',	'(999)99-999',	'(999)99-999'),
('LU',	'mobile',	'(999)999-999',	'(999)999-999'),
('LU',	'desktop',	'(999)999-999',	'(999)999-999'),
('LV',	'mobile',	'99-999-999',	'99-999-999'),
('LV',	'desktop',	'99-999-999',	'99-999-999'),
('LY',	'mobile',	'99-999-999',	'99-999-999'),
('LY',	'desktop',	'99-999-999',	'99-999-999'),
('MA',	'mobile',	'99-9999-999',	'99-9999-999'),
('MA',	'desktop',	'99-9999-999',	'99-9999-999'),
('MC',	'mobile',	'99 99 99 99',	'99 99 99 99'),
('MC',	'desktop',	'99 99 99 99',	'99 99 99 99'),
('MD',	'mobile',	'9999-9999',	'9999-9999'),
('MD',	'desktop',	'9999-9999',	'9999-9999'),
('ME',	'mobile',	'99-999-999',	'99-999-999'),
('ME',	'desktop',	'99-999-999',	'99-999-999'),
('MG',	'mobile',	'99-99-99999',	'99-99-99999'),
('MG',	'desktop',	'99-99-99999',	'99-99-99999'),
('MH',	'mobile',	'999-9999',	'999-9999'),
('MH',	'desktop',	'999-9999',	'999-9999'),
('MK',	'mobile',	'99-999-999',	'99-999-999'),
('MK',	'desktop',	'99-999-999',	'99-999-999'),
('ML',	'mobile',	'99-99-9999',	'99-99-9999'),
('ML',	'desktop',	'99-99-9999',	'99-99-9999'),
('MM',	'mobile',	'99-999-999',	'99-999-999'),
('MM',	'desktop',	'99-999-999',	'99-999-999'),
('MN',	'mobile',	'99-99-9999',	'99-99-9999'),
('MN',	'desktop',	'99-99-9999',	'99-99-9999'),
('MO',	'mobile',	'9999-9999',	'9999-9999'),
('MO',	'desktop',	'9999-9999',	'9999-9999'),
('MP',	'mobile',	'999-9999',	'999-9999'),
('MP',	'desktop',	'999-9999',	'999-9999'),
('MQ',	'mobile',	'(999)99-99-99',	'(999)99-99-99'),
('MQ',	'desktop',	'(999)99-99-99',	'(999)99-99-99'),
('MR',	'mobile',	'99-99-9999',	'99-99-9999'),
('MR',	'desktop',	'99-99-9999',	'99-99-9999'),
('MS',	'mobile',	'999-9999',	'999-9999'),
('MS',	'desktop',	'999-9999',	'999-9999'),
('MT',	'mobile',	'9999-9999',	'9999-9999'),
('MT',	'desktop',	'9999-9999',	'9999-9999'),
('MU',	'mobile',	'999-9999',	'999-9999'),
('MU',	'desktop',	'999-9999',	'999-9999'),
('MV',	'mobile',	'999-9999',	'999-9999'),
('MV',	'desktop',	'999-9999',	'999-9999'),
('MW',	'mobile',	'999-999',	'999-999'),
('MW',	'desktop',	'999-999',	'999-999'),
('MX',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('MX',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('MY',	'mobile',	'99-999-9999',	'99-999-9999'),
('MY',	'desktop',	'99-999-9999',	'99-999-9999'),
('MZ',	'mobile',	'99-999-999',	'99-999-999'),
('MZ',	'desktop',	'99-999-999',	'99-999-999'),
('NA',	'mobile',	'99-999-9999',	'99-999-9999'),
('NA',	'desktop',	'99-999-9999',	'99-999-9999'),
('NC',	'mobile',	'99-9999',	'99-9999'),
('NC',	'desktop',	'99-9999',	'99-9999'),
('NE',	'mobile',	'99-99-9999',	'99-99-9999'),
('NE',	'desktop',	'99-99-9999',	'99-99-9999'),
('NF',	'mobile',	'99-999',	'99-999'),
('NF',	'desktop',	'99-999',	'99-999'),
('NG',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('NG',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('NI',	'mobile',	'9999-9999',	'9999-9999'),
('NI',	'desktop',	'9999-9999',	'9999-9999'),
('NL',	'mobile',	'99-999-9999',	'99-999-9999'),
('NL',	'desktop',	'99-999-9999',	'99-999-9999'),
('NO',	'mobile',	'(999)99-999',	'(999)99-999'),
('NO',	'desktop',	'(999)99-999',	'(999)99-999'),
('NP',	'mobile',	'99-999-999',	'99-999-999'),
('NP',	'desktop',	'99-999-999',	'99-999-999'),
('NR',	'mobile',	'999-9999',	'999-9999'),
('NR',	'desktop',	'999-9999',	'999-9999'),
('NU',	'mobile',	'9999',	'9999'),
('NU',	'desktop',	'9999',	'9999'),
('NZ',	'mobile',	'(999)999-999',	'(999)999-999'),
('NZ',	'desktop',	'(999)999-999',	'(999)999-999'),
('OM',	'mobile',	'99-999-999',	'99-999-999'),
('OM',	'desktop',	'99-999-999',	'99-999-999'),
('PA',	'mobile',	'999-9999',	'999-9999'),
('PA',	'desktop',	'999-9999',	'999-9999'),
('PE',	'mobile',	'(999)999-999',	'(999)999-999'),
('PE',	'desktop',	'(999)999-999',	'(999)999-999'),
('PF',	'mobile',	'99-99-99',	'99-99-99'),
('PF',	'desktop',	'99-99-99',	'99-99-99'),
('PG',	'mobile',	'(999)99-999',	'(999)99-999'),
('PG',	'desktop',	'(999)99-999',	'(999)99-999'),
('PH',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('PH',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('PK',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('PK',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('PL',	'mobile',	'(999)999-999',	'(999)999-999'),
('PL',	'desktop',	'(999)999-999',	'(999)999-999'),
('PS',	'mobile',	'99-999-9999',	'99-999-9999'),
('PS',	'desktop',	'99-999-9999',	'99-999-9999'),
('PT',	'mobile',	'99-999-9999',	'99-999-9999'),
('PT',	'desktop',	'99-999-9999',	'99-999-9999'),
('PW',	'mobile',	'999-9999',	'999-9999'),
('PW',	'desktop',	'999-9999',	'999-9999'),
('PY',	'mobile',	'(999)999-999',	'(999)999-999'),
('PY',	'desktop',	'(999)999-999',	'(999)999-999'),
('QA',	'mobile',	'9999-9999',	'9999-9999'),
('QA',	'desktop',	'9999-9999',	'9999-9999'),
('RE',	'mobile',	'99999-9999',	'99999-9999'),
('RE',	'desktop',	'99999-9999',	'99999-9999'),
('RO',	'mobile',	'99-999-9999',	'99-999-9999'),
('RO',	'desktop',	'99-999-9999',	'99-999-9999'),
('RS',	'mobile',	'99-999-9999',	'99-999-9999'),
('RS',	'desktop',	'99-999-9999',	'99-999-9999'),
('RU',	'mobile',	'(999)999-99-99',	'(999)999-99-99'),
('RU',	'desktop',	'(999)999-99-99',	'(999)999-99-99'),
('RW',	'mobile',	'(999)999-999',	'(999)999-999'),
('RW',	'desktop',	'(999)999-999',	'(999)999-999'),
('SA',	'mobile',	'9999-9999',	'9999-9999'),
('SA',	'desktop',	'9999-9999',	'9999-9999'),
('SB',	'mobile',	'999-9999',	'999-9999'),
('SB',	'desktop',	'999-9999',	'999-9999'),
('SC',	'mobile',	'9-999-999',	'9-999-999'),
('SC',	'desktop',	'9-999-999',	'9-999-999'),
('SD',	'mobile',	'99-999-9999',	'99-999-9999'),
('SD',	'desktop',	'99-999-9999',	'99-999-9999'),
('SE',	'mobile',	'99-999-9999',	'99-999-9999'),
('SE',	'desktop',	'99-999-9999',	'99-999-9999'),
('SG',	'mobile',	'9999-9999',	'9999-9999'),
('SG',	'desktop',	'9999-9999',	'9999-9999'),
('SH',	'mobile',	'9999',	'9999'),
('SH',	'desktop',	'9999',	'9999'),
('SI',	'mobile',	'99-999-999',	'99-999-999'),
('SI',	'desktop',	'99-999-999',	'99-999-999'),
('SK',	'mobile',	'(999)999-999',	'(999)999-999'),
('SK',	'desktop',	'(999)999-999',	'(999)999-999'),
('SL',	'mobile',	'99-999999',	'99-999999'),
('SL',	'desktop',	'99-999999',	'99-999999'),
('SM',	'mobile',	'9999-999999',	'9999-999999'),
('SM',	'desktop',	'9999-999999',	'9999-999999'),
('SN',	'mobile',	'99-999-9999',	'99-999-9999'),
('SN',	'desktop',	'99-999-9999',	'99-999-9999'),
('SO',	'mobile',	'99-999-999',	'99-999-999'),
('SO',	'desktop',	'99-999-999',	'99-999-999'),
('SR',	'mobile',	'999-9999',	'999-9999'),
('SR',	'desktop',	'999-9999',	'999-9999'),
('SS',	'mobile',	'99-999-9999',	'99-999-9999'),
('SS',	'desktop',	'99-999-9999',	'99-999-9999'),
('ST',	'mobile',	'99-99999',	'99-99999'),
('ST',	'desktop',	'99-99999',	'99-99999'),
('SV',	'mobile',	'99-99-9999',	'99-99-9999'),
('SV',	'desktop',	'99-99-9999',	'99-99-9999'),
('SX',	'mobile',	'999-9999',	'999-9999'),
('SX',	'desktop',	'999-9999',	'999-9999'),
('SY',	'mobile',	'99-9999-999',	'99-9999-999'),
('SY',	'desktop',	'99-9999-999',	'99-9999-999'),
('SZ',	'mobile',	'99-99-9999',	'99-99-9999'),
('SZ',	'desktop',	'99-99-9999',	'99-99-9999'),
('TC',	'mobile',	'999-9999',	'999-9999'),
('TC',	'desktop',	'999-9999',	'999-9999'),
('TD',	'mobile',	'99-99-99-99',	'99-99-99-99'),
('TD',	'desktop',	'99-99-99-99',	'99-99-99-99'),
('TG',	'mobile',	'99-999-999',	'99-999-999'),
('TG',	'desktop',	'99-999-999',	'99-999-999'),
('TH',	'mobile',	'99-999-9999',	'99-999-9999'),
('TH',	'desktop',	'99-999-9999',	'99-999-9999'),
('TJ',	'mobile',	'99-999-9999',	'99-999-9999'),
('TJ',	'desktop',	'99-999-9999',	'99-999-9999'),
('TK',	'mobile',	'9999',	'9999'),
('TK',	'desktop',	'9999',	'9999'),
('TL',	'mobile',	'999-9999',	'999-9999'),
('TL',	'desktop',	'999-9999',	'999-9999'),
('TM',	'mobile',	'9-999-9999',	'9-999-9999'),
('TM',	'desktop',	'9-999-9999',	'9-999-9999'),
('TN',	'mobile',	'99-999-999',	'99-999-999'),
('TN',	'desktop',	'99-999-999',	'99-999-999'),
('TO',	'mobile',	'99999',	'99999'),
('TO',	'desktop',	'99999',	'99999'),
('TR',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('TR',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('TT',	'mobile',	'999-9999',	'999-9999'),
('TT',	'desktop',	'999-9999',	'999-9999'),
('TV',	'mobile',	'9999',	'9999'),
('TV',	'desktop',	'9999',	'9999'),
('TW',	'mobile',	'9-9999-9999',	'9-9999-9999'),
('TW',	'desktop',	'9-9999-9999',	'9-9999-9999'),
('TZ',	'mobile',	'99-999-9999',	'99-999-9999'),
('TZ',	'desktop',	'99-999-9999',	'99-999-9999'),
('UA',	'mobile',	'(99)999-99-99',	'(99)999-99-99'),
('UA',	'desktop',	'(99)999-99-99',	'(99)999-99-99'),
('UG',	'mobile',	'(999)999-999',	'(999)999-999'),
('UG',	'desktop',	'(999)999-999',	'(999)999-999'),
('UY',	'mobile',	'9-999-99-99',	'9-999-99-99'),
('UY',	'desktop',	'9-999-99-99',	'9-999-99-99'),
('UZ',	'mobile',	'99-999-9999',	'99-999-9999'),
('UZ',	'desktop',	'99-999-9999',	'99-999-9999'),
('VA',	'mobile',	'99999',	'99999'),
('VA',	'desktop',	'99999',	'99999'),
('VC',	'mobile',	'999-9999',	'999-9999'),
('VC',	'desktop',	'999-9999',	'999-9999'),
('VE',	'mobile',	'(999)999-9999',	'(999)999-9999'),
('VE',	'desktop',	'(999)999-9999',	'(999)999-9999'),
('VG',	'mobile',	'999-9999',	'999-9999'),
('VG',	'desktop',	'999-9999',	'999-9999'),
('VI',	'mobile',	'999-9999',	'999-9999'),
('VI',	'desktop',	'999-9999',	'999-9999'),
('VN',	'mobile',	'99-9999-999',	'99-9999-999'),
('VN',	'desktop',	'99-9999-999',	'99-9999-999'),
('VU',	'mobile',	'99-99999',	'99-99999'),
('VU',	'desktop',	'99-99999',	'99-99999'),
('WF',	'mobile',	'99-9999',	'99-9999'),
('WF',	'desktop',	'99-9999',	'99-9999'),
('WS',	'mobile',	'99-9999',	'99-9999'),
('WS',	'desktop',	'99-9999',	'99-9999'),
('YE',	'mobile',	'999-999-999',	'999-999-999'),
('YE',	'desktop',	'999-999-999',	'999-999-999'),
('ZA',	'mobile',	'99-999-9999',	'99-999-9999'),
('ZA',	'desktop',	'99-999-9999',	'99-999-9999'),
('ZM',	'mobile',	'99-999-9999',	'99-999-9999'),
('ZM',	'desktop',	'99-999-9999',	'99-999-9999'),
('ZW',	'mobile',	'9-999999',	'9-999999'),
('ZW',	'desktop',	'9-999999',	'9-999999');

-- 15/02/2018
-- version: 2.6.2
-- Add new template type 'type_fa_template'
INSERT IGNORE INTO `template_type` (`id`, `title`) VALUES ('type_fa_template', 'Featuredarea Templates');

-- 17/05/2018
-- version: 2.6.3
-- Add new voip phone column
ALTER TABLE `user` ADD COLUMN `voip_phone` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL;

-- 17/05/2018
-- version: 2.6.5

-- 12/07/2018
-- version: 3.0.1
INSERT IGNORE INTO `config` (`name`, `value`)
SELECT 'enableMinifyCss', `value` FROM `config` WHERE `name` = 'enableMinify';
INSERT IGNORE INTO `config` (`name`, `value`)
SELECT 'enableMinifyJs', `value` FROM `config` WHERE `name` = 'enableMinify';

-- 31/07/2018
-- version: 3.0.2
-- Add new prefix column
ALTER TABLE `user` ADD COLUMN `prefix` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL AFTER `email`;

-- 19/09/2018
-- version: 3.0.3
-- Add new reply_email column
ALTER TABLE `form` ADD COLUMN `reply_email` enum('0','1') COLLATE utf8_unicode_ci DEFAULT '0' AFTER `admin_text`;

-- 10.11.2015
-- version: 3.0.4
-- Add page type access
DROP TABLE IF EXISTS `page_types_access`;
CREATE TABLE `page_types_access` (
  `page_type_id` TINYINT(3) unsigned NOT NULL,
  `resource_type` VARCHAR(60),
  PRIMARY KEY (`page_type_id`, `resource_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

INSERT INTO `page_types_access` (`page_type_id`, `resource_type`) VALUES
('1', 'list_pages'),
('1', 'link_list'),
('1', 'organize_pages'),
('1', 'seo_pages'),
('2', 'seo_pages'),
('3', 'seo_pages'),
('1', 'sitemap_pages'),
('2', 'sitemap_pages'),
('3', 'sitemap_pages');

-- 30/10/18
-- version: 3.0.5
INSERT INTO `page_option` (`id`, `title`, `context`, `active`, `option_usage`) VALUES
  ('option_adminredirect',	'Page where superadmin will be redirected after login',	'Redirect',	1,	'once');

-- 11/09/2018
-- version: 3.0.6
-- Add new exclude_category column
ALTER TABLE `page` ADD COLUMN `exclude_category` enum('0','1') COLLATE utf8_unicode_ci NOT NULL DEFAULT '0' AFTER `page_type`;

-- 23/01/2019
-- version: 3.0.7
-- Add form blacklist rules
CREATE TABLE `form_blacklist_rules` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `type` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`type`,`value`),
  UNIQUE (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- 20/03/2019
-- version: 3.0.8
-- Add crop new format
INSERT IGNORE INTO `config` (`name`, `value`) VALUES ('cropNewFormat', '0');

-- 12/06/2019
-- version: 3.0.9
-- Add optimizedNotifications param. value (email1,email2,...)
INSERT IGNORE INTO `config` (`name`, `value`) VALUES ('optimizedNotifications', '');

-- 07/06/2017
-- version: 3.1.0
-- Add subfolders support
CREATE TABLE IF NOT EXISTS `page_folder` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `index_page` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `index_page` (`index_page`),
  CONSTRAINT `page_folder_ibfk_4` FOREIGN KEY (`index_page`) REFERENCES `page` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

ALTER TABLE `page`
  ADD `page_folder` varchar(255) NULL,
  ADD `is_folder_index` enum('0','1') NOT NULL DEFAULT '0' AFTER `page_folder`;

ALTER TABLE `page`
  ADD FOREIGN KEY (`page_folder`) REFERENCES `page_folder` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;

-- 07/10/2019
-- version: 3.1.1
-- Add users remote authorization
ALTER TABLE `user` ADD COLUMN `allow_remote_authorization` ENUM('1', '0') DEFAULT '0' NOT NULL;
ALTER TABLE `user` ADD COLUMN `remote_authorization_info` TEXT COLLATE utf8_unicode_ci DEFAULT NULL COMMENT 'additional info';
ALTER TABLE `user` ADD COLUMN `remote_authorization_token` CHAR(40) DEFAULT NULL;

-- 07/10/2019
-- version: 3.1.2
-- Add users remote authorization
UPDATE `masks_list` SET `mask_value` = '99-9999-9999?9' WHERE `country_code` = 'GB';
UPDATE `masks_list` SET `full_mask_value` = '99-9999-9999?9' WHERE `country_code` = 'GB';
UPDATE `masks_list` SET `mask_value` = '9 99 99 99 99?9' WHERE `country_code` = 'FR';
UPDATE `masks_list` SET `full_mask_value` = '9 99 99 99 99?9' WHERE `country_code` = 'FR';

-- 20/07/2020
-- version: 3.1.3
-- Pre package version

-- 01/03/2021
-- version: 3.2.0
ALTER TABLE `config` CHANGE `value` `value` TEXT COLLATE utf8_unicode_ci NOT NULL;

-- 09/03/2020
-- version: 3.2.1
-- Pre package version

-- 07/07/2021
-- version: 3.3.0
ALTER TABLE `user` ADD COLUMN `personal_calendar_url` TEXT COLLATE utf8_unicode_ci DEFAULT NULL;
ALTER TABLE `user` ADD COLUMN `avatar_link` TEXT COLLATE utf8_unicode_ci DEFAULT NULL;

-- 05/08/2021
-- version: 3.3.1
-- Pre package version

-- 13/08/2021
-- version: 3.4.0
INSERT IGNORE INTO `config` (`name`, `value`) VALUES ('wraplinks', '0');

-- 10/06/2022
-- version: 3.4.1

-- These alters are always the latest and updated version of the database
UPDATE `config` SET `value`='3.4.2' WHERE `name`='version';
SELECT value FROM `config` WHERE name = 'version';
