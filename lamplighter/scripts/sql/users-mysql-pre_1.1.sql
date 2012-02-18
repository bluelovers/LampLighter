-- MySQL dump 10.9
--
-- Host: localhost    Database: user_dev
-- ------------------------------------------------------
-- Server version	4.1.21-community-nt

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `group_preferences`
--


CREATE TABLE `group_preferences` (
  `group_pref_id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL default '0',
  `pref_type_id` int(10) unsigned NOT NULL default '0',
  `group_pref_val` mediumtext,
  PRIMARY KEY  (`group_pref_id`),
  KEY `pref_type_fk` (`pref_type_id`),
  KEY `group_id_fk` (`group_id`),
  CONSTRAINT `group_preferences_ibfk_1` FOREIGN KEY (`pref_type_id`) REFERENCES `user_preference_types` (`pref_type_id`),
  CONSTRAINT `group_preferences_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `group_privileges`
--


CREATE TABLE `group_privileges` (
  `group_priv_id` int(10) unsigned NOT NULL auto_increment,
  `group_id` int(10) unsigned NOT NULL default '0',
  `group_priv_val` tinytext,
  `priv_type_id` int(10) unsigned NOT NULL default '0',
  `priv_context_type_id` int(10) unsigned default NULL,
  `group_priv_context_val` varchar(128) default NULL,
  PRIMARY KEY  (`group_priv_id`),
  KEY `priv_type_fk` (`priv_type_id`),
  KEY `group_id_fk` (`group_id`),
  KEY `context_fk` (`priv_context_type_id`),
  CONSTRAINT `group_privileges_ibfk_1` FOREIGN KEY (`priv_type_id`) REFERENCES `user_privilege_types` (`priv_type_id`),
  CONSTRAINT `group_privileges_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `group_privileges_ibfk_3` FOREIGN KEY (`priv_context_type_id`) REFERENCES `user_priv_context_types` (`priv_context_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `user_group_link`
--

CREATE TABLE `user_group_link` (
  `user_id` int(10) unsigned NOT NULL default '0',
  `group_id` int(10) unsigned NOT NULL default '0',
  KEY `FK_user_groups_1` (`user_id`),
  KEY `FK_user_groups_2` (`group_id`),
  CONSTRAINT `FK_user_groups_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `FK_user_groups_2` FOREIGN KEY (`group_id`) REFERENCES `user_groups` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_group_link`
--

LOCK TABLES `user_group_link` WRITE;
/*!40000 ALTER TABLE `user_group_link` DISABLE KEYS */;
INSERT INTO `user_group_link` VALUES (1,1);
/*!40000 ALTER TABLE `user_group_link` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_groups`
--


CREATE TABLE `user_groups` (
  `group_id` int(10) unsigned NOT NULL auto_increment,
  `group_name` varchar(64) NOT NULL default '',
  `group_desc` tinytext,
  PRIMARY KEY  (`group_id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_groups`
--

LOCK TABLES `user_groups` WRITE;
/*!40000 ALTER TABLE `user_groups` DISABLE KEYS */;
INSERT INTO `user_groups` VALUES (1,'Administrators','Full Access Administrators');
/*!40000 ALTER TABLE `user_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_preference_types`
--


CREATE TABLE `user_preference_types` (
  `pref_type_id` int(10) unsigned NOT NULL auto_increment,
  `pref_type_key` varchar(64) NOT NULL default '',
  `pref_type_desc` tinytext,
  `pref_type_default_val` mediumtext,
  `pref_type_name` varchar(128) default NULL,
  PRIMARY KEY  (`pref_type_id`),
  UNIQUE KEY `pref_type_key` (`pref_type_key`),
  UNIQUE KEY `pref_type_name` (`pref_type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `user_preferences`
--


CREATE TABLE `user_preferences` (
  `user_pref_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `pref_type_id` int(10) unsigned NOT NULL default '0',
  `user_pref_val` mediumtext,
  PRIMARY KEY  (`user_pref_id`),
  KEY `pref_type_fk` (`pref_type_id`),
  KEY `user_id_fk` (`user_id`),
  CONSTRAINT `user_preferences_ibfk_1` FOREIGN KEY (`pref_type_id`) REFERENCES `user_preference_types` (`pref_type_id`),
  CONSTRAINT `user_preferences_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `user_priv_context_types`
--

CREATE TABLE `user_priv_context_types` (
  `priv_context_type_id` int(10) unsigned NOT NULL auto_increment,
  `priv_context_type_key` varchar(64) NOT NULL default '',
  `priv_context_type_name` varchar(128) default NULL,
  `priv_context_type_desc` tinytext,
  PRIMARY KEY  (`priv_context_type_id`),
  UNIQUE KEY `priv_context_type_key` (`priv_context_type_key`),
  UNIQUE KEY `priv_context_type_name` (`priv_context_type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Table structure for table `user_privilege_types`
--

CREATE TABLE `user_privilege_types` (
  `priv_type_id` int(10) unsigned NOT NULL auto_increment,
  `priv_type_key` varchar(64) NOT NULL default '',
  `priv_type_desc` tinytext,
  PRIMARY KEY  (`priv_type_id`),
  UNIQUE KEY `priv_type_key` (`priv_type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `user_privileges`
--

CREATE TABLE `user_privileges` (
  `user_priv_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_priv_val` tinytext,
  `priv_type_id` int(10) unsigned NOT NULL default '0',
  `priv_context_type_id` int(10) unsigned default NULL,
  `user_priv_context_val` varchar(128) default NULL,
  PRIMARY KEY  (`user_priv_id`),
  KEY `priv_type_fk` (`priv_type_id`),
  KEY `user_id_fk` (`user_id`),
  KEY `context_fk` (`priv_context_type_id`),
  CONSTRAINT `user_privileges_ibfk_1` FOREIGN KEY (`priv_type_id`) REFERENCES `user_privilege_types` (`priv_type_id`),
  CONSTRAINT `user_privileges_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `user_privileges_ibfk_3` FOREIGN KEY (`priv_context_type_id`) REFERENCES `user_priv_context_types` (`priv_context_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `user_restrictions`
--

CREATE TABLE `user_restrictions` (
  `user_restriction_id` int(10) unsigned NOT NULL auto_increment,
  `user_id` int(10) unsigned NOT NULL default '0',
  `user_restriction_val` varchar(128) default NULL,
  `priv_type_id` int(10) unsigned NOT NULL default '0',
  `priv_context_type_id` int(10) unsigned default NULL,
  `user_restriction_context_val` varchar(128) default NULL,
  PRIMARY KEY  (`user_restriction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(10) unsigned NOT NULL auto_increment,
  `user_name` varchar(64) NOT NULL default '',
  `user_password` varchar(64) NOT NULL default '',
  `user_password_encrypted` blob,
  `user_date` datetime default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `user_name` (`user_name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin',NULL,'2007-07-16 14:56:40');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

