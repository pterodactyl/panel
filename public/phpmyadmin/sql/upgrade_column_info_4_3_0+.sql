-- -------------------------------------------------------------
-- SQL Commands to upgrade pmadb.pma__column_info table
-- for normal phpMyAdmin operation
--
-- This file is meant for use with phpMyAdmin 4.3.0 and above!
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
-- Update table structure for table `pma__column_info`
--
UPDATE `pma__column_info`
  SET `mimetype` = REPLACE(`mimetype`, 'octet-stream', 'octetstream');
UPDATE `pma__column_info`
  SET `transformation` = REPLACE(REPLACE(`transformation`, '__', '_'), 'inc.php', 'class.php');
UPDATE `pma__column_info`
  SET `transformation` = ''
  WHERE `transformation` = '_';
UPDATE `pma__column_info`
  SET `transformation` = CONCAT('output/', `transformation`)
  WHERE `transformation` IN (
    'application_octetstream_download.class.php',
    'application_octetstream_hex.class.php',
    'image_jpeg_inline.class.php',
    'image_jpeg_link.class.php',
    'image_png_inline.class.php',
    'text_plain_bool2text.class.php',
    'text_plain_dateformat.class.php',
    'text_plain_external.class.php',
    'text_plain_formatted.class.php',
    'text_plain_imagelink.class.php',
    'text_plain_sql.class.php'
  );
ALTER TABLE `pma__column_info`
  ADD `input_transformation` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  ADD `input_transformation_options` varchar(255) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '';
