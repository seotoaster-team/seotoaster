-- 21/11/2013
-- Main menu template type
-- version: 2.1.0
DELETE FROM `template_type` WHERE `id` = 'typemenu';

-- 19/12/2013
-- User attributes table added
-- version: 2.1.1
DROP TABLE `user_attributes`;

-- 26/12/2013
-- SEO Intro fields for optimized table
-- version: 2.1.2
ALTER TABLE `optimized` DROP `seo_intro`, DROP `seo_intro_target`;

-- 29/01/2014
-- reCAPTCHA refactoring
-- version: 2.1.3
UPDATE `config` SET `name` = 'recapthaPublicKey' WHERE `name` = 'recaptchaPublicKey';
UPDATE `config` SET `name` = 'recapthaPrivateKey' WHERE `name` = 'recaptchaPrivateKey';

-- These alters are always the latest and updated version of the database
SELECT value FROM `config` WHERE name = 'version';
