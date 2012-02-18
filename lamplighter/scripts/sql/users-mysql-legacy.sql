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
-- Definition of table `group_prefs`
--

DROP TABLE IF EXISTS `group_prefs`;
CREATE TABLE `group_prefs` (
  `group_pref_id` int(10) unsigned NOT NULL auto_increment,
  `gid` int(10) unsigned NOT NULL default '0',
  `pref_type_id` int(10) unsigned NOT NULL default '0',
  `group_pref_val` mediumtext,
  PRIMARY KEY  (`group_pref_id`),
  KEY `pref_type_fk` (`pref_type_id`),
  KEY `gid_fk` (`gid`),
  CONSTRAINT `group_prefs_ibfk_1` FOREIGN KEY (`pref_type_id`) REFERENCES `user_pref_types` (`pref_type_id`),
  CONSTRAINT `group_prefs_ibfk_2` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `group_prefs`
--

/*!40000 ALTER TABLE `group_prefs` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_prefs` ENABLE KEYS */;


--
-- Definition of table `group_privs`
--

DROP TABLE IF EXISTS `group_privs`;
CREATE TABLE `group_privs` (
  `group_priv_id` int(10) unsigned NOT NULL auto_increment,
  `gid` int(10) unsigned NOT NULL default '0',
  `group_priv_val` tinytext,
  `priv_type_id` int(10) unsigned NOT NULL default '0',
  `priv_context_type_id` int(10) unsigned default NULL,
  `priv_context_val` varchar(128) default NULL,
  PRIMARY KEY  (`group_priv_id`),
  KEY `priv_type_fk` (`priv_type_id`),
  KEY `gid_fk` (`gid`),
  KEY `context_fk` (`priv_context_type_id`),
  CONSTRAINT `group_privs_ibfk_1` FOREIGN KEY (`priv_type_id`) REFERENCES `user_priv_types` (`priv_type_id`),
  CONSTRAINT `group_privs_ibfk_2` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`) ON DELETE CASCADE,
  CONSTRAINT `group_privs_ibfk_3` FOREIGN KEY (`priv_context_type_id`) REFERENCES `user_priv_context_types` (`priv_context_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `group_privs`
--

/*!40000 ALTER TABLE `group_privs` DISABLE KEYS */;
/*!40000 ALTER TABLE `group_privs` ENABLE KEYS */;


--
-- Definition of table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE `groups` (
  `gid` int(10) unsigned NOT NULL auto_increment,
  `groupname` varchar(64) NOT NULL default '',
  `group_desc` tinytext,
  PRIMARY KEY  (`gid`),
  UNIQUE KEY `groupname` (`groupname`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `groups`
--

/*!40000 ALTER TABLE `groups` DISABLE KEYS */;
INSERT INTO `groups` (`gid`,`groupname`,`group_desc`) VALUES 
 (1,'Administrators','Full Access Administrators');
/*!40000 ALTER TABLE `groups` ENABLE KEYS */;


--
-- Definition of table `user_pref_types`
--

DROP TABLE IF EXISTS `user_pref_types`;
CREATE TABLE `user_pref_types` (
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
-- Dumping data for table `user_pref_types`
--

/*!40000 ALTER TABLE `user_pref_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_pref_types` ENABLE KEYS */;


--
-- Definition of table `user_prefs`
--

DROP TABLE IF EXISTS `user_prefs`;
CREATE TABLE `user_prefs` (
  `user_pref_id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `pref_type_id` int(10) unsigned NOT NULL default '0',
  `user_pref_val` mediumtext,
  PRIMARY KEY  (`user_pref_id`),
  KEY `pref_type_fk` (`pref_type_id`),
  KEY `uid_fk` (`uid`),
  CONSTRAINT `user_prefs_ibfk_1` FOREIGN KEY (`pref_type_id`) REFERENCES `user_pref_types` (`pref_type_id`),
  CONSTRAINT `user_prefs_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_prefs`
--

/*!40000 ALTER TABLE `user_prefs` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_prefs` ENABLE KEYS */;


--
-- Definition of table `user_priv_context_types`
--

DROP TABLE IF EXISTS `user_priv_context_types`;
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
-- Dumping data for table `user_priv_context_types`
--

/*!40000 ALTER TABLE `user_priv_context_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_priv_context_types` ENABLE KEYS */;


--
-- Definition of table `user_priv_types`
--

DROP TABLE IF EXISTS `user_priv_types`;
CREATE TABLE `user_priv_types` (
  `priv_type_id` int(10) unsigned NOT NULL auto_increment,
  `priv_type_key` varchar(64) NOT NULL default '',
  `priv_type_desc` tinytext,
  PRIMARY KEY  (`priv_type_id`),
  UNIQUE KEY `priv_type_key` (`priv_type_key`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_priv_types`
--

/*!40000 ALTER TABLE `user_priv_types` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_priv_types` ENABLE KEYS */;


--
-- Definition of table `user_privs`
--

DROP TABLE IF EXISTS `user_privs`;
CREATE TABLE `user_privs` (
  `user_priv_id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `user_priv_val` tinytext,
  `priv_type_id` int(10) unsigned NOT NULL default '0',
  `priv_context_type_id` int(10) unsigned default NULL,
  `priv_context_val` varchar(128) default NULL,
  PRIMARY KEY  (`user_priv_id`),
  KEY `priv_type_fk` (`priv_type_id`),
  KEY `uid_fk` (`uid`),
  KEY `context_fk` (`priv_context_type_id`),
  CONSTRAINT `user_privs_ibfk_1` FOREIGN KEY (`priv_type_id`) REFERENCES `user_priv_types` (`priv_type_id`),
  CONSTRAINT `user_privs_ibfk_2` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE,
  CONSTRAINT `user_privs_ibfk_3` FOREIGN KEY (`priv_context_type_id`) REFERENCES `user_priv_context_types` (`priv_context_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_privs`
--

/*!40000 ALTER TABLE `user_privs` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_privs` ENABLE KEYS */;


--
-- Definition of table `user_restrictions`
--

DROP TABLE IF EXISTS `user_restrictions`;
CREATE TABLE `user_restrictions` (
  `user_restriction_id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL default '0',
  `user_restriction_val` varchar(128) default NULL,
  `priv_type_id` int(10) unsigned NOT NULL default '0',
  `priv_context_type_id` int(10) unsigned default NULL,
  `priv_context_val` varchar(128) default NULL,
  PRIMARY KEY  (`user_restriction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_restrictions`
--

/*!40000 ALTER TABLE `user_restrictions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_restrictions` ENABLE KEYS */;


--
-- Definition of table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `uid` int(10) unsigned NOT NULL auto_increment,
  `gid` int(10) unsigned NOT NULL default '0',
  `username` varchar(64) NOT NULL default '',
  `password` varchar(64) NOT NULL default '',
  `password_encrypted` blob,
  `user_date` datetime default NULL,
  PRIMARY KEY  (`uid`),
  UNIQUE KEY `username` (`username`),
  KEY `gid_fk` (`gid`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`gid`) REFERENCES `groups` (`gid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` (`uid`,`gid`,`username`,`password`,`password_encrypted`,`user_date`) VALUES 
 (1,1,'Administrator','admin',NULL,'2007-07-16 14:56:40');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;




/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;