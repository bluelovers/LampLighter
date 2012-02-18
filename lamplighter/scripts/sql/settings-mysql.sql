-- MySQL Administrator dump 1.4
--
-- ------------------------------------------------------
-- Server version	4.1.21-community-nt


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


--
-- Definition of table `setting_context_types`
--

CREATE TABLE `setting_context_types` (
  `setting_context_type_id` int(10) unsigned NOT NULL auto_increment,
  `setting_context_type_key` varchar(64) NOT NULL default '',
  `setting_context_type_name` varchar(128) default NULL,
  `setting_context_type_desc` tinytext,
  PRIMARY KEY  (`setting_context_type_id`),
  UNIQUE KEY `Index_2` (`setting_context_type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Definition of table `setting_types`
--

CREATE TABLE `setting_types` (
  `setting_type_id` int(10) unsigned NOT NULL auto_increment,
  `setting_type_key` varchar(64) NOT NULL default '',
  `setting_type_name` varchar(128) default NULL,
  `setting_type_desc` tinytext,
  `setting_type_default_value` mediumtext,
  PRIMARY KEY  (`setting_type_id`),
  UNIQUE KEY `Index_2` (`setting_type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Definition of table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(10) unsigned NOT NULL auto_increment,
  `setting_value` mediumtext NOT NULL,
  `setting_type_id` int(10) unsigned NOT NULL default '0',
  `setting_context_type_id` int(10) unsigned default NULL,
  `setting_context_value` mediumtext,
  PRIMARY KEY  (`setting_id`),
  KEY `FK_settings_1` (`setting_type_id`),
  KEY `FK_settings_2` (`setting_context_type_id`),
  CONSTRAINT `FK_settings_2` FOREIGN KEY (`setting_context_type_id`) REFERENCES `setting_context_types` (`setting_context_type_id`),
  CONSTRAINT `FK_settings_1` FOREIGN KEY (`setting_type_id`) REFERENCES `setting_types` (`setting_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
