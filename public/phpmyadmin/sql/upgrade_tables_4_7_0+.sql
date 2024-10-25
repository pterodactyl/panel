-- -------------------------------------------------------------
-- SQL Commands to upgrade pmadb.pma__column_info table
-- for normal phpMyAdmin operation
--
-- This file is meant for use with phpMyAdmin 4.6.5 and above!
-- For older releases, please use create_tables.sql
--
-- Please don't forget to set up the table names in config.inc.php
--

-- --------------------------------------------------------

--
-- Database : `phpmyadmin`
--
USE `phpmyadmin`;

-- --------------------------------------------------------

--
-- Update table structure for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  CHANGE `id` `id` int( 10 ) unsigned NOT NULL auto_increment;
