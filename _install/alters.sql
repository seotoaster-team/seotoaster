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

INSERT INTO `email_triggers` (`id`, `enabled`, `trigger_name`, `observer`) VALUES
(null, '1', 'store_neworder', 'Tools_AppsSmsWatchdog'),
(null, '1', 'store_trackingnumber', 'Tools_AppsSmsWatchdog');

INSERT INTO `email_triggers_actions` (`id`, `service`, `trigger`, `template`, `recipient`, `message`, `from`, `subject`) VALUES (NULL, 'sms', 'store_neworder', NULL, 'customer', 'Hello {customer:fullname},
this message is from your favorite store {company:name}.
We received your order on {order:createdat} date for {order:total}.
Your order status is {order:status} and will ship to {order:shippingaddress}.
Thanks for your business.', '', '');

INSERT INTO `email_triggers_actions` (`id`, `service`, `trigger`, `template`, `recipient`, `message`, `from`, `subject`) VALUES (NULL, 'sms', 'store_trackingnumber', NULL, 'customer', 'Hello {customer:fullname},
this message is from your favorite store {company:name}.
Your order {order:shippingtrackingid} for {order:total} placed on {order:createdat} is now {order:status}.
The shipping address for this order is {order:shippingaddress}
Thanks for your business.', '', '');


-- 29.08.2014
-- Extend plugin table
-- version: 2.2.2
ALTER TABLE `plugin` ADD `version` varchar(20) COLLATE 'utf8_unicode_ci' NULL, COMMENT='';

-- 12.09.2014
-- Extend user table
-- version: 2.2.3
ALTER TABLE `user` ADD `mobile_phone` varchar(20) COLLATE 'utf8_unicode_ci' NULL, COMMENT='';

-- 19.09.2014
-- Extend page header_title field type to TEXT
-- version 2.2.4
ALTER TABLE `page` CHANGE `header_title` `header_title` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;

-- 19.09.2014
-- Extend page header_title field type to TEXT
-- version 2.2.5
INSERT INTO `seotoaster`.`template_type` (`id`, `title`) VALUES ('type_partial_template', 'Partial template');

-- These alters are always the latest and updated version of the database
UPDATE `config` SET `value`='2.2.6' WHERE `name`='version';
SELECT value FROM `config` WHERE name = 'version';