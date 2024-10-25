-- -------------------------------------------------------------
-- SQL Commands to upgrade pmadb for normal phpMyAdmin operation
-- with MySQL 4.1.2 and above.
--
-- This file is meant for use with MySQL 4.1.2 and above!
-- For older MySQL releases, please use create_tables.sql
--
-- If you are running one MySQL 4.1.0 or 4.1.1, please create the tables using
-- create_tables.sql, then use this script.
--
-- Please don't forget to set up the tablenames in config.inc.php
--

-- --------------------------------------------------------

--
-- Database : `phpmyadmin`
--
ALTER DATABASE `phpmyadmin`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE phpmyadmin;

-- --------------------------------------------------------

--
-- Table structure for table `pma__bookmark`
--
ALTER TABLE `pma__bookmark`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__bookmark`
  CHANGE `dbase` `dbase` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__bookmark`
  CHANGE `user` `user` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__bookmark`
  CHANGE `label` `label` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `pma__bookmark`
  CHANGE `query` `query` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

-- --------------------------------------------------------

--
-- Table structure for table `pma__column_info`
--

ALTER TABLE `pma__column_info`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__column_info`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__column_info`
  CHANGE `table_name` `table_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__column_info`
  CHANGE `column_name` `column_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__column_info`
  CHANGE `comment` `comment` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `pma__column_info`
  CHANGE `mimetype` `mimetype` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '';
ALTER TABLE `pma__column_info`
  CHANGE `transformation` `transformation` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__column_info`
  CHANGE `transformation_options` `transformation_options` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

-- --------------------------------------------------------

--
-- Table structure for table `pma__history`
--
ALTER TABLE `pma__history`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__history`
  CHANGE `username` `username` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__history`
  CHANGE `db` `db` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__history`
  CHANGE `table` `table` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__history`
  CHANGE `sqlquery` `sqlquery` TEXT CHARACTER SET utf8 COLLATE utf8_bin NOT NULL;

-- --------------------------------------------------------

--
-- Table structure for table `pma__pdf_pages`
--

ALTER TABLE `pma__pdf_pages`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__pdf_pages`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__pdf_pages`
  CHANGE `page_descr` `page_descr` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '';

-- --------------------------------------------------------

--
-- Table structure for table `pma__relation`
--
ALTER TABLE `pma__relation`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__relation`
  CHANGE `master_db` `master_db` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__relation`
  CHANGE `master_table` `master_table` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__relation`
  CHANGE `master_field` `master_field` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__relation`
  CHANGE `foreign_db` `foreign_db` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__relation`
  CHANGE `foreign_table` `foreign_table` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__relation`
  CHANGE `foreign_field` `foreign_field` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_coords`
--

ALTER TABLE `pma__table_coords`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__table_coords`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__table_coords`
  CHANGE `table_name` `table_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';

-- --------------------------------------------------------

--
-- Table structure for table `pma__table_info`
--

ALTER TABLE `pma__table_info`
  DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;

ALTER TABLE `pma__table_info`
  CHANGE `db_name` `db_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__table_info`
  CHANGE `table_name` `table_name` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
ALTER TABLE `pma__table_info`
  CHANGE `display_field` `display_field` VARCHAR(64) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
