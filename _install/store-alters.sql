-- version: 2.1.1

-- These alters are always the latest and updated version of the database
UPDATE `shopping_config` SET `value`='2.1.1' WHERE `name`='version';
SELECT value FROM `shopping_config` WHERE `name` = 'version';
