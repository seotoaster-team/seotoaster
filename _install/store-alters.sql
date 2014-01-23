-- version: 2.1.0

-- version: 2.1.1
ALTER TABLE `shopping_cart_session` ADD COLUMN `discount_tax_rate` enum('0','1', '2', '3') COLLATE utf8_unicode_ci DEFAULT '0' AFTER `gateway`;
ALTER TABLE `shopping_cart_session` ADD COLUMN `shipping_tax` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Shipping Tax' AFTER `sub_total`;
ALTER TABLE `shopping_cart_session` ADD COLUMN `discount_tax` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Discount Tax' AFTER `shipping_tax`;
ALTER TABLE `shopping_cart_session` ADD COLUMN `sub_total_tax` decimal(10,2) NOT NULL DEFAULT '0.00' COMMENT 'Sub total Tax' AFTER `discount_tax`;

-- These alters are always the latest and updated version of the database
UPDATE `shopping_config` SET `value`='2.1.2' WHERE `name`='version';
SELECT value FROM `shopping_config` WHERE `name` = 'version';
