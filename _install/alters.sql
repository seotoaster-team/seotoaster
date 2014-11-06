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

-- 30.09.2014
-- Update DB version
-- version: 2.2.8

-- These alters are always the latest and updated version of the database

UPDATE `config` SET `value`='2.3.0' WHERE `name`='version';
SELECT value FROM `config` WHERE name = 'version';

