--21/11/2013
-- Main menu template type
-- version: 2.1.0
INSERT INTO `template_type` (`id`, `title`) VALUES ('typemenu', 'Menu');

-- These alters are always the latest and updated version of the database
UPDATE `config` SET `value`='2.1.1' WHERE `name`='version';
SELECT value FROM `config` WHERE name = 'version';
