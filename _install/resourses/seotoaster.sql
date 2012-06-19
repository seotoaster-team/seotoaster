SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `name` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `config`
--

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
('inlineEditor', '0'),

-- --------------------------------------------------------

--
-- Table structure for table `container`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `deeplink`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `email_triggers`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `email_triggers_actions`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `email_triggers_recipient`
--

CREATE TABLE IF NOT EXISTS `email_triggers_recipient` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recipient` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Recipient Name',
  PRIMARY KEY (`id`),
  KEY `recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `featured_area`
--

CREATE TABLE IF NOT EXISTS `featured_area` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(164) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `form`
--

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
  `tracking_code` text COLLATE utf8_unicode_ci NOT NULL,
  `reply_from_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `link_container`
--

CREATE TABLE IF NOT EXISTS `link_container` (
  `id_container` int(10) unsigned NOT NULL,
  `link` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id_container`,`link`),
  KEY `indContainerId` (`id_container`),
  KEY `indLink` (`link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `news_category`
--

CREATE TABLE IF NOT EXISTS `news_category` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `news_rel_category`
--

CREATE TABLE IF NOT EXISTS `news_rel_category` (
  `news_id` int(10) unsigned NOT NULL,
  `category_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`news_id`,`category_id`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `observers_queue`
--

CREATE TABLE IF NOT EXISTS `observers_queue` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `observable` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Observable Class Name',
  `observer` text COLLATE utf8_unicode_ci NOT NULL COMMENT 'Observer Class Name',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `optimized`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `page`
--

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

--
-- Dumping data for table `page`
--

INSERT INTO `page` (`id`, `template_id`, `parent_id`, `nav_name`, `meta_description`, `meta_keywords`, `header_title`, `h1`, `url`, `teaser_text`, `last_update`, `is_404page`, `show_in_menu`, `order`, `weight`, `silo_id`, `targeted_key_phrase`, `protected`, `system`, `draft`, `publish_at`, `news`, `err_login_landing`, `mem_landing`, `signup_landing`, `checkout`) VALUES
(1, 'index', 0, 'Home', '', '', 'Home', 'Home', 'index.html', '', '2012-05-25 15:47:28', '0', '1', 0, 0, NULL, 'Home', '0', '0', '0', NULL, '0', '0', '0', '0', '0');

-- --------------------------------------------------------

--
-- Table structure for table `page_fa`
--

CREATE TABLE IF NOT EXISTS `page_fa` (
  `page_id` int(10) unsigned NOT NULL,
  `fa_id` int(10) unsigned NOT NULL,
  `order` int(10) unsigned NOT NULL,
  PRIMARY KEY (`page_id`,`fa_id`),
  KEY `indPageId` (`page_id`),
  KEY `indFaId` (`fa_id`),
  KEY `indOrder` (`order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_log`
--

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

-- --------------------------------------------------------

--
-- Table structure for table `plugin`
--

CREATE TABLE IF NOT EXISTS `plugin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `status` enum('enabled','disabled') COLLATE utf8_unicode_ci DEFAULT 'disabled',
  `tags` text COLLATE utf8_unicode_ci COMMENT 'Comma separated words',
  `license` blob NOT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`),
  KEY `indStatus` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;


-- --------------------------------------------------------

--
-- Table structure for table `redirect`
--

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
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `seo_data`
--

CREATE TABLE IF NOT EXISTS `seo_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `seo_top` longtext COLLATE utf8_unicode_ci,
  `seo_bottom` longtext COLLATE utf8_unicode_ci,
  `seo_head` longtext COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `silo`
--

CREATE TABLE IF NOT EXISTS `silo` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `indName` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `template`
--

CREATE TABLE IF NOT EXISTS `template` (
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`name`),
  KEY `type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `template`
--

INSERT INTO `template` (`name`, `content`, `type`) VALUES
('category', '<!DOCTYPE html>\n<html lang="en">\n<head>\n<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"  />\n<title>{$page:title}</title>\n<meta name="keywords" content="{$meta:keywords}" />\n<meta name="description" content="{$meta:description}" />\n<meta name="generator" content="seotoaster" />\n\n<link href="reset.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="style.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="content.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="nav.css" rel="stylesheet"  type="text/css" media="screen" />\n<link href="products.css" rel="stylesheet"  type="text/css" media="screen" />\n\n{$concatcss}\n<!--[if IE]>\n<script src="html5.js" type="text/javascript"></script>\n<![endif]-->\n{$seo:top}\n</head>\n\n<body>\n<div id="container" class="container_12">\n\n<!-- Start of the header -->\n  <header class="grid_12 mb10px header">\n  <div class=''grid_8 alpha''>\n     <a href="{$website:url}" title="" class="logo">\n      <img src="images/logo-small.jpg" width="112" height="130" alt="">\n     </a>\n   </div>\n    <!-- end of logo div -->    \n \n   <div class=''grid_4 omega''>\n     <!--{$cart}--> \n\n    <form id="search-form" class="grid_3" action="{$website:url}/sys/backend_search/search" method="post">\n      <input id="searchtext" name="searchtext" type="text" onfocus="value=''''" value="Search" />\n      <input type="submit" value="Search" />\n      <input id="pageUrl" style="display:none;" name="pageUrl" type="hidden" value="search-results.html" />\n    </form>\n  \n  </div>\n     \n    <div class="clear"></div>\n    {$menu:flat}     \n  </header>\n<!-- end of the header -->\n\n<!-- Left Column -->     \n  <div id="left" class="grid_3">\n    <nav> {$menu:main} </nav>\n    {$content:left:static}\n  </div>\n\n<!-- Main Column -->    \n  <div id="content" class="grid_6">\n    <h2>{$header:content}</h2>\n    {$content:content1}\n    {$content:content2} \n  </div>\n  \n \n<!-- Right Column --> \n  <div id="right" class="grid_3">\n    <h2>{$header:right1:static}</h2>\n    {$content:right1:static}\n    <div class="separator"></div>\n    <h2>{$header:right2}</h2>\n    {$content:right2}\n  </div>\n  \n<!-- Footer -->  \n  <footer class="grid_12 mt10px"> {$content:footer:static} </footer>\n</div>\n<!-- end of container div -->\n{$seo:bottom}\n </body>\n</html>', 'typeregular'),
('default', '<!DOCTYPE html>\n<html lang="en">\n<head>\n<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"  />\n<title>{$page:title}</title>\n<meta name="keywords" content="{$meta:keywords}" />\n<meta name="description" content="{$meta:description}" />\n<meta name="generator" content="seotoaster" />\n\n<link href="reset.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="style.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="content.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="nav.css" rel="stylesheet"  type="text/css" media="screen" />\n<link href="products.css" rel="stylesheet"  type="text/css" media="screen" />\n\n{$concatcss}\n<!--[if IE]>\n<script src="html5.js" type="text/javascript"></script>\n<![endif]-->\n{$seo:top}\n</head>\n\n<body>\n<div id="container" class="container_12">\n\n<!-- Start of the header -->\n  <header class="grid_12 mb10px header">\n  <div class=''grid_8 alpha''>\n     <a href="{$website:url}" title="" class="logo">\n      <img src="images/logo-small.jpg" width="112" height="130" alt="seotoaster">\n     </a>\n   </div>\n    <!-- end of logo div -->    \n \n   <div class=''grid_4 omega''>\n     <!--{$cart}--> \n\n    <form id="search-form" class="grid_3" action="{$website:url}/sys/backend_search/search" method="post">\n      <input id="searchtext" name="searchtext" type="text" onfocus="value=''''" value="Search" />\n      <input type="submit" value="Search" />\n      <input id="pageUrl" style="display:none;" name="pageUrl" type="hidden" value="search-results.html" />\n    </form>\n  \n  </div>\n \n    <div class="clear"></div>\n    {$menu:flat}     \n  </header>\n<!-- end of the header -->\n\n<!-- Left Column -->     \n  <div id="left" class="grid_3">\n    <nav> {$menu:main} </nav>\n    {$content:left:static}\n  </div>\n\n<!-- Main Column -->    \n  <div id="content" class="grid_6">\n    <h2>{$header:content}</h2>\n    {$content:content1}\n    {$content:content2} \n  </div>\n  \n \n<!-- Right Column --> \n  <div id="right" class="grid_3">\n    <h2>{$header:right1:static}</h2>\n    {$content:right1:static}\n    <div class="separator"></div>\n    <h2>{$header:right2}</h2>\n    {$content:right2}\n  </div>\n  \n<!-- Footer -->  \n  <footer class="grid_12 mt10px"> {$content:footer:static} </footer>\n</div>\n<!-- end of container div -->\n{$seo:bottom}\n </body>\n</html>', 'typeregular'),
('index', '<!DOCTYPE html>\n<html lang="en">\n<head>\n<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"  />\n<title>{$page:title}</title>\n<meta name="keywords" content="{$meta:keywords}" />\n<meta name="description" content="{$meta:description}" />\n<meta name="generator" content="seotoaster" />\n\n<link href="reset.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="style.css" rel="stylesheet" type="text/css" media="screen" />\n<link href="content.css" rel="stylesheet" type="text/css" media="screen" />\n\n<!--[if IE]>\n<script src="html5.js" type="text/javascript"></script>\n<![endif]-->\n\n{$seo:top}\n</head>\n\n<body>\n<div  class="container_12">\n\n<header>\n      <div class="grid_4">\n        <h1 class="logo">\n    	 <a href="{$website:url}" title="{$page:h1}" class="logo">\n			  <img src="images/logo-small.jpg" width="215" height="275" alt="seotoaster">\n		 </a>\n	   </h1>\n	  </div>\n\n	  <div class="grid_7">\n		<h2 class="mt40px mb50px xlarge">Congratulations,<br />you have succesfully installed<br /><strong>SEOTOASTER V2 !</strong></h2>\n        <div class="log_in">Now log into your admin console at <a href="{$website:url}go">{$website:url}go</a></div>\n	   </div>\n</header>\n{adminonly}\n<script>\n  $(document).ready(function(){\n     $(''.log_in'').hide();\n});\n</script>\n<section>\n    <h3><span class="number">1</span>Hit the ground running: get your website on the map now</h3>\n    <p>Complete the website ID [WID] card below. It''s a great time saver, and when you use one of our free premium themes your information shows up in <strong>all the right places</strong> throughout your website.</p>\n    <p>In addition,  SEOTOASTER build a kml file to help <strong>search engines and Google Earth locate your business </strong>while plug-ins work better and provide you with a pre-built <strong>mobile version</strong> of your website for instance.</p>\n    <hr />\n\n    {$plugin:widcard:landing}\n   <h3 class="mt10px"><span class="number">2</span>Look like a million bucks: download a FREE premium theme</h3>\n    <iframe id="themesList" scrolling-y="yes" frameborder="0" style="width: 100%; height: 660px;" runat="server" src="http://new.seotoaster.com/themes-for-mojo.html" allowtransparency="true"></iframe>\n    <h3 class="mt10px"><span class="number">3</span>Use the easy-to-follow assembly instructions</h3>\n<a class="_lbox" title="1 click themes" href="http://www.seotoaster.com/images/how-to-add-theme/original/1-click-themes.jpg"><img src="http://www.seotoaster.com/images/how-to-add-theme/medium/1-click-themes.jpg" border="0" alt="1 click themes" width="315" height="221" /></a>\n<a class="_lbox" title="2 upload theme" href="http://www.seotoaster.com/images/how-to-add-theme/original/2-upload-theme.jpg"><img src="http://www.seotoaster.com/images/how-to-add-theme/medium/2-upload-theme.jpg" border="0" alt="2 upload theme" width="315" height="221" /></a>\n<a class="_lbox" title="3 select theme" href="http://www.seotoaster.com/images/how-to-add-theme/original/3-select-theme.jpg"><img src="http://www.seotoaster.com/images/how-to-add-theme/medium/3-select-theme.jpg" border="0" alt="3 select theme" width="315" height="221" /></a>\n    <h3 class="mt40px"><span class="number">4</span>Explore the plug-ins marketplace: buy or lease it''s up to you !</h3>\n    <iframe id="themesList" scrolling-y="yes" frameborder="0" style="width: 100%; height: 700px;" runat="server" src="http://new.seotoaster.com/plugins-for-mojo.html" allowtransparency="true"></iframe>\n</section>\n{/adminonly}\n<hr />\n  <footer class="mt10px">\n<p>Powered by Free &amp; Open Source Ecommerce Website Builder <a href="http://www.seotoaster.com" target="_blank">SEOTOASTER</a>, Courtesy of <a href="http://www.seosamba.com" target="_blank">SEO Samba</a>.</p> </footer>\n</div>\n{$seo:bottom}\n </body>\n</html>', 'typeregular'),
('news', '<!DOCTYPE html>\r\n<html lang="en">\r\n<head>\r\n<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"  />\r\n<title>{$page:title}</title>\r\n<meta name="keywords" content="{$meta:keywords}" />\r\n<meta name="description" content="{$meta:description}" />\r\n<meta name="generator" content="seotoaster" />\r\n\r\n<link href="reset.css" rel="stylesheet" type="text/css" media="screen" />\r\n<link href="style.css" rel="stylesheet" type="text/css" media="screen" />\r\n<link href="content.css" rel="stylesheet" type="text/css" media="screen" />\r\n<link href="nav.css" rel="stylesheet"  type="text/css" media="screen" />\r\n<link href="products.css" rel="stylesheet"  type="text/css" media="screen" />\r\n\r\n{$concatcss}\r\n<!--[if IE]>\r\n<script src="html5.js" type="text/javascript"></script>\r\n<![endif]-->\r\n{$seo:top}\r\n</head>\r\n\r\n<body>\r\n<div id="container" class="container_12">\r\n\r\n<!-- Start of the header -->\r\n  <header class="grid_12 mb10px header">\r\n     <a href="" title="" class="logo">\r\n      <img src="images/logo-small.jpg" width="112" height="130" alt="">\r\n     </a>\r\n    <!-- end of logo div -->\r\n         \r\n    <div class="clear"></div>\r\n    {$menu:flat}     \r\n  </header>\r\n<!-- end of the header -->\r\n\r\n<!-- Left Column -->     \r\n  <div id="left" class="grid_3">\r\n    <nav> {$menu:main} </nav>\r\n  </div>\r\n\r\n<!-- Main Column -->    \r\n  <div id="content" class="grid_9">\r\n   {$newslist}\r\n   {$newsitem}   \r\n  </div>  \r\n  \r\n<!-- Footer -->  \r\n  <footer class="grid_12 mt10px"></footer>\r\n</div>\r\n<!-- end of container div -->\r\n{$seo:bottom}\r\n </body>\r\n</html>', 'typeregular');

-- --------------------------------------------------------

--
-- Table structure for table `template_type`
--

CREATE TABLE IF NOT EXISTS `template_type` (
  `id` varchar(255) COLLATE utf8_unicode_ci NOT NULL COMMENT 'Template type name: For example ''quote'', ''regularpage'', etc...',
  `title` tinytext COLLATE utf8_unicode_ci NOT NULL COMMENT 'Alias for the template "Product listing", etc...',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `template_type`
--

INSERT INTO `template_type` (`id`, `title`) VALUES
('typecheckout', 'Checkout page'),
('typelisting', 'Product listing'),
('typemail', 'E-mail sending'),
('typeproduct', 'Product page'),
('typeregular', 'Regular page');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

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

--
-- Constraints for dumped tables
--

--
-- Constraints for table `container`
--
ALTER TABLE `container`
  ADD CONSTRAINT `container_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `deeplink`
--
ALTER TABLE `deeplink`
  ADD CONSTRAINT `deeplink_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `email_triggers_actions`
--
ALTER TABLE `email_triggers_actions`
  ADD CONSTRAINT `email_triggers_actions_ibfk_1` FOREIGN KEY (`trigger`) REFERENCES `email_triggers` (`trigger_name`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `email_triggers_actions_ibfk_2` FOREIGN KEY (`recipient`) REFERENCES `email_triggers_recipient` (`recipient`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `email_triggers_actions_ibfk_3` FOREIGN KEY (`template`) REFERENCES `template` (`name`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `link_container`
--
ALTER TABLE `link_container`
  ADD CONSTRAINT `FK_link_container` FOREIGN KEY (`id_container`) REFERENCES `container` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news`
--
ALTER TABLE `news`
  ADD CONSTRAINT `news_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `news_rel_category`
--
ALTER TABLE `news_rel_category`
  ADD CONSTRAINT `news_rel_category_ibfk_1` FOREIGN KEY (`news_id`) REFERENCES `news` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `news_rel_category_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `news_category` (`id`);

--
-- Constraints for table `optimized`
--
ALTER TABLE `optimized`
  ADD CONSTRAINT `optimized_ibfk_1` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `page_fa`
--
ALTER TABLE `page_fa`
  ADD CONSTRAINT `page_fa_ibfk_1` FOREIGN KEY (`fa_id`) REFERENCES `featured_area` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_page_fa` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `redirect`
--
ALTER TABLE `redirect`
  ADD CONSTRAINT `FK_redirect` FOREIGN KEY (`page_id`) REFERENCES `page` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `template`
--
ALTER TABLE `template`
  ADD CONSTRAINT `template_ibfk_1` FOREIGN KEY (`type`) REFERENCES `template_type` (`id`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
