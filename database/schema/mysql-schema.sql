/*M!999999\- enable the sandbox mode */ 
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `activity_log_subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_log_subjects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `activity_log_id` bigint(20) unsigned NOT NULL,
  `subject_type` varchar(191) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_log_subjects_activity_log_id_foreign` (`activity_log_id`),
  KEY `activity_log_subjects_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  CONSTRAINT `activity_log_subjects_activity_log_id_foreign` FOREIGN KEY (`activity_log_id`) REFERENCES `activity_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `batch` char(36) DEFAULT NULL,
  `event` varchar(191) NOT NULL,
  `ip` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `actor_type` varchar(191) DEFAULT NULL,
  `actor_id` bigint(20) unsigned DEFAULT NULL,
  `api_key_id` int(10) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`properties`)),
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `activity_logs_actor_type_actor_id_index` (`actor_type`,`actor_id`),
  KEY `activity_logs_event_index` (`event`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `allocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `node_id` int(10) unsigned NOT NULL,
  `ip` varchar(191) NOT NULL,
  `ip_alias` text DEFAULT NULL,
  `port` mediumint(8) unsigned NOT NULL,
  `server_id` int(10) unsigned DEFAULT NULL,
  `notes` varchar(191) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `allocations_node_id_ip_port_unique` (`node_id`,`ip`,`port`),
  KEY `allocations_server_id_foreign` (`server_id`),
  CONSTRAINT `allocations_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `allocations_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `key_type` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `identifier` char(16) DEFAULT NULL,
  `token` text NOT NULL,
  `allowed_ips` text DEFAULT NULL,
  `memo` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `r_servers` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_nodes` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_allocations` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_users` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_locations` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_nests` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_eggs` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_database_hosts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `r_server_databases` tinyint(3) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_keys_identifier_unique` (`identifier`),
  KEY `api_keys_user_id_foreign` (`user_id`),
  CONSTRAINT `api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `authorized` tinyint(1) NOT NULL,
  `error` text DEFAULT NULL,
  `key` char(16) DEFAULT NULL,
  `method` char(6) NOT NULL,
  `route` text NOT NULL,
  `content` text DEFAULT NULL,
  `user_agent` text NOT NULL,
  `request_ip` varchar(45) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `user_id` int(10) unsigned DEFAULT NULL,
  `server_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(191) NOT NULL,
  `subaction` varchar(191) DEFAULT NULL,
  `device` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`device`)),
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_server_id_foreign` (`server_id`),
  KEY `audit_logs_action_server_id_index` (`action`,`server_id`),
  CONSTRAINT `audit_logs_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL,
  `uuid` char(36) NOT NULL,
  `upload_id` text DEFAULT NULL,
  `is_successful` tinyint(1) NOT NULL DEFAULT 0,
  `is_locked` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `name` varchar(191) NOT NULL,
  `ignored_files` text NOT NULL,
  `disk` varchar(191) NOT NULL,
  `checksum` varchar(191) DEFAULT NULL,
  `bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `backups_uuid_unique` (`uuid`),
  KEY `backups_server_id_foreign` (`server_id`),
  CONSTRAINT `backups_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `database_hosts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `database_hosts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `host` varchar(191) NOT NULL,
  `port` int(10) unsigned NOT NULL,
  `username` varchar(191) NOT NULL,
  `password` text NOT NULL,
  `max_databases` int(10) unsigned DEFAULT NULL,
  `node_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `database_hosts_node_id_foreign` (`node_id`),
  CONSTRAINT `database_hosts_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `databases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `databases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL,
  `database_host_id` int(10) unsigned NOT NULL,
  `database` varchar(191) NOT NULL,
  `username` varchar(191) NOT NULL,
  `remote` varchar(191) NOT NULL DEFAULT '%',
  `password` text NOT NULL,
  `max_connections` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `databases_database_host_id_username_unique` (`database_host_id`,`username`),
  UNIQUE KEY `databases_database_host_id_server_id_database_unique` (`database_host_id`,`server_id`,`database`),
  KEY `databases_server_id_foreign` (`server_id`),
  CONSTRAINT `databases_database_host_id_foreign` FOREIGN KEY (`database_host_id`) REFERENCES `database_hosts` (`id`),
  CONSTRAINT `databases_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `egg_mount`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `egg_mount` (
  `egg_id` int(10) unsigned NOT NULL,
  `mount_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `egg_mount_egg_id_mount_id_unique` (`egg_id`,`mount_id`),
  KEY `egg_mount_mount_id_foreign` (`mount_id`),
  CONSTRAINT `egg_mount_egg_id_foreign` FOREIGN KEY (`egg_id`) REFERENCES `eggs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `egg_mount_mount_id_foreign` FOREIGN KEY (`mount_id`) REFERENCES `mounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `egg_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `egg_variables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `egg_id` int(10) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `env_variable` varchar(191) NOT NULL,
  `default_value` text NOT NULL,
  `user_viewable` tinyint(3) unsigned NOT NULL,
  `user_editable` tinyint(3) unsigned NOT NULL,
  `rules` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_variables_egg_id_foreign` (`egg_id`),
  CONSTRAINT `service_variables_egg_id_foreign` FOREIGN KEY (`egg_id`) REFERENCES `eggs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `eggs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `eggs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `nest_id` int(10) unsigned NOT NULL,
  `author` varchar(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `docker_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`docker_images`)),
  `file_denylist` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`file_denylist`)),
  `update_url` text DEFAULT NULL,
  `config_files` text DEFAULT NULL,
  `config_startup` text DEFAULT NULL,
  `config_logs` text DEFAULT NULL,
  `config_stop` varchar(191) DEFAULT NULL,
  `config_from` int(10) unsigned DEFAULT NULL,
  `startup` text DEFAULT NULL,
  `script_container` varchar(191) NOT NULL DEFAULT 'alpine:3.4',
  `copy_script_from` int(10) unsigned DEFAULT NULL,
  `script_entry` varchar(191) NOT NULL DEFAULT 'ash',
  `script_is_privileged` tinyint(1) NOT NULL DEFAULT 1,
  `script_install` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `force_outgoing_ip` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_options_uuid_unique` (`uuid`),
  KEY `service_options_nest_id_foreign` (`nest_id`),
  KEY `eggs_config_from_foreign` (`config_from`),
  KEY `eggs_copy_script_from_foreign` (`copy_script_from`),
  CONSTRAINT `eggs_config_from_foreign` FOREIGN KEY (`config_from`) REFERENCES `eggs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `eggs_copy_script_from_foreign` FOREIGN KEY (`copy_script_from`) REFERENCES `eggs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `service_options_nest_id_foreign` FOREIGN KEY (`nest_id`) REFERENCES `nests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) DEFAULT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `failed_at` timestamp NOT NULL,
  `exception` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(191) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_reserved_at_index` (`queue`,`reserved_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `locations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `short` varchar(191) NOT NULL,
  `long` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `locations_short_unique` (`short`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mount_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mount_node` (
  `node_id` int(10) unsigned NOT NULL,
  `mount_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `mount_node_node_id_mount_id_unique` (`node_id`,`mount_id`),
  KEY `mount_node_mount_id_foreign` (`mount_id`),
  CONSTRAINT `mount_node_mount_id_foreign` FOREIGN KEY (`mount_id`) REFERENCES `mounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `mount_node_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mount_server`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mount_server` (
  `server_id` int(10) unsigned NOT NULL,
  `mount_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `mount_server_server_id_mount_id_unique` (`server_id`,`mount_id`),
  KEY `mount_server_mount_id_foreign` (`mount_id`),
  CONSTRAINT `mount_server_mount_id_foreign` FOREIGN KEY (`mount_id`) REFERENCES `mounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `mount_server_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `source` varchar(191) NOT NULL,
  `target` varchar(191) NOT NULL,
  `read_only` tinyint(3) unsigned NOT NULL,
  `user_mountable` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mounts_id_unique` (`id`),
  UNIQUE KEY `mounts_uuid_unique` (`uuid`),
  UNIQUE KEY `mounts_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `author` char(191) NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `services_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nodes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) NOT NULL,
  `public` smallint(5) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `location_id` int(10) unsigned NOT NULL,
  `fqdn` varchar(191) NOT NULL,
  `scheme` varchar(191) NOT NULL DEFAULT 'https',
  `behind_proxy` tinyint(1) NOT NULL DEFAULT 0,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `memory` int(10) unsigned NOT NULL,
  `memory_overallocate` int(11) NOT NULL DEFAULT 0,
  `disk` int(10) unsigned NOT NULL,
  `disk_overallocate` int(11) NOT NULL DEFAULT 0,
  `upload_size` int(10) unsigned NOT NULL DEFAULT 100,
  `daemon_token_id` char(16) NOT NULL,
  `daemon_token` text NOT NULL,
  `daemonListen` smallint(5) unsigned NOT NULL DEFAULT 8080,
  `daemonSFTP` smallint(5) unsigned NOT NULL DEFAULT 2022,
  `daemonBase` varchar(191) NOT NULL DEFAULT '/home/daemon-files',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nodes_uuid_unique` (`uuid`),
  UNIQUE KEY `nodes_daemon_token_id_unique` (`daemon_token_id`),
  KEY `nodes_location_id_foreign` (`location_id`),
  CONSTRAINT `nodes_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` varchar(191) NOT NULL,
  `type` varchar(191) NOT NULL,
  `notifiable_type` varchar(191) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NOT NULL,
  KEY `password_resets_email_index` (`email`),
  KEY `password_resets_token_index` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `recovery_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recovery_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(191) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `recovery_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `recovery_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `cron_day_of_week` varchar(191) NOT NULL,
  `cron_month` varchar(191) NOT NULL,
  `cron_day_of_month` varchar(191) NOT NULL,
  `cron_hour` varchar(191) NOT NULL,
  `cron_minute` varchar(191) NOT NULL,
  `is_active` tinyint(1) NOT NULL,
  `is_processing` tinyint(1) NOT NULL,
  `only_when_online` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_run_at` timestamp NULL DEFAULT NULL,
  `next_run_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_server_id_foreign` (`server_id`),
  CONSTRAINT `schedules_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `server_transfers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned NOT NULL,
  `successful` tinyint(1) DEFAULT NULL,
  `old_node` int(10) unsigned NOT NULL,
  `new_node` int(10) unsigned NOT NULL,
  `old_allocation` int(10) unsigned NOT NULL,
  `new_allocation` int(10) unsigned NOT NULL,
  `old_additional_allocations` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  `new_additional_allocations` longtext DEFAULT NULL COMMENT '(DC2Type:json)',
  `archived` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `server_transfers_server_id_foreign` (`server_id`),
  CONSTRAINT `server_transfers_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `server_variables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `server_variables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `server_id` int(10) unsigned DEFAULT NULL,
  `variable_id` int(10) unsigned NOT NULL,
  `variable_value` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `server_variables_server_id_foreign` (`server_id`),
  KEY `server_variables_variable_id_foreign` (`variable_id`),
  CONSTRAINT `server_variables_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `server_variables_variable_id_foreign` FOREIGN KEY (`variable_id`) REFERENCES `egg_variables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(191) DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `uuidShort` char(8) NOT NULL,
  `node_id` int(10) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `description` text NOT NULL,
  `status` varchar(191) DEFAULT NULL,
  `skip_scripts` tinyint(1) NOT NULL DEFAULT 0,
  `owner_id` int(10) unsigned NOT NULL,
  `memory` int(10) unsigned NOT NULL,
  `swap` int(11) NOT NULL,
  `disk` int(10) unsigned NOT NULL,
  `io` int(10) unsigned NOT NULL,
  `cpu` int(10) unsigned NOT NULL,
  `threads` varchar(191) DEFAULT NULL,
  `oom_disabled` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `allocation_id` int(10) unsigned NOT NULL,
  `nest_id` int(10) unsigned NOT NULL,
  `egg_id` int(10) unsigned NOT NULL,
  `startup` text NOT NULL,
  `image` varchar(191) NOT NULL,
  `allocation_limit` int(10) unsigned DEFAULT NULL,
  `database_limit` int(10) unsigned DEFAULT 0,
  `backup_limit` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `installed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servers_uuid_unique` (`uuid`),
  UNIQUE KEY `servers_uuidshort_unique` (`uuidShort`),
  UNIQUE KEY `servers_allocation_id_unique` (`allocation_id`),
  UNIQUE KEY `servers_external_id_unique` (`external_id`),
  KEY `servers_node_id_foreign` (`node_id`),
  KEY `servers_owner_id_foreign` (`owner_id`),
  KEY `servers_nest_id_foreign` (`nest_id`),
  KEY `servers_egg_id_foreign` (`egg_id`),
  CONSTRAINT `servers_allocation_id_foreign` FOREIGN KEY (`allocation_id`) REFERENCES `allocations` (`id`),
  CONSTRAINT `servers_egg_id_foreign` FOREIGN KEY (`egg_id`) REFERENCES `eggs` (`id`),
  CONSTRAINT `servers_nest_id_foreign` FOREIGN KEY (`nest_id`) REFERENCES `nests` (`id`),
  CONSTRAINT `servers_node_id_foreign` FOREIGN KEY (`node_id`) REFERENCES `nodes` (`id`),
  CONSTRAINT `servers_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(191) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` text NOT NULL,
  `last_activity` int(11) NOT NULL,
  UNIQUE KEY `sessions_id_unique` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settings_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subusers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subusers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `server_id` int(10) unsigned NOT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subusers_user_id_foreign` (`user_id`),
  KEY `subusers_server_id_foreign` (`server_id`),
  CONSTRAINT `subusers_server_id_foreign` FOREIGN KEY (`server_id`) REFERENCES `servers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `subusers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `schedule_id` int(10) unsigned NOT NULL,
  `sequence_id` int(10) unsigned NOT NULL,
  `action` varchar(191) NOT NULL,
  `payload` text NOT NULL,
  `time_offset` int(10) unsigned NOT NULL,
  `is_queued` tinyint(1) NOT NULL,
  `continue_on_failure` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_schedule_id_sequence_id_index` (`schedule_id`,`sequence_id`),
  CONSTRAINT `tasks_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `schedules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` int(10) unsigned NOT NULL,
  `run_time` timestamp NOT NULL,
  `run_status` int(10) unsigned NOT NULL,
  `response` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_ssh_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_ssh_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `name` varchar(191) NOT NULL,
  `fingerprint` varchar(191) NOT NULL,
  `public_key` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_ssh_keys_user_id_foreign` (`user_id`),
  CONSTRAINT `user_ssh_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `external_id` varchar(191) DEFAULT NULL,
  `uuid` char(36) NOT NULL,
  `username` varchar(191) NOT NULL,
  `email` varchar(191) NOT NULL,
  `name_first` varchar(191) DEFAULT NULL,
  `name_last` varchar(191) DEFAULT NULL,
  `password` text NOT NULL,
  `remember_token` varchar(191) DEFAULT NULL,
  `language` char(5) NOT NULL DEFAULT 'en',
  `root_admin` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `use_totp` tinyint(3) unsigned NOT NULL,
  `totp_secret` text DEFAULT NULL,
  `totp_authenticated_at` timestamp NULL DEFAULT NULL,
  `gravatar` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_uuid_unique` (`uuid`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_username_unique` (`username`),
  KEY `users_external_id_index` (`external_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

/*M!999999\- enable the sandbox mode */ 
INSERT INTO `migrations` VALUES (1,'2016_01_23_195641_add_allocations_table',1);
INSERT INTO `migrations` VALUES (2,'2016_01_23_195851_add_api_keys',1);
INSERT INTO `migrations` VALUES (3,'2016_01_23_200044_add_api_permissions',1);
INSERT INTO `migrations` VALUES (4,'2016_01_23_200159_add_downloads',1);
INSERT INTO `migrations` VALUES (5,'2016_01_23_200421_create_failed_jobs_table',1);
INSERT INTO `migrations` VALUES (6,'2016_01_23_200440_create_jobs_table',1);
INSERT INTO `migrations` VALUES (7,'2016_01_23_200528_add_locations',1);
INSERT INTO `migrations` VALUES (8,'2016_01_23_200648_add_nodes',1);
INSERT INTO `migrations` VALUES (9,'2016_01_23_201433_add_password_resets',1);
INSERT INTO `migrations` VALUES (10,'2016_01_23_201531_add_permissions',1);
INSERT INTO `migrations` VALUES (11,'2016_01_23_201649_add_server_variables',1);
INSERT INTO `migrations` VALUES (12,'2016_01_23_201748_add_servers',1);
INSERT INTO `migrations` VALUES (13,'2016_01_23_202544_add_service_options',1);
INSERT INTO `migrations` VALUES (14,'2016_01_23_202731_add_service_varibles',1);
INSERT INTO `migrations` VALUES (15,'2016_01_23_202943_add_services',1);
INSERT INTO `migrations` VALUES (16,'2016_01_23_203119_create_settings_table',1);
INSERT INTO `migrations` VALUES (17,'2016_01_23_203150_add_subusers',1);
INSERT INTO `migrations` VALUES (18,'2016_01_23_203159_add_users',1);
INSERT INTO `migrations` VALUES (19,'2016_01_23_203947_create_sessions_table',1);
INSERT INTO `migrations` VALUES (20,'2016_01_25_234418_rename_permissions_column',1);
INSERT INTO `migrations` VALUES (21,'2016_02_07_172148_add_databases_tables',1);
INSERT INTO `migrations` VALUES (22,'2016_02_07_181319_add_database_servers_table',1);
INSERT INTO `migrations` VALUES (23,'2016_02_13_154306_add_service_option_default_startup',1);
INSERT INTO `migrations` VALUES (24,'2016_02_20_155318_add_unique_service_field',1);
INSERT INTO `migrations` VALUES (25,'2016_02_27_163411_add_tasks_table',1);
INSERT INTO `migrations` VALUES (26,'2016_02_27_163447_add_tasks_log_table',1);
INSERT INTO `migrations` VALUES (27,'2016_03_18_155649_add_nullable_field_lastrun',1);
INSERT INTO `migrations` VALUES (28,'2016_08_30_212718_add_ip_alias',1);
INSERT INTO `migrations` VALUES (29,'2016_08_30_213301_modify_ip_storage_method',1);
INSERT INTO `migrations` VALUES (30,'2016_09_01_193520_add_suspension_for_servers',1);
INSERT INTO `migrations` VALUES (31,'2016_09_01_211924_remove_active_column',1);
INSERT INTO `migrations` VALUES (32,'2016_09_02_190647_add_sftp_password_storage',1);
INSERT INTO `migrations` VALUES (33,'2016_09_04_171338_update_jobs_tables',1);
INSERT INTO `migrations` VALUES (34,'2016_09_04_172028_update_failed_jobs_table',1);
INSERT INTO `migrations` VALUES (35,'2016_09_04_182835_create_notifications_table',1);
INSERT INTO `migrations` VALUES (36,'2016_09_07_163017_add_unique_identifier',1);
INSERT INTO `migrations` VALUES (37,'2016_09_14_145945_allow_longer_regex_field',1);
INSERT INTO `migrations` VALUES (38,'2016_09_17_194246_add_docker_image_column',1);
INSERT INTO `migrations` VALUES (39,'2016_09_21_165554_update_servers_column_name',1);
INSERT INTO `migrations` VALUES (40,'2016_09_29_213518_rename_double_insurgency',1);
INSERT INTO `migrations` VALUES (41,'2016_10_07_152117_build_api_log_table',1);
INSERT INTO `migrations` VALUES (42,'2016_10_14_164802_update_api_keys',1);
INSERT INTO `migrations` VALUES (43,'2016_10_23_181719_update_misnamed_bungee',1);
INSERT INTO `migrations` VALUES (44,'2016_10_23_193810_add_foreign_keys_servers',1);
INSERT INTO `migrations` VALUES (45,'2016_10_23_201624_add_foreign_allocations',1);
INSERT INTO `migrations` VALUES (46,'2016_10_23_202222_add_foreign_api_keys',1);
INSERT INTO `migrations` VALUES (47,'2016_10_23_202703_add_foreign_api_permissions',1);
INSERT INTO `migrations` VALUES (48,'2016_10_23_202953_add_foreign_database_servers',1);
INSERT INTO `migrations` VALUES (49,'2016_10_23_203105_add_foreign_databases',1);
INSERT INTO `migrations` VALUES (50,'2016_10_23_203335_add_foreign_nodes',1);
INSERT INTO `migrations` VALUES (51,'2016_10_23_203522_add_foreign_permissions',1);
INSERT INTO `migrations` VALUES (52,'2016_10_23_203857_add_foreign_server_variables',1);
INSERT INTO `migrations` VALUES (53,'2016_10_23_204157_add_foreign_service_options',1);
INSERT INTO `migrations` VALUES (54,'2016_10_23_204321_add_foreign_service_variables',1);
INSERT INTO `migrations` VALUES (55,'2016_10_23_204454_add_foreign_subusers',1);
INSERT INTO `migrations` VALUES (56,'2016_10_23_204610_add_foreign_tasks',1);
INSERT INTO `migrations` VALUES (57,'2016_11_04_000949_add_ark_service_option_fixed',1);
INSERT INTO `migrations` VALUES (58,'2016_11_11_220649_add_pack_support',1);
INSERT INTO `migrations` VALUES (59,'2016_11_11_231731_set_service_name_unique',1);
INSERT INTO `migrations` VALUES (60,'2016_11_27_142519_add_pack_column',1);
INSERT INTO `migrations` VALUES (61,'2016_12_01_173018_add_configurable_upload_limit',1);
INSERT INTO `migrations` VALUES (62,'2016_12_02_185206_correct_service_variables',1);
INSERT INTO `migrations` VALUES (63,'2017_01_03_150436_fix_misnamed_option_tag',1);
INSERT INTO `migrations` VALUES (64,'2017_01_07_154228_create_node_configuration_tokens_table',1);
INSERT INTO `migrations` VALUES (65,'2017_01_12_135449_add_more_user_data',1);
INSERT INTO `migrations` VALUES (66,'2017_02_02_175548_UpdateColumnNames',1);
INSERT INTO `migrations` VALUES (67,'2017_02_03_140948_UpdateNodesTable',1);
INSERT INTO `migrations` VALUES (68,'2017_02_03_155554_RenameColumns',1);
INSERT INTO `migrations` VALUES (69,'2017_02_05_164123_AdjustColumnNames',1);
INSERT INTO `migrations` VALUES (70,'2017_02_05_164516_AdjustColumnNamesForServicePacks',1);
INSERT INTO `migrations` VALUES (71,'2017_02_09_174834_SetupPermissionsPivotTable',1);
INSERT INTO `migrations` VALUES (72,'2017_02_10_171858_UpdateAPIKeyColumnNames',1);
INSERT INTO `migrations` VALUES (73,'2017_03_03_224254_UpdateNodeConfigTokensColumns',1);
INSERT INTO `migrations` VALUES (74,'2017_03_05_212803_DeleteServiceExecutableOption',1);
INSERT INTO `migrations` VALUES (75,'2017_03_10_162934_AddNewServiceOptionsColumns',1);
INSERT INTO `migrations` VALUES (76,'2017_03_10_173607_MigrateToNewServiceSystem',1);
INSERT INTO `migrations` VALUES (77,'2017_03_11_215455_ChangeServiceVariablesValidationRules',1);
INSERT INTO `migrations` VALUES (78,'2017_03_12_150648_MoveFunctionsFromFileToDatabase',1);
INSERT INTO `migrations` VALUES (79,'2017_03_14_175631_RenameServicePacksToSingluarPacks',1);
INSERT INTO `migrations` VALUES (80,'2017_03_14_200326_AddLockedStatusToTable',1);
INSERT INTO `migrations` VALUES (81,'2017_03_16_181109_ReOrganizeDatabaseServersToDatabaseHost',1);
INSERT INTO `migrations` VALUES (82,'2017_03_16_181515_CleanupDatabasesDatabase',1);
INSERT INTO `migrations` VALUES (83,'2017_03_18_204953_AddForeignKeyToPacks',1);
INSERT INTO `migrations` VALUES (84,'2017_03_31_221948_AddServerDescriptionColumn',1);
INSERT INTO `migrations` VALUES (85,'2017_04_02_163232_DropDeletedAtColumnFromServers',1);
INSERT INTO `migrations` VALUES (86,'2017_04_15_125021_UpgradeTaskSystem',1);
INSERT INTO `migrations` VALUES (87,'2017_04_20_171943_AddScriptsToServiceOptions',1);
INSERT INTO `migrations` VALUES (88,'2017_04_21_151432_AddServiceScriptTrackingToServers',1);
INSERT INTO `migrations` VALUES (89,'2017_04_27_145300_AddCopyScriptFromColumn',1);
INSERT INTO `migrations` VALUES (90,'2017_04_27_223629_AddAbilityToDefineConnectionOverSSLWithDaemonBehindProxy',1);
INSERT INTO `migrations` VALUES (91,'2017_05_01_141528_DeleteDownloadTable',1);
INSERT INTO `migrations` VALUES (92,'2017_05_01_141559_DeleteNodeConfigurationTable',1);
INSERT INTO `migrations` VALUES (93,'2017_06_10_152951_add_external_id_to_users',1);
INSERT INTO `migrations` VALUES (94,'2017_06_25_133923_ChangeForeignKeyToBeOnCascadeDelete',1);
INSERT INTO `migrations` VALUES (95,'2017_07_08_152806_ChangeUserPermissionsToDeleteOnUserDeletion',1);
INSERT INTO `migrations` VALUES (96,'2017_07_08_154416_SetAllocationToReferenceNullOnServerDelete',1);
INSERT INTO `migrations` VALUES (97,'2017_07_08_154650_CascadeDeletionWhenAServerOrVariableIsDeleted',1);
INSERT INTO `migrations` VALUES (98,'2017_07_24_194433_DeleteTaskWhenParentServerIsDeleted',1);
INSERT INTO `migrations` VALUES (99,'2017_08_05_115800_CascadeNullValuesForDatabaseHostWhenNodeIsDeleted',1);
INSERT INTO `migrations` VALUES (100,'2017_08_05_144104_AllowNegativeValuesForOverallocation',1);
INSERT INTO `migrations` VALUES (101,'2017_08_05_174811_SetAllocationUnqiueUsingMultipleFields',1);
INSERT INTO `migrations` VALUES (102,'2017_08_15_214555_CascadeDeletionWhenAParentServiceIsDeleted',1);
INSERT INTO `migrations` VALUES (103,'2017_08_18_215428_RemovePackWhenParentServiceOptionIsDeleted',1);
INSERT INTO `migrations` VALUES (104,'2017_09_10_225749_RenameTasksTableForStructureRefactor',1);
INSERT INTO `migrations` VALUES (105,'2017_09_10_225941_CreateSchedulesTable',1);
INSERT INTO `migrations` VALUES (106,'2017_09_10_230309_CreateNewTasksTableForSchedules',1);
INSERT INTO `migrations` VALUES (107,'2017_09_11_002938_TransferOldTasksToNewScheduler',1);
INSERT INTO `migrations` VALUES (108,'2017_09_13_211810_UpdateOldPermissionsToPointToNewScheduleSystem',1);
INSERT INTO `migrations` VALUES (109,'2017_09_23_170933_CreateDaemonKeysTable',1);
INSERT INTO `migrations` VALUES (110,'2017_09_23_173628_RemoveDaemonSecretFromServersTable',1);
INSERT INTO `migrations` VALUES (111,'2017_09_23_185022_RemoveDaemonSecretFromSubusersTable',1);
INSERT INTO `migrations` VALUES (112,'2017_10_02_202000_ChangeServicesToUseAMoreUniqueIdentifier',1);
INSERT INTO `migrations` VALUES (113,'2017_10_02_202007_ChangeToABetterUniqueServiceConfiguration',1);
INSERT INTO `migrations` VALUES (114,'2017_10_03_233202_CascadeDeletionWhenServiceOptionIsDeleted',1);
INSERT INTO `migrations` VALUES (115,'2017_10_06_214026_ServicesToNestsConversion',1);
INSERT INTO `migrations` VALUES (116,'2017_10_06_214053_ServiceOptionsToEggsConversion',1);
INSERT INTO `migrations` VALUES (117,'2017_10_06_215741_ServiceVariablesToEggVariablesConversion',1);
INSERT INTO `migrations` VALUES (118,'2017_10_24_222238_RemoveLegacySFTPInformation',1);
INSERT INTO `migrations` VALUES (119,'2017_11_11_161922_Add2FaLastAuthorizationTimeColumn',1);
INSERT INTO `migrations` VALUES (120,'2017_11_19_122708_MigratePubPrivFormatToSingleKey',1);
INSERT INTO `migrations` VALUES (121,'2017_12_04_184012_DropAllocationsWhenNodeIsDeleted',1);
INSERT INTO `migrations` VALUES (122,'2017_12_12_220426_MigrateSettingsTableToNewFormat',1);
INSERT INTO `migrations` VALUES (123,'2018_01_01_122821_AllowNegativeValuesForServerSwap',1);
INSERT INTO `migrations` VALUES (124,'2018_01_11_213943_AddApiKeyPermissionColumns',1);
INSERT INTO `migrations` VALUES (125,'2018_01_13_142012_SetupTableForKeyEncryption',1);
INSERT INTO `migrations` VALUES (126,'2018_01_13_145209_AddLastUsedAtColumn',1);
INSERT INTO `migrations` VALUES (127,'2018_02_04_145617_AllowTextInUserExternalId',1);
INSERT INTO `migrations` VALUES (128,'2018_02_10_151150_remove_unique_index_on_external_id_column',1);
INSERT INTO `migrations` VALUES (129,'2018_02_17_134254_ensure_unique_allocation_id_on_servers_table',1);
INSERT INTO `migrations` VALUES (130,'2018_02_24_112356_add_external_id_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (131,'2018_02_25_160152_remove_default_null_value_on_table',1);
INSERT INTO `migrations` VALUES (132,'2018_02_25_160604_define_unique_index_on_users_external_id',1);
INSERT INTO `migrations` VALUES (133,'2018_03_01_192831_add_database_and_port_limit_columns_to_servers_table',1);
INSERT INTO `migrations` VALUES (134,'2018_03_15_124536_add_description_to_nodes',1);
INSERT INTO `migrations` VALUES (135,'2018_05_04_123826_add_maintenance_to_nodes',1);
INSERT INTO `migrations` VALUES (136,'2018_09_03_143756_allow_egg_variables_to_have_longer_values',1);
INSERT INTO `migrations` VALUES (137,'2018_09_03_144005_allow_server_variables_to_have_longer_values',1);
INSERT INTO `migrations` VALUES (138,'2019_03_02_142328_set_allocation_limit_default_null',1);
INSERT INTO `migrations` VALUES (139,'2019_03_02_151321_fix_unique_index_to_account_for_host',1);
INSERT INTO `migrations` VALUES (140,'2020_03_22_163911_merge_permissions_table_into_subusers',1);
INSERT INTO `migrations` VALUES (141,'2020_03_22_164814_drop_permissions_table',1);
INSERT INTO `migrations` VALUES (142,'2020_04_03_203624_add_threads_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (143,'2020_04_03_230614_create_backups_table',1);
INSERT INTO `migrations` VALUES (144,'2020_04_04_131016_add_table_server_transfers',1);
INSERT INTO `migrations` VALUES (145,'2020_04_10_141024_store_node_tokens_as_encrypted_value',1);
INSERT INTO `migrations` VALUES (146,'2020_04_17_203438_allow_nullable_descriptions',1);
INSERT INTO `migrations` VALUES (147,'2020_04_22_055500_add_max_connections_column',1);
INSERT INTO `migrations` VALUES (148,'2020_04_26_111208_add_backup_limit_to_servers',1);
INSERT INTO `migrations` VALUES (149,'2020_05_20_234655_add_mounts_table',1);
INSERT INTO `migrations` VALUES (150,'2020_05_21_192756_add_mount_server_table',1);
INSERT INTO `migrations` VALUES (151,'2020_07_02_213612_create_user_recovery_tokens_table',1);
INSERT INTO `migrations` VALUES (152,'2020_07_09_201845_add_notes_column_for_allocations',1);
INSERT INTO `migrations` VALUES (153,'2020_08_20_205533_add_backup_state_column_to_backups',1);
INSERT INTO `migrations` VALUES (154,'2020_08_22_132500_update_bytes_to_unsigned_bigint',1);
INSERT INTO `migrations` VALUES (155,'2020_08_23_175331_modify_checksums_column_for_backups',1);
INSERT INTO `migrations` VALUES (156,'2020_09_13_110007_drop_packs_from_servers',1);
INSERT INTO `migrations` VALUES (157,'2020_09_13_110021_drop_packs_from_api_key_permissions',1);
INSERT INTO `migrations` VALUES (158,'2020_09_13_110047_drop_packs_table',1);
INSERT INTO `migrations` VALUES (159,'2020_09_13_113503_drop_daemon_key_table',1);
INSERT INTO `migrations` VALUES (160,'2020_10_10_165437_change_unique_database_name_to_account_for_server',1);
INSERT INTO `migrations` VALUES (161,'2020_10_26_194904_remove_nullable_from_schedule_name_field',1);
INSERT INTO `migrations` VALUES (162,'2020_11_02_201014_add_features_column_to_eggs',1);
INSERT INTO `migrations` VALUES (163,'2020_12_12_102435_support_multiple_docker_images_and_updates',1);
INSERT INTO `migrations` VALUES (164,'2020_12_14_013707_make_successful_nullable_in_server_transfers',1);
INSERT INTO `migrations` VALUES (165,'2020_12_17_014330_add_archived_field_to_server_transfers_table',1);
INSERT INTO `migrations` VALUES (166,'2020_12_24_092449_make_allocation_fields_json',1);
INSERT INTO `migrations` VALUES (167,'2020_12_26_184914_add_upload_id_column_to_backups_table',1);
INSERT INTO `migrations` VALUES (168,'2021_01_10_153937_add_file_denylist_to_egg_configs',1);
INSERT INTO `migrations` VALUES (169,'2021_01_13_013420_add_cron_month',1);
INSERT INTO `migrations` VALUES (170,'2021_01_17_102401_create_audit_logs_table',1);
INSERT INTO `migrations` VALUES (171,'2021_01_17_152623_add_generic_server_status_column',1);
INSERT INTO `migrations` VALUES (172,'2021_01_26_210502_update_file_denylist_to_json',1);
INSERT INTO `migrations` VALUES (173,'2021_02_23_205021_add_index_for_server_and_action',1);
INSERT INTO `migrations` VALUES (174,'2021_02_23_212657_make_sftp_port_unsigned_int',1);
INSERT INTO `migrations` VALUES (175,'2021_03_21_104718_force_cron_month_field_to_have_value_if_missing',1);
INSERT INTO `migrations` VALUES (176,'2021_05_01_092457_add_continue_on_failure_option_to_tasks',1);
INSERT INTO `migrations` VALUES (177,'2021_05_01_092523_add_only_run_when_server_online_option_to_schedules',1);
INSERT INTO `migrations` VALUES (178,'2021_05_03_201016_add_support_for_locking_a_backup',1);
INSERT INTO `migrations` VALUES (179,'2021_07_12_013420_remove_userinteraction',1);
INSERT INTO `migrations` VALUES (180,'2021_07_17_211512_create_user_ssh_keys_table',1);
INSERT INTO `migrations` VALUES (181,'2021_08_03_210600_change_successful_field_to_default_to_false_on_backups_table',1);
INSERT INTO `migrations` VALUES (182,'2021_08_21_175111_add_foreign_keys_to_mount_node_table',1);
INSERT INTO `migrations` VALUES (183,'2021_08_21_175118_add_foreign_keys_to_mount_server_table',1);
INSERT INTO `migrations` VALUES (184,'2021_08_21_180921_add_foreign_keys_to_egg_mount_table',1);
INSERT INTO `migrations` VALUES (185,'2022_01_25_030847_drop_google_analytics',1);
INSERT INTO `migrations` VALUES (186,'2022_05_07_165334_migrate_egg_images_array_to_new_format',1);
INSERT INTO `migrations` VALUES (187,'2022_05_28_135717_create_activity_logs_table',1);
INSERT INTO `migrations` VALUES (188,'2022_05_29_140349_create_activity_log_actors_table',1);
INSERT INTO `migrations` VALUES (189,'2022_06_18_112822_track_api_key_usage_for_activity_events',1);
INSERT INTO `migrations` VALUES (190,'2022_08_16_214400_add_force_outgoing_ip_column_to_eggs_table',1);
INSERT INTO `migrations` VALUES (191,'2022_08_16_230204_add_installed_at_column_to_servers_table',1);
INSERT INTO `migrations` VALUES (192,'2022_12_12_213937_update_mail_settings_to_new_format',1);
INSERT INTO `migrations` VALUES (193,'2023_01_24_210051_add_uuid_column_to_failed_jobs_table',1);
INSERT INTO `migrations` VALUES (194,'2023_02_23_191004_add_expires_at_column_to_api_keys_table',1);
