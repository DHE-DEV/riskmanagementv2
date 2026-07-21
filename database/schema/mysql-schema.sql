/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `ai_cost_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_cost_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `ai_quota_id` bigint unsigned DEFAULT NULL,
  `alert_type` enum('cost_warning','cost_critical','quota_exceeded','error_threshold','system_health','daily_report','weekly_report') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `severity` enum('info','warning','critical','emergency') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `trigger_source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `threshold_value` decimal(10,2) DEFAULT NULL,
  `current_value` decimal(10,2) DEFAULT NULL,
  `limit_value` decimal(10,2) DEFAULT NULL,
  `metric_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `context_data` json DEFAULT NULL,
  `model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `period_date` date DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed','acknowledged','resolved') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `channels` json DEFAULT NULL,
  `delivery_status` json DEFAULT NULL,
  `delivery_error` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `triggered_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `alert_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `occurrence_count` int NOT NULL,
  `first_occurrence_at` timestamp NULL DEFAULT NULL,
  `last_occurrence_at` timestamp NULL DEFAULT NULL,
  `actions_taken` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_examples`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_examples` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `input_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expected_output` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `prompt_template_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_job_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_job_progress` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batch_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_items` int NOT NULL,
  `processed_items` int NOT NULL,
  `failed_items` int NOT NULL,
  `progress_percentage` decimal(5,2) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `error_details` json DEFAULT NULL,
  `tokens_used` int NOT NULL,
  `cost_accumulated` decimal(10,4) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_processing_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_processing_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `disaster_event_id` bigint unsigned NOT NULL,
  `prompt_template_id` bigint unsigned NOT NULL,
  `input_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `output_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `model_used` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tokens_used` int unsigned NOT NULL,
  `processing_time` double NOT NULL,
  `status` enum('pending','success','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_prompts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_prompts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prompt_template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_quotas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_quotas` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `quota_type` enum('global','user','model','endpoint') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resource_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `resource_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `period_type` enum('hourly','daily','weekly','monthly','yearly') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `limit_value` int unsigned NOT NULL,
  `used_value` int unsigned NOT NULL,
  `cost_limit` decimal(10,2) DEFAULT NULL,
  `cost_used` decimal(10,2) DEFAULT NULL,
  `cost_per_unit` decimal(8,6) DEFAULT NULL,
  `auto_reset` tinyint(1) DEFAULT NULL,
  `period_start` datetime NOT NULL,
  `period_end` datetime NOT NULL,
  `last_reset_at` datetime DEFAULT NULL,
  `alert_thresholds` json DEFAULT NULL,
  `alert_settings` json DEFAULT NULL,
  `status` enum('active','suspended','exceeded','expired') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `enforce_limit` tinyint(1) DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_system_health`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_system_health` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('healthy','degraded','unhealthy','unknown') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `response_time_ms` double DEFAULT NULL,
  `success_rate_percentage` int DEFAULT NULL,
  `error_count_last_hour` int NOT NULL,
  `request_count_last_hour` int NOT NULL,
  `cost_last_hour` decimal(8,4) DEFAULT NULL,
  `error_details` json DEFAULT NULL,
  `performance_metrics` json DEFAULT NULL,
  `quota_status` json DEFAULT NULL,
  `health_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `last_check_at` timestamp NOT NULL,
  `last_success_at` timestamp NULL DEFAULT NULL,
  `last_failure_at` timestamp NULL DEFAULT NULL,
  `configuration` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_usage_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ai_usage_tracking` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `request_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `operation_type` enum('classification','synthesis','communication','analysis','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `request_data` json DEFAULT NULL,
  `response_data` json DEFAULT NULL,
  `input_tokens` int NOT NULL,
  `output_tokens` int NOT NULL,
  `total_tokens` int NOT NULL,
  `cost_usd` decimal(10,6) DEFAULT NULL,
  `processing_time` double NOT NULL,
  `status` enum('success','failed','timeout','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `retry_count` int NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `airline_airport`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `airline_airport` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `airline_id` bigint unsigned NOT NULL,
  `airport_id` bigint unsigned NOT NULL,
  `direction` enum('from','to','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `terminal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `airline_airport_airline_id_airport_id_unique` (`airline_id`,`airport_id`) USING BTREE,
  KEY `airline_airport_airport_id_foreign` (`airport_id`) USING BTREE,
  CONSTRAINT `airline_airport_airline_id_foreign` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `airline_airport_airport_id_foreign` FOREIGN KEY (`airport_id`) REFERENCES `airports` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `airline_airport_code`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `airline_airport_code` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `airline_id` bigint unsigned NOT NULL,
  `airport_code_id` bigint unsigned NOT NULL,
  `direction` enum('from','to','both') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'both',
  `terminal` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `airline_airport_code_airline_id_airport_code_id_direction_unique` (`airline_id`,`airport_code_id`,`direction`) USING BTREE,
  KEY `airline_airport_code_airport_code_id_foreign` (`airport_code_id`) USING BTREE,
  CONSTRAINT `airline_airport_code_airline_id_foreign` FOREIGN KEY (`airline_id`) REFERENCES `airlines` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `airline_airport_code_airport_code_id_foreign` FOREIGN KEY (`airport_code_id`) REFERENCES `airport_codes_1` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `airlines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `airlines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `iata_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icao_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_country_id` bigint unsigned DEFAULT NULL,
  `headquarters` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `website` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_info` json DEFAULT NULL,
  `baggage_rules` json DEFAULT NULL,
  `cabin_classes` json DEFAULT NULL,
  `pet_policy` json DEFAULT NULL,
  `lounges` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `airlines_iata_code_unique` (`iata_code`) USING BTREE,
  UNIQUE KEY `airlines_icao_code_unique` (`icao_code`) USING BTREE,
  KEY `airlines_home_country_id_foreign` (`home_country_id`) USING BTREE,
  CONSTRAINT `airlines_home_country_id_foreign` FOREIGN KEY (`home_country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `airport_codes_1`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `airport_codes_1` (
  `id` bigint unsigned NOT NULL,
  `ident` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude_deg` decimal(15,8) DEFAULT NULL,
  `longitude_deg` decimal(15,8) DEFAULT NULL,
  `elevation_ft` int DEFAULT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dst_timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `continent` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso_country` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso_region` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `municipality` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_service` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `operates_24h` tinyint(1) NOT NULL DEFAULT '0',
  `lounges` json DEFAULT NULL,
  `nearby_hotels` json DEFAULT NULL,
  `mobility_options` json DEFAULT NULL,
  `icao_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iata_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gps_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `home_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `website` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `wikipedia_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `security_timeslot_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `airport_codes_1_ident_index` (`ident`) USING BTREE,
  KEY `airport_codes_1_iata_code_index` (`iata_code`) USING BTREE,
  KEY `airport_codes_1_icao_code_index` (`icao_code`) USING BTREE,
  KEY `airport_codes_1_iso_country_index` (`iso_country`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `airport_codes_2`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `airport_codes_2` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ident` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `elevation_ft` int DEFAULT NULL,
  `continent` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso_country` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iso_region` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `municipality` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icao_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `iata_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gps_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `local_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coordinates` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(15,8) DEFAULT NULL,
  `longitude` decimal(15,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `airport_codes_2_ident_index` (`ident`) USING BTREE,
  KEY `airport_codes_2_iata_code_index` (`iata_code`) USING BTREE,
  KEY `airport_codes_2_icao_code_index` (`icao_code`) USING BTREE,
  KEY `airport_codes_2_iso_country_index` (`iso_country`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `airports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `airports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iata_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icao_code` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city_id` bigint unsigned NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  `website` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lat` decimal(20,16) DEFAULT NULL,
  `lng` decimal(20,16) DEFAULT NULL,
  `altitude` int DEFAULT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `dst_timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `type` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `operates_24h` tinyint(1) NOT NULL DEFAULT '0',
  `lounges` json DEFAULT NULL,
  `nearby_hotels` json DEFAULT NULL,
  `mobility_options` json DEFAULT NULL,
  `security_timeslot_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_client_event_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_client_event_group` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_client_id` bigint unsigned NOT NULL,
  `event_group_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `api_client_event_group_api_client_id_event_group_id_unique` (`api_client_id`,`event_group_id`) USING BTREE,
  KEY `api_client_event_group_event_group_id_foreign` (`event_group_id`) USING BTREE,
  CONSTRAINT `api_client_event_group_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `api_client_event_group_event_group_id_foreign` FOREIGN KEY (`event_group_id`) REFERENCES `event_groups` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_client_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_client_request_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_client_id` bigint unsigned NOT NULL,
  `token_id` bigint unsigned DEFAULT NULL,
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `query_params` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `response_status` int NOT NULL,
  `response_time_ms` int DEFAULT NULL,
  `created_at` timestamp NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `api_client_request_logs_api_client_id_index` (`api_client_id`) USING BTREE,
  KEY `api_client_request_logs_created_at_index` (`created_at`) USING BTREE,
  CONSTRAINT `api_client_request_logs_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `auto_approve_events` tinyint(1) NOT NULL DEFAULT '0',
  `can_create_events` tinyint(1) NOT NULL DEFAULT '0',
  `rate_limit` int NOT NULL DEFAULT '60',
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `api_clients_status_index` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `booking_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `booking_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `booking_locations_type_index` (`type`) USING BTREE,
  KEY `booking_locations_postal_code_index` (`postal_code`) USING BTREE,
  KEY `booking_locations_latitude_longitude_index` (`latitude`,`longitude`) USING BTREE,
  KEY `booking_locations_branch_id_foreign` (`branch_id`) USING BTREE,
  KEY `booking_locations_customer_id_foreign` (`customer_id`) USING BTREE,
  CONSTRAINT `booking_locations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `booking_locations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `salutation` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `function` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `branch_contacts_branch_id_foreign` (`branch_id`) USING BTREE,
  CONSTRAINT `branch_contacts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_exports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_exports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `filename` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `count` int NOT NULL DEFAULT '0',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `branch_exports_expires_at_index` (`expires_at`) USING BTREE,
  KEY `branch_exports_customer_id_created_at_index` (`customer_id`,`created_at`) USING BTREE,
  KEY `branch_exports_customer_id_status_index` (`customer_id`,`status`) USING BTREE,
  CONSTRAINT `branch_exports_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_org_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_org_node` (
  `branch_id` bigint unsigned NOT NULL,
  `org_node_id` bigint unsigned NOT NULL,
  `customer_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contract_number` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`branch_id`,`org_node_id`) USING BTREE,
  KEY `branch_org_node_org_node_id_foreign` (`org_node_id`) USING BTREE,
  CONSTRAINT `branch_org_node_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `branch_org_node_org_node_id_foreign` FOREIGN KEY (`org_node_id`) REFERENCES `org_nodes` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `app_code` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `additional` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `is_headquarters` tinyint(1) NOT NULL DEFAULT '0',
  `scheduled_deletion_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `branches_app_code_unique` (`app_code`) USING BTREE,
  KEY `branches_customer_id_foreign` (`customer_id`) USING BTREE,
  CONSTRAINT `branches_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expiration` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_translations` json NOT NULL,
  `country_id` bigint unsigned NOT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `population` int unsigned DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `is_capital` tinyint(1) DEFAULT NULL,
  `is_regional_capital` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `city_custom_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `city_custom_event` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `city_id` bigint unsigned NOT NULL,
  `custom_event_id` bigint unsigned NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `use_default_coordinates` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `city_custom_event_city_id_custom_event_id_unique` (`city_id`,`custom_event_id`) USING BTREE,
  KEY `city_custom_event_custom_event_id_foreign` (`custom_event_id`) USING BTREE,
  CONSTRAINT `city_custom_event_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `city_custom_event_custom_event_id_foreign` FOREIGN KEY (`custom_event_id`) REFERENCES `custom_events` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `continents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `continents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_translations` json NOT NULL,
  `code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sort_order` int NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `keywords` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `countries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `iso_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `iso3_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name_translations` json NOT NULL,
  `is_eu_member` tinyint(1) DEFAULT NULL,
  `is_schengen_member` tinyint(1) DEFAULT NULL,
  `currency_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `currency_symbol` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone_prefix` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `languages` json DEFAULT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `risk_factors` json DEFAULT NULL,
  `travel_advisories` json DEFAULT NULL,
  `climate_zones` json DEFAULT NULL,
  `risk_profile` json DEFAULT NULL,
  `population` int unsigned DEFAULT NULL,
  `area_km2` decimal(12,2) DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `continent_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `country_custom_event`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `country_custom_event` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned NOT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `custom_event_id` bigint unsigned NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `use_default_coordinates` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `country_custom_event_region_id_foreign` (`region_id`) USING BTREE,
  KEY `country_custom_event_city_id_foreign` (`city_id`) USING BTREE,
  CONSTRAINT `country_custom_event_city_id_foreign` FOREIGN KEY (`city_id`) REFERENCES `cities` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `country_custom_event_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_event_event_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_event_event_type` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_event_id` bigint unsigned NOT NULL,
  `event_type_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_event_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_event_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_event_id` bigint unsigned NOT NULL,
  `label_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `custom_event_label_custom_event_id_label_id_unique` (`custom_event_id`,`label_id`) USING BTREE,
  KEY `custom_event_label_label_id_foreign` (`label_id`) USING BTREE,
  CONSTRAINT `custom_event_label_custom_event_id_foreign` FOREIGN KEY (`custom_event_id`) REFERENCES `custom_events` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `custom_event_label_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_event_org_node`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_event_org_node` (
  `custom_event_id` bigint unsigned NOT NULL,
  `org_node_id` bigint unsigned NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  PRIMARY KEY (`custom_event_id`,`org_node_id`) USING BTREE,
  KEY `custom_event_org_node_org_node_id_foreign` (`org_node_id`) USING BTREE,
  CONSTRAINT `custom_event_org_node_custom_event_id_foreign` FOREIGN KEY (`custom_event_id`) REFERENCES `custom_events` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `custom_event_org_node_org_node_id_foreign` FOREIGN KEY (`org_node_id`) REFERENCES `org_nodes` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_event_region`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_event_region` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_event_id` bigint unsigned NOT NULL,
  `region_id` bigint unsigned NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `location_note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `use_default_coordinates` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `custom_event_region_custom_event_id_region_id_unique` (`custom_event_id`,`region_id`) USING BTREE,
  KEY `custom_event_region_region_id_foreign` (`region_id`) USING BTREE,
  CONSTRAINT `custom_event_region_custom_event_id_foreign` FOREIGN KEY (`custom_event_id`) REFERENCES `custom_events` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `custom_event_region_region_id_foreign` FOREIGN KEY (`region_id`) REFERENCES `regions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `custom_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `custom_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `event_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_type_id` bigint unsigned DEFAULT NULL,
  `selected_display_event_type_id` bigint unsigned DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `severity` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `latitude` decimal(24,16) DEFAULT NULL,
  `longitude` decimal(24,16) DEFAULT NULL,
  `marker_color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marker_icon` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `icon_color` varchar(7) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marker_size` enum('small','medium','large') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `popup_content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_show_frontend` tinyint(1) NOT NULL DEFAULT '1',
  `source_link_text` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source_link_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` timestamp NOT NULL,
  `end_date` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `visible_community` tinyint(1) NOT NULL DEFAULT '0',
  `community_start_date` date DEFAULT NULL,
  `community_end_date` date DEFAULT NULL,
  `visible_organization` tinyint(1) NOT NULL DEFAULT '1',
  `organization_start_date` date DEFAULT NULL,
  `organization_end_date` date DEFAULT NULL,
  `archived` tinyint(1) DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `priority` enum('info','low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `api_client_id` bigint unsigned DEFAULT NULL,
  `review_status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'approved',
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `event_category_id` bigint unsigned DEFAULT NULL,
  `data_source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_source_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `custom_events_uuid_unique` (`uuid`) USING BTREE,
  KEY `custom_events_reviewed_by_foreign` (`reviewed_by`) USING BTREE,
  KEY `custom_events_review_status_index` (`review_status`) USING BTREE,
  KEY `custom_events_api_client_id_review_status_index` (`api_client_id`,`review_status`) USING BTREE,
  KEY `custom_events_customer_id_index` (`customer_id`) USING BTREE,
  CONSTRAINT `custom_events_api_client_id_foreign` FOREIGN KEY (`api_client_id`) REFERENCES `api_clients` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `custom_events_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `custom_events_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_access` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `accessor_customer_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_access_customer_id_accessor_customer_id_unique` (`customer_id`,`accessor_customer_id`),
  KEY `customer_access_accessor_customer_id_foreign` (`accessor_customer_id`),
  CONSTRAINT `customer_access_accessor_customer_id_foreign` FOREIGN KEY (`accessor_customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_access_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_feature_overrides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_feature_overrides` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `navigation_events_enabled` tinyint(1) DEFAULT NULL,
  `navigation_entry_conditions_enabled` tinyint(1) DEFAULT NULL,
  `navigation_booking_enabled` tinyint(1) DEFAULT NULL,
  `navigation_airports_enabled` tinyint(1) DEFAULT NULL,
  `navigation_branches_enabled` tinyint(1) DEFAULT NULL,
  `navigation_my_travelers_enabled` tinyint(1) DEFAULT NULL,
  `navigation_risk_overview_enabled` tinyint(1) DEFAULT NULL,
  `navigation_cruise_enabled` tinyint(1) DEFAULT NULL,
  `navigation_business_visa_enabled` tinyint(1) DEFAULT NULL,
  `navigation_center_map_enabled` tinyint(1) DEFAULT NULL,
  `navigation_visumpoint_enabled` tinyint(1) DEFAULT NULL,
  `navigation_customer_events_enabled` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `customer_feature_overrides_customer_id_unique` (`customer_id`) USING BTREE,
  CONSTRAINT `customer_feature_overrides_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `assign_to` bigint unsigned DEFAULT NULL,
  `app_code` varchar(4) COLLATE utf8mb4_unicode_ci NOT NULL,
  `agent_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service1_customer_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pds_customer_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_client_account_id` int unsigned DEFAULT NULL,
  `legacy_passolution_company_id` tinyint unsigned DEFAULT NULL,
  `legacy_account_id` int unsigned DEFAULT NULL,
  `legacy_organization_id` bigint unsigned DEFAULT NULL,
  `legacy_language_id` tinyint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` json DEFAULT NULL,
  `account_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `customer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `business_type` json DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_additional` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_house_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_additional` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_house_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passolution_access_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passolution_token_expires_at` timestamp NULL DEFAULT NULL,
  `passolution_refresh_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `passolution_refresh_token_expires_at` timestamp NULL DEFAULT NULL,
  `passolution_subscription_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passolution_roles` json DEFAULT NULL,
  `pds_api_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'PDS API access token for pds-api calls',
  `pds_api_token_expires_at` timestamp NULL DEFAULT NULL COMMENT 'Expiration timestamp for the PDS API token',
  `passolution_features` json DEFAULT NULL,
  `passolution_subscription_updated_at` timestamp NULL DEFAULT NULL,
  `hide_profile_completion` tinyint(1) NOT NULL DEFAULT '0',
  `directory_listing_active` tinyint(1) NOT NULL DEFAULT '0',
  `branch_management_active` tinyint(1) NOT NULL DEFAULT '0',
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_password_md5` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_code_expires_at` timestamp NULL DEFAULT NULL,
  `avatar` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `provider` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `provider_refresh_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `auto_refresh_travelers` tinyint(1) NOT NULL DEFAULT '0',
  `travelers_refresh_interval` int NOT NULL DEFAULT '30' COMMENT 'Auto-refresh interval in seconds',
  `gtm_api_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `pds_sync_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `pds_last_synced_at` timestamp NULL DEFAULT NULL,
  `travel_links_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `gtm_api_rate_limit` int unsigned NOT NULL DEFAULT '60',
  `notifications_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_platform_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_travel_alert_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_gtm_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_trs_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_entry_conditions_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_travel_data_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_travel_links_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_booking_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_airports_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_branches_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_my_travelers_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_customer_events_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_cruise_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_business_visa_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_visumpoint_tour` tinyint(1) NOT NULL DEFAULT '0',
  `has_seen_settings_tour` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `unique_agent_customer` (`agent_id`,`service1_customer_id`) USING BTREE,
  KEY `customers_provider_provider_id_index` (`provider`,`provider_id`) USING BTREE,
  KEY `customers_email_index` (`email`),
  KEY `customers_assign_to_foreign` (`assign_to`),
  CONSTRAINT `customers_assign_to_foreign` FOREIGN KEY (`assign_to`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers_legacy_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers_legacy_options` (
  `account_id` int unsigned NOT NULL,
  `account_type` int DEFAULT '1' COMMENT '1=Testaccount VA, 2=Testaccount RB, 3=Veranstalter, 4=ReisebÃ¼ro, 5=Reisebarater',
  `client_type` int DEFAULT '1',
  `office_count` int DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revised` tinyint NOT NULL DEFAULT '0',
  `live_from` date DEFAULT NULL,
  `end_of_use` date DEFAULT NULL,
  `zoho_crm_id` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_visa_service` int DEFAULT NULL,
  `show_visa_service_link` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_visa_service_text` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visa_places` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DE',
  `show_travel_warning` tinyint DEFAULT '1',
  `travel_warning_country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'de',
  `response_api_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0.6',
  `response_api_status` int unsigned DEFAULT '1',
  `agency_address_position` int DEFAULT NULL,
  `use_report` int DEFAULT '2',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `myjack_agency_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `providers` json NOT NULL,
  `tech_access` int DEFAULT '0',
  `cooperations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort_order` int NOT NULL DEFAULT '0',
  `customer_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `departments_customer_id_foreign` (`customer_id`) USING BTREE,
  CONSTRAINT `departments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `disaster_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `disaster_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `severity` enum('low','medium','high','critical') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_type` enum('earthquake','hurricane','flood','wildfire','volcano','tsunami','drought','tornado','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_type_id` bigint unsigned DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `radius_km` decimal(8,2) DEFAULT NULL,
  `country_id` bigint unsigned DEFAULT NULL,
  `region_id` bigint unsigned DEFAULT NULL,
  `city_id` bigint unsigned DEFAULT NULL,
  `affected_areas` json DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` datetime DEFAULT NULL,
  `end_time` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `impact_assessment` json DEFAULT NULL,
  `travel_recommendations` json DEFAULT NULL,
  `official_sources` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `media_coverage` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `tourism_impact` json DEFAULT NULL,
  `external_sources` json NOT NULL,
  `last_updated` datetime NOT NULL,
  `confidence_score` int NOT NULL,
  `processing_status` enum('pending','processed','failed','none') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ai_summary` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ai_recommendations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `crisis_communication` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `keywords` json DEFAULT NULL,
  `magnitude` int DEFAULT NULL,
  `casualties` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `economic_impact` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `infrastructure_damage` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `emergency_response` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `recovery_status` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_gdacs` tinyint(1) DEFAULT NULL,
  `gdacs_event_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_episode_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_alert_level` enum('Green','Orange','Red') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_alert_score` int DEFAULT NULL,
  `gdacs_episode_alert_level` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_episode_alert_score` int DEFAULT NULL,
  `gdacs_event_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_calculation_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_severity_value` decimal(8,2) DEFAULT NULL,
  `gdacs_severity_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_severity_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gdacs_population_value` bigint DEFAULT NULL,
  `gdacs_population_unit` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_population_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gdacs_vulnerability` decimal(10,6) DEFAULT NULL,
  `gdacs_iso3` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_glide` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gdacs_bbox` json DEFAULT NULL,
  `gdacs_cap_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gdacs_icon_url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gdacs_version` int DEFAULT NULL,
  `gdacs_temporary` tinyint(1) DEFAULT NULL,
  `gdacs_is_current` tinyint(1) DEFAULT NULL,
  `gdacs_duration_weeks` int DEFAULT NULL,
  `gdacs_resources` json DEFAULT NULL,
  `gdacs_map_image` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gdacs_map_link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `gdacs_date_added` datetime DEFAULT NULL,
  `gdacs_date_modified` datetime DEFAULT NULL,
  `weather_conditions` json DEFAULT NULL,
  `evacuation_info` json DEFAULT NULL,
  `transportation_impact` json DEFAULT NULL,
  `accommodation_impact` json DEFAULT NULL,
  `communication_status` json DEFAULT NULL,
  `health_services_status` json DEFAULT NULL,
  `utility_services_status` json DEFAULT NULL,
  `border_crossings_status` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `disaster_events_uuid_unique` (`uuid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `email_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort_order` int NOT NULL DEFAULT '0',
  `customer_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `department_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `email_addresses_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `email_addresses_department_id_foreign` (`department_id`) USING BTREE,
  KEY `email_addresses_branch_id_foreign` (`branch_id`) USING BTREE,
  CONSTRAINT `email_addresses_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `email_addresses_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `email_addresses_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_employee_group`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_employee_group` (
  `employee_id` bigint unsigned NOT NULL,
  `employee_group_id` bigint unsigned NOT NULL,
  PRIMARY KEY (`employee_id`,`employee_group_id`),
  KEY `employee_employee_group_employee_group_id_foreign` (`employee_group_id`),
  CONSTRAINT `employee_employee_group_employee_group_id_foreign` FOREIGN KEY (`employee_group_id`) REFERENCES `employee_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `employee_employee_group_employee_id_foreign` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employee_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `employee_groups_customer_id_foreign` (`customer_id`),
  CONSTRAINT `employee_groups_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `salutation` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `personnel_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `active_from` date DEFAULT NULL,
  `active_until` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `legacy_usersweb_id` int unsigned DEFAULT NULL,
  `legacy_client_account_id` int unsigned DEFAULT NULL,
  `legacy_usersweb_assignto` bigint unsigned DEFAULT NULL,
  `legacy_usersweb_idpaymentuser` bigint unsigned DEFAULT NULL,
  `legacy_usersweb_idcontact` bigint unsigned DEFAULT NULL,
  `legacy_usersweb_username` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_usersweb_role` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_usersweb_revised` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `legacy_usersweb_level` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `employees_legacy_usersweb_id_unique` (`legacy_usersweb_id`),
  KEY `employees_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `employees_branch_id_foreign` (`branch_id`) USING BTREE,
  KEY `employees_department_id_foreign` (`department_id`) USING BTREE,
  KEY `employees_legacy_client_account_id_index` (`legacy_client_account_id`),
  CONSTRAINT `employees_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `employees_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `employees_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `entry_conditions_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entry_conditions_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `filters` json NOT NULL COMMENT 'Selected filter checkboxes',
  `nationality` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_body` json NOT NULL COMMENT 'Full API request body sent to Passolution',
  `response_data` json DEFAULT NULL COMMENT 'API response from Passolution',
  `response_status` int DEFAULT NULL COMMENT 'HTTP status code',
  `results_count` int DEFAULT NULL COMMENT 'Number of destinations returned',
  `success` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Whether the request was successful',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Error message if request failed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `entry_conditions_logs_created_at_index` (`created_at`) USING BTREE,
  KEY `entry_conditions_logs_nationality_index` (`nationality`) USING BTREE,
  KEY `entry_conditions_logs_success_index` (`success`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_clicks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_clicks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `custom_event_id` bigint unsigned NOT NULL,
  `click_type` enum('list','map_marker','details_button') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `session_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `clicked_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_display_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_display_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `multi_event_icon_strategy` enum('default','manual_select','multi_event_type','show_all','show_icon_preview') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `multi_event_type_id` bigint unsigned DEFAULT NULL,
  `show_icon_preview_in_form` tinyint(1) DEFAULT NULL,
  `strategy_description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_groups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `include_passolution_events` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `event_groups_slug_unique` (`slug`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_types` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `connection` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `queue` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `failed_at` timestamp NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_car_rental_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_car_rental_services` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `itinerary_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `rental_company` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `pickup_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_lat` decimal(10,8) DEFAULT NULL,
  `pickup_lng` decimal(11,8) DEFAULT NULL,
  `pickup_datetime` timestamp NOT NULL,
  `return_location` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `return_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_lat` decimal(10,8) DEFAULT NULL,
  `return_lng` decimal(11,8) DEFAULT NULL,
  `return_datetime` timestamp NOT NULL,
  `vehicle_category` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_make_model` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transmission` enum('manual','automatic') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuel_type` enum('petrol','diesel','electric','hybrid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rental_days` int unsigned DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `insurance_options` json DEFAULT NULL,
  `extras` json DEFAULT NULL,
  `status` enum('pending','confirmed','picked_up','returned','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_car_rental_services_itinerary_id_foreign` (`itinerary_id`) USING BTREE,
  KEY `folder_car_rental_services_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `folder_car_rental_services_customer_id_itinerary_id_index` (`customer_id`,`itinerary_id`) USING BTREE,
  KEY `folder_car_rental_services_pickup_datetime_return_datetime_index` (`pickup_datetime`,`return_datetime`) USING BTREE,
  KEY `folder_car_rental_services_pickup_lat_pickup_lng_index` (`pickup_lat`,`pickup_lng`) USING BTREE,
  KEY `folder_car_rental_services_pickup_country_code_index` (`pickup_country_code`) USING BTREE,
  KEY `folder_car_rental_services_pickup_datetime_index` (`pickup_datetime`) USING BTREE,
  KEY `folder_car_rental_services_return_country_code_index` (`return_country_code`) USING BTREE,
  KEY `folder_car_rental_services_return_datetime_index` (`return_datetime`) USING BTREE,
  CONSTRAINT `folder_car_rental_services_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_car_rental_services_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_car_rental_services_itinerary_id_foreign` FOREIGN KEY (`itinerary_id`) REFERENCES `folder_itineraries` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_customers` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `salutation` enum('mr','mrs','diverse') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `birth_date` date DEFAULT NULL,
  `nationality` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_customers_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `folder_customers_folder_id_customer_id_index` (`folder_id`,`customer_id`) USING BTREE,
  CONSTRAINT `folder_customers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_customers_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_flight_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_flight_segments` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `flight_service_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `segment_number` int unsigned NOT NULL DEFAULT '1',
  `departure_airport_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `departure_airport_id` bigint unsigned DEFAULT NULL,
  `departure_lat` decimal(10,8) DEFAULT NULL,
  `departure_lng` decimal(11,8) DEFAULT NULL,
  `departure_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departure_country_id` bigint unsigned DEFAULT NULL,
  `departure_time` timestamp NOT NULL,
  `departure_terminal` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_airport_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `arrival_airport_id` bigint unsigned DEFAULT NULL,
  `arrival_lat` decimal(10,8) DEFAULT NULL,
  `arrival_lng` decimal(11,8) DEFAULT NULL,
  `arrival_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_country_id` bigint unsigned DEFAULT NULL,
  `arrival_time` timestamp NOT NULL,
  `arrival_terminal` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `airline_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flight_number` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `aircraft_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duration_minutes` int unsigned DEFAULT NULL,
  `booking_class` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cabin_class` enum('economy','premium_economy','business','first') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'economy',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_flight_segments_flight_service_id_foreign` (`flight_service_id`) USING BTREE,
  KEY `folder_flight_segments_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `idx_segment_customer_flight` (`customer_id`,`flight_service_id`) USING BTREE,
  KEY `idx_segment_airports` (`departure_airport_code`,`arrival_airport_code`) USING BTREE,
  KEY `idx_segment_dep_coords` (`departure_lat`,`departure_lng`) USING BTREE,
  KEY `idx_segment_arr_coords` (`arrival_lat`,`arrival_lng`) USING BTREE,
  KEY `folder_flight_segments_departure_airport_code_index` (`departure_airport_code`) USING BTREE,
  KEY `folder_flight_segments_departure_country_code_index` (`departure_country_code`) USING BTREE,
  KEY `folder_flight_segments_departure_time_index` (`departure_time`) USING BTREE,
  KEY `folder_flight_segments_arrival_airport_code_index` (`arrival_airport_code`) USING BTREE,
  KEY `folder_flight_segments_arrival_country_code_index` (`arrival_country_code`) USING BTREE,
  KEY `folder_flight_segments_arrival_time_index` (`arrival_time`) USING BTREE,
  KEY `folder_flight_segments_departure_airport_id_index` (`departure_airport_id`) USING BTREE,
  KEY `folder_flight_segments_arrival_airport_id_index` (`arrival_airport_id`) USING BTREE,
  KEY `folder_flight_segments_departure_country_id_index` (`departure_country_id`) USING BTREE,
  KEY `folder_flight_segments_arrival_country_id_index` (`arrival_country_id`) USING BTREE,
  CONSTRAINT `folder_flight_segments_arrival_airport_id_foreign` FOREIGN KEY (`arrival_airport_id`) REFERENCES `airport_codes_1` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_segments_arrival_country_id_foreign` FOREIGN KEY (`arrival_country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_segments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_segments_departure_airport_id_foreign` FOREIGN KEY (`departure_airport_id`) REFERENCES `airport_codes_1` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_segments_departure_country_id_foreign` FOREIGN KEY (`departure_country_id`) REFERENCES `countries` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_segments_flight_service_id_foreign` FOREIGN KEY (`flight_service_id`) REFERENCES `folder_flight_services` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_segments_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_flight_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_flight_services` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `itinerary_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `booking_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_type` enum('outbound','return','multi_leg') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'outbound',
  `departure_time` timestamp NULL DEFAULT NULL,
  `arrival_time` timestamp NULL DEFAULT NULL,
  `origin_airport_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_airport_code` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `airline_pnr` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ticket_numbers` json DEFAULT NULL,
  `status` enum('pending','ticketed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_flight_services_itinerary_id_foreign` (`itinerary_id`) USING BTREE,
  KEY `folder_flight_services_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `folder_flight_services_customer_id_itinerary_id_index` (`customer_id`,`itinerary_id`) USING BTREE,
  KEY `idx_flight_origin_dest` (`origin_airport_code`,`destination_airport_code`) USING BTREE,
  KEY `folder_flight_services_departure_time_index` (`departure_time`) USING BTREE,
  KEY `folder_flight_services_arrival_time_index` (`arrival_time`) USING BTREE,
  KEY `folder_flight_services_origin_airport_code_index` (`origin_airport_code`) USING BTREE,
  KEY `folder_flight_services_destination_airport_code_index` (`destination_airport_code`) USING BTREE,
  KEY `folder_flight_services_origin_country_code_index` (`origin_country_code`) USING BTREE,
  KEY `folder_flight_services_destination_country_code_index` (`destination_country_code`) USING BTREE,
  CONSTRAINT `folder_flight_services_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_services_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_flight_services_itinerary_id_foreign` FOREIGN KEY (`itinerary_id`) REFERENCES `folder_itineraries` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_folders` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `folder_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `travel_start_date` date DEFAULT NULL,
  `travel_end_date` date DEFAULT NULL,
  `destinations_visited` json DEFAULT NULL,
  `primary_destination` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','confirmed','active','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `travel_type` enum('business','leisure','mixed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'leisure',
  `agent_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `custom_field_1_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_field_1_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `custom_field_2_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_field_2_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `custom_field_3_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_field_3_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `custom_field_4_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_field_4_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `custom_field_5_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custom_field_5_value` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_participants` int unsigned NOT NULL DEFAULT '0',
  `total_itineraries` int unsigned NOT NULL DEFAULT '0',
  `total_value` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `folder_folders_folder_number_unique` (`folder_number`) USING BTREE,
  KEY `folder_folders_customer_id_index` (`customer_id`) USING BTREE,
  KEY `folder_folders_customer_id_status_index` (`customer_id`,`status`) USING BTREE,
  KEY `folder_folders_travel_start_date_travel_end_date_index` (`travel_start_date`,`travel_end_date`) USING BTREE,
  KEY `folder_folders_travel_start_date_index` (`travel_start_date`) USING BTREE,
  KEY `folder_folders_travel_end_date_index` (`travel_end_date`) USING BTREE,
  KEY `folder_folders_primary_destination_index` (`primary_destination`) USING BTREE,
  KEY `folder_folders_status_index` (`status`) USING BTREE,
  CONSTRAINT `folder_folders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_hotel_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_hotel_services` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `itinerary_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `hotel_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `hotel_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotel_code_type` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `point` point DEFAULT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `nights` int unsigned DEFAULT NULL,
  `room_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_count` int unsigned NOT NULL DEFAULT '1',
  `board_type` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `status` enum('pending','confirmed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_hotel_services_itinerary_id_foreign` (`itinerary_id`) USING BTREE,
  KEY `folder_hotel_services_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `folder_hotel_services_customer_id_itinerary_id_index` (`customer_id`,`itinerary_id`) USING BTREE,
  KEY `folder_hotel_services_check_in_date_check_out_date_index` (`check_in_date`,`check_out_date`) USING BTREE,
  KEY `folder_hotel_services_lat_lng_index` (`lat`,`lng`) USING BTREE,
  KEY `folder_hotel_services_city_index` (`city`) USING BTREE,
  KEY `folder_hotel_services_country_code_index` (`country_code`) USING BTREE,
  KEY `folder_hotel_services_check_in_date_index` (`check_in_date`) USING BTREE,
  KEY `folder_hotel_services_check_out_date_index` (`check_out_date`) USING BTREE,
  CONSTRAINT `folder_hotel_services_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_hotel_services_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_hotel_services_itinerary_id_foreign` FOREIGN KEY (`itinerary_id`) REFERENCES `folder_itineraries` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_import_logs` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `import_source` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('pending','processing','completed','failed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `source_data` json DEFAULT NULL,
  `mapping_config` json DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `records_imported` int unsigned NOT NULL DEFAULT '0',
  `records_failed` int unsigned NOT NULL DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_import_logs_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `folder_import_logs_customer_id_status_index` (`customer_id`,`status`) USING BTREE,
  CONSTRAINT `folder_import_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_import_logs_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_itineraries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_itineraries` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `booking_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `itinerary_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `payment_status` enum('unpaid','partial','paid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'unpaid',
  `provider_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_reference` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_itineraries_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `folder_itineraries_folder_id_customer_id_index` (`folder_id`,`customer_id`) USING BTREE,
  KEY `folder_itineraries_start_date_end_date_index` (`start_date`,`end_date`) USING BTREE,
  KEY `folder_itineraries_start_date_index` (`start_date`) USING BTREE,
  KEY `folder_itineraries_end_date_index` (`end_date`) USING BTREE,
  KEY `folder_itineraries_status_index` (`status`) USING BTREE,
  CONSTRAINT `folder_itineraries_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_itineraries_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_itinerary_participant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_itinerary_participant` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `itinerary_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `participant_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `folder_itinerary_participant_itinerary_id_participant_id_unique` (`itinerary_id`,`participant_id`) USING BTREE,
  KEY `folder_itinerary_participant_participant_id_foreign` (`participant_id`) USING BTREE,
  KEY `folder_itinerary_participant_customer_id_index` (`customer_id`) USING BTREE,
  CONSTRAINT `folder_itinerary_participant_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_itinerary_participant_itinerary_id_foreign` FOREIGN KEY (`itinerary_id`) REFERENCES `folder_itineraries` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_itinerary_participant_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `folder_participants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `folder_label_folder_id_label_id_unique` (`folder_id`,`label_id`) USING BTREE,
  KEY `folder_label_label_id_foreign` (`label_id`) USING BTREE,
  CONSTRAINT `folder_label_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_label_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_participants` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `salutation` enum('mr','mrs','child','infant','diverse') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `birth_date` date DEFAULT NULL,
  `nationality` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_number` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_issue_date` date DEFAULT NULL,
  `passport_expiry_date` date DEFAULT NULL,
  `passport_issuing_country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dietary_requirements` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `medical_conditions` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_main_contact` tinyint(1) NOT NULL DEFAULT '0',
  `participant_type` enum('adult','child','infant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'adult',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_participants_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `folder_participants_folder_id_customer_id_index` (`folder_id`,`customer_id`) USING BTREE,
  KEY `folder_participants_nationality_index` (`nationality`) USING BTREE,
  CONSTRAINT `folder_participants_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_participants_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_ship_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_ship_services` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `itinerary_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `ship_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cruise_line` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ship_code` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embarkation_date` date NOT NULL,
  `disembarkation_date` date NOT NULL,
  `nights` int unsigned DEFAULT NULL,
  `embarkation_port` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embarkation_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `embarkation_lat` decimal(10,8) DEFAULT NULL,
  `embarkation_lng` decimal(11,8) DEFAULT NULL,
  `disembarkation_port` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disembarkation_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `disembarkation_lat` decimal(10,8) DEFAULT NULL,
  `disembarkation_lng` decimal(11,8) DEFAULT NULL,
  `cabin_number` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cabin_type` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cabin_category` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deck` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `currency` varchar(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `status` enum('pending','confirmed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `port_calls` json DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_ship_services_itinerary_id_foreign` (`itinerary_id`) USING BTREE,
  KEY `folder_ship_services_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `folder_ship_services_customer_id_itinerary_id_index` (`customer_id`,`itinerary_id`) USING BTREE,
  KEY `folder_ship_services_embarkation_date_disembarkation_date_index` (`embarkation_date`,`disembarkation_date`) USING BTREE,
  KEY `folder_ship_services_embarkation_lat_embarkation_lng_index` (`embarkation_lat`,`embarkation_lng`) USING BTREE,
  KEY `folder_ship_services_embarkation_date_index` (`embarkation_date`) USING BTREE,
  KEY `folder_ship_services_disembarkation_date_index` (`disembarkation_date`) USING BTREE,
  KEY `folder_ship_services_embarkation_country_code_index` (`embarkation_country_code`) USING BTREE,
  CONSTRAINT `folder_ship_services_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_ship_services_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_ship_services_itinerary_id_foreign` FOREIGN KEY (`itinerary_id`) REFERENCES `folder_itineraries` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folder_timeline_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folder_timeline_locations` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `folder_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `itinerary_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `location_type` enum('flight_departure','flight_arrival','hotel','cruise_embark','cruise_disembark','cruise_port','car_pickup','car_return') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` enum('flight_segment','hotel_service','ship_service','car_rental_service') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL,
  `point` point DEFAULT NULL,
  `location_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  `participant_ids` json DEFAULT NULL,
  `participant_nationalities` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `folder_timeline_locations_folder_id_foreign` (`folder_id`) USING BTREE,
  KEY `folder_timeline_locations_itinerary_id_foreign` (`itinerary_id`) USING BTREE,
  KEY `folder_timeline_locations_customer_id_folder_id_index` (`customer_id`,`folder_id`) USING BTREE,
  KEY `folder_timeline_locations_customer_id_start_time_end_time_index` (`customer_id`,`start_time`,`end_time`) USING BTREE,
  KEY `folder_timeline_locations_start_time_end_time_country_code_index` (`start_time`,`end_time`,`country_code`) USING BTREE,
  KEY `folder_timeline_locations_lat_lng_start_time_end_time_index` (`lat`,`lng`,`start_time`,`end_time`) USING BTREE,
  KEY `folder_timeline_locations_lat_index` (`lat`) USING BTREE,
  KEY `folder_timeline_locations_lng_index` (`lng`) USING BTREE,
  KEY `folder_timeline_locations_location_code_index` (`location_code`) USING BTREE,
  KEY `folder_timeline_locations_country_code_index` (`country_code`) USING BTREE,
  KEY `folder_timeline_locations_start_time_index` (`start_time`) USING BTREE,
  KEY `folder_timeline_locations_end_time_index` (`end_time`) USING BTREE,
  CONSTRAINT `folder_timeline_locations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_timeline_locations_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folder_folders` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `folder_timeline_locations_itinerary_id_foreign` FOREIGN KEY (`itinerary_id`) REFERENCES `folder_itineraries` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `gtm_api_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `gtm_api_request_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `token_id` bigint unsigned DEFAULT NULL,
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `endpoint` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `query_params` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `response_status` smallint unsigned NOT NULL,
  `response_time_ms` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  KEY `idx_customer_created` (`customer_id`,`created_at`) USING BTREE,
  KEY `idx_created_at` (`created_at`) USING BTREE,
  KEY `idx_response_status` (`response_status`,`created_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `info_source_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `info_source_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `info_source_id` bigint unsigned NOT NULL,
  `external_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ID aus der Quelle',
  `title` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `link` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `author` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categories` json DEFAULT NULL,
  `countries` json DEFAULT NULL COMMENT 'Erkannte Länder-Codes',
  `published_at` timestamp NULL DEFAULT NULL,
  `updated_at_source` timestamp NULL DEFAULT NULL,
  `status` enum('new','reviewed','imported','ignored') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `imported_as_event_id` bigint unsigned DEFAULT NULL COMMENT 'ID des erstellten CustomEvents',
  `raw_data` json DEFAULT NULL COMMENT 'Originaldaten aus der Quelle',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `info_source_items_info_source_id_external_id_unique` (`info_source_id`,`external_id`) USING BTREE,
  KEY `info_source_items_status_created_at_index` (`status`,`created_at`) USING BTREE,
  KEY `info_source_items_published_at_index` (`published_at`) USING BTREE,
  CONSTRAINT `info_source_items_info_source_id_foreign` FOREIGN KEY (`info_source_id`) REFERENCES `info_sources` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `info_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `info_sources` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `type` enum('rss','api','rss_api') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rss',
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `api_endpoint` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `api_key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_config` json DEFAULT NULL,
  `content_type` enum('travel_advisory','health','disaster','conflict','general') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ISO-2 Code für länderspezifische Quellen',
  `language` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `refresh_interval` int NOT NULL DEFAULT '3600' COMMENT 'Aktualisierungsintervall in Sekunden',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `auto_import` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'Events automatisch importieren',
  `last_fetched_at` timestamp NULL DEFAULT NULL,
  `last_error_at` timestamp NULL DEFAULT NULL,
  `last_error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `info_sources_code_unique` (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `infosystem_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `infosystem_entries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_id` bigint unsigned NOT NULL,
  `position` int NOT NULL,
  `appearance` int NOT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country_names` json DEFAULT NULL,
  `lang` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `language_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `language_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tagtype` int DEFAULT NULL,
  `categories` json DEFAULT NULL,
  `tagtext` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tagdate` date NOT NULL,
  `header` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `content` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `archive` tinyint(1) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT NULL,
  `published_at` timestamp NULL DEFAULT NULL,
  `published_as_event_id` bigint unsigned DEFAULT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `api_created_at` timestamp NULL DEFAULT NULL,
  `request_id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `response_time` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `labels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `labels` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3B82F6',
  `icon` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'fa-tag',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `labels_customer_id_index` (`customer_id`) USING BTREE,
  CONSTRAINT `labels_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_rule_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `event_id` bigint unsigned DEFAULT NULL,
  `event_type` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `recipient_email` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `template_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `rule_name` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci DEFAULT NULL,
  `is_test` tinyint(1) NOT NULL DEFAULT '0',
  `status` enum('sent','failed') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'sent',
  `error_message` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `affected_trips_count` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  UNIQUE KEY `idx_rule_event_unique` (`notification_rule_id`,`event_id`,`event_type`,`created_at`) USING BTREE,
  KEY `idx_notification_rule_id` (`notification_rule_id`) USING BTREE,
  KEY `idx_customer_id` (`customer_id`) USING BTREE,
  KEY `idx_event` (`event_id`,`event_type`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_customer_created` (`customer_id`,`created_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_queue_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_queue_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue_name` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `events_processed` int unsigned NOT NULL DEFAULT '0',
  `notifications_sent` int unsigned NOT NULL DEFAULT '0',
  `errors` int unsigned NOT NULL DEFAULT '0',
  `status` enum('running','completed','failed') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'running',
  `error_message` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`started_at`) USING BTREE,
  KEY `idx_queue_name` (`queue_name`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_queue_started` (`queue_name`,`started_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_rule_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_rule_recipients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_rule_id` bigint unsigned NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_type` enum('to','cc','bcc') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `notification_rule_recipients_notification_rule_id_foreign` (`notification_rule_id`) USING BTREE,
  CONSTRAINT `notification_rule_recipients_notification_rule_id_foreign` FOREIGN KEY (`notification_rule_id`) REFERENCES `notification_rules` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'travel-alert',
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `risk_levels` json DEFAULT NULL,
  `categories` json DEFAULT NULL,
  `country_ids` json DEFAULT NULL,
  `notification_template_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `notification_rules_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `notification_rules_notification_template_id_foreign` (`notification_template_id`) USING BTREE,
  KEY `notification_rules_source_index` (`source`) USING BTREE,
  CONSTRAINT `notification_rules_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `notification_rules_notification_template_id_foreign` FOREIGN KEY (`notification_template_id`) REFERENCES `notification_templates` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `source` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body_html` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `notification_templates_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `notification_templates_source_index` (`source`) USING BTREE,
  CONSTRAINT `notification_templates_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_unsubscribe_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_unsubscribe_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_rule_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned NOT NULL,
  `unsubscribed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `notification_unsubscribe_tokens_token_unique` (`token`) USING BTREE,
  KEY `notification_unsubscribe_tokens_notification_rule_id_foreign` (`notification_rule_id`) USING BTREE,
  KEY `notification_unsubscribe_tokens_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `notification_unsubscribe_tokens_email_index` (`email`) USING BTREE,
  CONSTRAINT `notification_unsubscribe_tokens_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `notification_unsubscribe_tokens_notification_rule_id_foreign` FOREIGN KEY (`notification_rule_id`) REFERENCES `notification_rules` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `notifiable_id` bigint unsigned NOT NULL,
  `data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `org_nodes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `org_nodes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `parent_id` bigint unsigned DEFAULT NULL,
  `relation_label` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '#3b82f6',
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `org_nodes_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `org_nodes_parent_id_foreign` (`parent_id`) USING BTREE,
  CONSTRAINT `org_nodes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `org_nodes_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `org_nodes` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pds_trip_label`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pds_trip_label` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `pds_tid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label_id` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `pds_trip_label_customer_id_pds_tid_label_id_unique` (`customer_id`,`pds_tid`,`label_id`) USING BTREE,
  KEY `pds_trip_label_label_id_foreign` (`label_id`) USING BTREE,
  KEY `pds_trip_label_customer_id_pds_tid_index` (`customer_id`,`pds_tid`) USING BTREE,
  CONSTRAINT `pds_trip_label_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `pds_trip_label_label_id_foreign` FOREIGN KEY (`label_id`) REFERENCES `labels` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`) USING BTREE,
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`) USING BTREE,
  KEY `personal_access_tokens_expires_at_index` (`expires_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `phone_numbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `phone_numbers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort_order` int NOT NULL DEFAULT '0',
  `customer_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'phone',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `department_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `phone_numbers_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `phone_numbers_department_id_foreign` (`department_id`) USING BTREE,
  KEY `phone_numbers_branch_id_foreign` (`branch_id`) USING BTREE,
  CONSTRAINT `phone_numbers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `phone_numbers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `phone_numbers_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plugin_clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `company_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `house_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','inactive','suspended') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `allow_app_access` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `plugin_clients_status_index` (`status`) USING BTREE,
  KEY `plugin_clients_customer_id_foreign` (`customer_id`) USING BTREE,
  CONSTRAINT `plugin_clients_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plugin_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_domains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `plugin_client_id` bigint unsigned NOT NULL,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `plugin_domains_plugin_client_id_domain_unique` (`plugin_client_id`,`domain`) USING BTREE,
  UNIQUE KEY `plugin_domains_uuid_unique` (`uuid`),
  KEY `plugin_domains_domain_index` (`domain`) USING BTREE,
  CONSTRAINT `plugin_domains_plugin_client_id_foreign` FOREIGN KEY (`plugin_client_id`) REFERENCES `plugin_clients` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plugin_email_verifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_email_verifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `form_data` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL DEFAULT '0',
  `expires_at` timestamp NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `plugin_email_verifications_token_unique` (`token`) USING BTREE,
  KEY `plugin_email_verifications_email_code_index` (`email`,`code`) USING BTREE,
  KEY `plugin_email_verifications_email_index` (`email`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plugin_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_client_id` bigint unsigned NOT NULL,
  `public_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `plugin_keys_public_key_unique` (`public_key`) USING BTREE,
  KEY `plugin_keys_plugin_client_id_is_active_index` (`plugin_client_id`,`is_active`) USING BTREE,
  CONSTRAINT `plugin_keys_plugin_client_id_foreign` FOREIGN KEY (`plugin_client_id`) REFERENCES `plugin_clients` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plugin_usage_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plugin_usage_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plugin_client_id` bigint unsigned NOT NULL,
  `public_key` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `domain` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'page_load',
  `meta` json DEFAULT NULL,
  `ip_hash` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `plugin_usage_events_plugin_client_id_created_at_index` (`plugin_client_id`,`created_at`) USING BTREE,
  KEY `plugin_usage_events_public_key_created_at_index` (`public_key`,`created_at`) USING BTREE,
  KEY `plugin_usage_events_event_type_index` (`event_type`) USING BTREE,
  CONSTRAINT `plugin_usage_events_plugin_client_id_foreign` FOREIGN KEY (`plugin_client_id`) REFERENCES `plugin_clients` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prompt_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `prompt_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `template` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `variables` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `regions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `regions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name_translations` json NOT NULL,
  `code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country_id` bigint unsigned NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `keywords` json DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `last_activity` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `share_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `share_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `views` bigint unsigned NOT NULL DEFAULT '0',
  `created_by_ip` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `share_links_token_unique` (`token`) USING BTREE,
  KEY `share_links_type_index` (`type`) USING BTREE,
  KEY `share_links_expires_at_index` (`expires_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `platform` varchar(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `latitude` decimal(10,6) DEFAULT NULL,
  `longitude` decimal(10,6) DEFAULT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sso_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sso_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Unique identifier for tracking a complete SSO flow',
  `step` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Current step in SSO flow',
  `version_idp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SSO version of Identity Provider (pds-homepage)',
  `version_sp` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'SSO version of Service Provider (riskmanagementv2)',
  `status` enum('success','error','warning','info') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Status of this SSO step',
  `method` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'HTTP method (GET, POST, etc.)',
  `url` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Full request URL',
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Client IP address (supports IPv4 and IPv6)',
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Client user agent string',
  `jwt_payload` json DEFAULT NULL COMMENT 'Decoded JWT payload as JSON',
  `jwt_token` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Raw JWT token string',
  `ott` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'One-time token for authentication',
  `customer_id` bigint unsigned DEFAULT NULL COMMENT 'Foreign key to customers table',
  `agent_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Agent identifier from SSO provider',
  `service1_customer_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Customer ID from Service1/external SSO provider',
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Error message if step failed',
  `error_trace` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci COMMENT 'Full error stack trace for debugging',
  `request_data` json DEFAULT NULL COMMENT 'Full request data as JSON',
  `response_data` json DEFAULT NULL COMMENT 'Response data as JSON',
  `duration_ms` int DEFAULT NULL COMMENT 'Duration of this step in milliseconds',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `sso_logs_request_id_index` (`request_id`) USING BTREE,
  KEY `sso_logs_step_index` (`step`) USING BTREE,
  KEY `sso_logs_status_index` (`status`) USING BTREE,
  KEY `sso_logs_customer_id_index` (`customer_id`) USING BTREE,
  KEY `sso_logs_agent_id_index` (`agent_id`) USING BTREE,
  KEY `idx_request_id_created_at` (`request_id`,`created_at`) USING BTREE,
  KEY `idx_customer_id_created_at` (`customer_id`,`created_at`) USING BTREE,
  KEY `idx_status_created_at` (`status`,`created_at`) USING BTREE,
  KEY `idx_version_idp` (`version_idp`) USING BTREE,
  KEY `idx_version_sp` (`version_sp`) USING BTREE,
  CONSTRAINT `sso_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_air_legs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_air_legs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `leg_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` enum('air','rail','bus','ferry','car') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'air',
  `leg_start_at` timestamp NULL DEFAULT NULL,
  `leg_end_at` timestamp NULL DEFAULT NULL,
  `total_duration_minutes` int unsigned DEFAULT NULL,
  `segment_count` int unsigned NOT NULL DEFAULT '0',
  `origin_airport_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origin_lat` decimal(10,8) DEFAULT NULL,
  `origin_lng` decimal(11,8) DEFAULT NULL,
  `origin_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_airport_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `destination_lat` decimal(10,8) DEFAULT NULL,
  `destination_lng` decimal(11,8) DEFAULT NULL,
  `destination_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `idx_trip_leg` (`trip_id`,`leg_id`) USING BTREE,
  KEY `idx_leg_dates` (`leg_start_at`,`leg_end_at`) USING BTREE,
  KEY `idx_origin_coords` (`origin_lat`,`origin_lng`) USING BTREE,
  KEY `idx_destination_coords` (`destination_lat`,`destination_lng`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
/*!50100 PARTITION BY RANGE (((year(`created_at`) * 100) + month(`created_at`)))
(PARTITION p202501 VALUES LESS THAN (202502) ENGINE = InnoDB,
 PARTITION p202502 VALUES LESS THAN (202503) ENGINE = InnoDB,
 PARTITION p202503 VALUES LESS THAN (202504) ENGINE = InnoDB,
 PARTITION p202504 VALUES LESS THAN (202505) ENGINE = InnoDB,
 PARTITION p202505 VALUES LESS THAN (202506) ENGINE = InnoDB,
 PARTITION p202506 VALUES LESS THAN (202507) ENGINE = InnoDB,
 PARTITION p202507 VALUES LESS THAN (202508) ENGINE = InnoDB,
 PARTITION p202508 VALUES LESS THAN (202509) ENGINE = InnoDB,
 PARTITION p202509 VALUES LESS THAN (202510) ENGINE = InnoDB,
 PARTITION p202510 VALUES LESS THAN (202511) ENGINE = InnoDB,
 PARTITION p202511 VALUES LESS THAN (202512) ENGINE = InnoDB,
 PARTITION p202512 VALUES LESS THAN (202601) ENGINE = InnoDB,
 PARTITION p202601 VALUES LESS THAN (202602) ENGINE = InnoDB,
 PARTITION p202602 VALUES LESS THAN (202603) ENGINE = InnoDB,
 PARTITION p202603 VALUES LESS THAN (202604) ENGINE = InnoDB,
 PARTITION p202604 VALUES LESS THAN (202605) ENGINE = InnoDB,
 PARTITION p202605 VALUES LESS THAN (202606) ENGINE = InnoDB,
 PARTITION p202606 VALUES LESS THAN (202607) ENGINE = InnoDB,
 PARTITION p202607 VALUES LESS THAN (202608) ENGINE = InnoDB,
 PARTITION p202608 VALUES LESS THAN (202609) ENGINE = InnoDB,
 PARTITION p202609 VALUES LESS THAN (202610) ENGINE = InnoDB,
 PARTITION p202610 VALUES LESS THAN (202611) ENGINE = InnoDB,
 PARTITION p202611 VALUES LESS THAN (202612) ENGINE = InnoDB,
 PARTITION p202612 VALUES LESS THAN (202701) ENGINE = InnoDB,
 PARTITION p202701 VALUES LESS THAN (202702) ENGINE = InnoDB,
 PARTITION p202702 VALUES LESS THAN (202703) ENGINE = InnoDB,
 PARTITION p202703 VALUES LESS THAN (202704) ENGINE = InnoDB,
 PARTITION p202704 VALUES LESS THAN (202705) ENGINE = InnoDB,
 PARTITION p202705 VALUES LESS THAN (202706) ENGINE = InnoDB,
 PARTITION p202706 VALUES LESS THAN (202707) ENGINE = InnoDB,
 PARTITION p202707 VALUES LESS THAN (202708) ENGINE = InnoDB,
 PARTITION p202708 VALUES LESS THAN (202709) ENGINE = InnoDB,
 PARTITION p202709 VALUES LESS THAN (202710) ENGINE = InnoDB,
 PARTITION p202710 VALUES LESS THAN (202711) ENGINE = InnoDB,
 PARTITION p202711 VALUES LESS THAN (202712) ENGINE = InnoDB,
 PARTITION p202712 VALUES LESS THAN (202801) ENGINE = InnoDB,
 PARTITION p202801 VALUES LESS THAN (202802) ENGINE = InnoDB,
 PARTITION p202802 VALUES LESS THAN (202803) ENGINE = InnoDB,
 PARTITION p202803 VALUES LESS THAN (202804) ENGINE = InnoDB,
 PARTITION p202804 VALUES LESS THAN (202805) ENGINE = InnoDB,
 PARTITION p202805 VALUES LESS THAN (202806) ENGINE = InnoDB,
 PARTITION p202806 VALUES LESS THAN (202807) ENGINE = InnoDB,
 PARTITION p202807 VALUES LESS THAN (202808) ENGINE = InnoDB,
 PARTITION p202808 VALUES LESS THAN (202809) ENGINE = InnoDB,
 PARTITION p202809 VALUES LESS THAN (202810) ENGINE = InnoDB,
 PARTITION p202810 VALUES LESS THAN (202811) ENGINE = InnoDB,
 PARTITION p202811 VALUES LESS THAN (202812) ENGINE = InnoDB,
 PARTITION p202812 VALUES LESS THAN (202901) ENGINE = InnoDB,
 PARTITION p202901 VALUES LESS THAN (202902) ENGINE = InnoDB,
 PARTITION p202902 VALUES LESS THAN (202903) ENGINE = InnoDB,
 PARTITION p202903 VALUES LESS THAN (202904) ENGINE = InnoDB,
 PARTITION p202904 VALUES LESS THAN (202905) ENGINE = InnoDB,
 PARTITION p202905 VALUES LESS THAN (202906) ENGINE = InnoDB,
 PARTITION p202906 VALUES LESS THAN (202907) ENGINE = InnoDB,
 PARTITION p202907 VALUES LESS THAN (202908) ENGINE = InnoDB,
 PARTITION p202908 VALUES LESS THAN (202909) ENGINE = InnoDB,
 PARTITION p202909 VALUES LESS THAN (202910) ENGINE = InnoDB,
 PARTITION p202910 VALUES LESS THAN (202911) ENGINE = InnoDB,
 PARTITION p202911 VALUES LESS THAN (202912) ENGINE = InnoDB,
 PARTITION p202912 VALUES LESS THAN (203001) ENGINE = InnoDB,
 PARTITION p203001 VALUES LESS THAN (203002) ENGINE = InnoDB,
 PARTITION p203002 VALUES LESS THAN (203003) ENGINE = InnoDB,
 PARTITION p203003 VALUES LESS THAN (203004) ENGINE = InnoDB,
 PARTITION p203004 VALUES LESS THAN (203005) ENGINE = InnoDB,
 PARTITION p203005 VALUES LESS THAN (203006) ENGINE = InnoDB,
 PARTITION p203006 VALUES LESS THAN (203007) ENGINE = InnoDB,
 PARTITION p203007 VALUES LESS THAN (203008) ENGINE = InnoDB,
 PARTITION p203008 VALUES LESS THAN (203009) ENGINE = InnoDB,
 PARTITION p203009 VALUES LESS THAN (203010) ENGINE = InnoDB,
 PARTITION p203010 VALUES LESS THAN (203011) ENGINE = InnoDB,
 PARTITION p203011 VALUES LESS THAN (203012) ENGINE = InnoDB,
 PARTITION p203012 VALUES LESS THAN (203101) ENGINE = InnoDB,
 PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_flight_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_flight_segments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `air_leg_id` bigint unsigned NOT NULL,
  `trip_id` bigint unsigned NOT NULL,
  `segment_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence_in_leg` int unsigned NOT NULL DEFAULT '0',
  `departure_airport_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `departure_lat` decimal(10,8) DEFAULT NULL,
  `departure_lng` decimal(11,8) DEFAULT NULL,
  `departure_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `departure_time` timestamp NOT NULL,
  `departure_terminal` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_airport_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `arrival_lat` decimal(10,8) DEFAULT NULL,
  `arrival_lng` decimal(11,8) DEFAULT NULL,
  `arrival_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `arrival_time` timestamp NOT NULL,
  `arrival_terminal` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marketing_airline_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flight_number` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `operating_airline_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_role_hint` enum('in','out','none') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `duration_minutes` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  KEY `idx_leg_sequence` (`air_leg_id`,`sequence_in_leg`) USING BTREE,
  KEY `idx_trip_segment` (`trip_id`) USING BTREE,
  KEY `idx_departure_time` (`departure_time`) USING BTREE,
  KEY `idx_arrival_time` (`arrival_time`) USING BTREE,
  KEY `idx_departure_coords` (`departure_lat`,`departure_lng`) USING BTREE,
  KEY `idx_arrival_coords` (`arrival_lat`,`arrival_lng`) USING BTREE,
  KEY `idx_departure_airport` (`departure_airport_code`) USING BTREE,
  KEY `idx_arrival_airport` (`arrival_airport_code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_import_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_import_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_trip_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` enum('create','update','error') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failed','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `error_details` json DEFAULT NULL,
  `request_payload` json DEFAULT NULL,
  `response_payload` json DEFAULT NULL,
  `processing_time_ms` int unsigned DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  KEY `idx_provider` (`provider_id`) USING BTREE,
  KEY `idx_created_at` (`created_at`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_pds_share_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_pds_share_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `share_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NULL DEFAULT NULL,
  `view_count` int unsigned NOT NULL DEFAULT '0',
  `last_viewed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `idx_trip_share` (`trip_id`) USING BTREE,
  KEY `idx_tid` (`tid`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
/*!50100 PARTITION BY RANGE (((year(`created_at`) * 100) + month(`created_at`)))
(PARTITION p202501 VALUES LESS THAN (202502) ENGINE = InnoDB,
 PARTITION p202502 VALUES LESS THAN (202503) ENGINE = InnoDB,
 PARTITION p202503 VALUES LESS THAN (202504) ENGINE = InnoDB,
 PARTITION p202504 VALUES LESS THAN (202505) ENGINE = InnoDB,
 PARTITION p202505 VALUES LESS THAN (202506) ENGINE = InnoDB,
 PARTITION p202506 VALUES LESS THAN (202507) ENGINE = InnoDB,
 PARTITION p202507 VALUES LESS THAN (202508) ENGINE = InnoDB,
 PARTITION p202508 VALUES LESS THAN (202509) ENGINE = InnoDB,
 PARTITION p202509 VALUES LESS THAN (202510) ENGINE = InnoDB,
 PARTITION p202510 VALUES LESS THAN (202511) ENGINE = InnoDB,
 PARTITION p202511 VALUES LESS THAN (202512) ENGINE = InnoDB,
 PARTITION p202512 VALUES LESS THAN (202601) ENGINE = InnoDB,
 PARTITION p202601 VALUES LESS THAN (202602) ENGINE = InnoDB,
 PARTITION p202602 VALUES LESS THAN (202603) ENGINE = InnoDB,
 PARTITION p202603 VALUES LESS THAN (202604) ENGINE = InnoDB,
 PARTITION p202604 VALUES LESS THAN (202605) ENGINE = InnoDB,
 PARTITION p202605 VALUES LESS THAN (202606) ENGINE = InnoDB,
 PARTITION p202606 VALUES LESS THAN (202607) ENGINE = InnoDB,
 PARTITION p202607 VALUES LESS THAN (202608) ENGINE = InnoDB,
 PARTITION p202608 VALUES LESS THAN (202609) ENGINE = InnoDB,
 PARTITION p202609 VALUES LESS THAN (202610) ENGINE = InnoDB,
 PARTITION p202610 VALUES LESS THAN (202611) ENGINE = InnoDB,
 PARTITION p202611 VALUES LESS THAN (202612) ENGINE = InnoDB,
 PARTITION p202612 VALUES LESS THAN (202701) ENGINE = InnoDB,
 PARTITION p202701 VALUES LESS THAN (202702) ENGINE = InnoDB,
 PARTITION p202702 VALUES LESS THAN (202703) ENGINE = InnoDB,
 PARTITION p202703 VALUES LESS THAN (202704) ENGINE = InnoDB,
 PARTITION p202704 VALUES LESS THAN (202705) ENGINE = InnoDB,
 PARTITION p202705 VALUES LESS THAN (202706) ENGINE = InnoDB,
 PARTITION p202706 VALUES LESS THAN (202707) ENGINE = InnoDB,
 PARTITION p202707 VALUES LESS THAN (202708) ENGINE = InnoDB,
 PARTITION p202708 VALUES LESS THAN (202709) ENGINE = InnoDB,
 PARTITION p202709 VALUES LESS THAN (202710) ENGINE = InnoDB,
 PARTITION p202710 VALUES LESS THAN (202711) ENGINE = InnoDB,
 PARTITION p202711 VALUES LESS THAN (202712) ENGINE = InnoDB,
 PARTITION p202712 VALUES LESS THAN (202801) ENGINE = InnoDB,
 PARTITION p202801 VALUES LESS THAN (202802) ENGINE = InnoDB,
 PARTITION p202802 VALUES LESS THAN (202803) ENGINE = InnoDB,
 PARTITION p202803 VALUES LESS THAN (202804) ENGINE = InnoDB,
 PARTITION p202804 VALUES LESS THAN (202805) ENGINE = InnoDB,
 PARTITION p202805 VALUES LESS THAN (202806) ENGINE = InnoDB,
 PARTITION p202806 VALUES LESS THAN (202807) ENGINE = InnoDB,
 PARTITION p202807 VALUES LESS THAN (202808) ENGINE = InnoDB,
 PARTITION p202808 VALUES LESS THAN (202809) ENGINE = InnoDB,
 PARTITION p202809 VALUES LESS THAN (202810) ENGINE = InnoDB,
 PARTITION p202810 VALUES LESS THAN (202811) ENGINE = InnoDB,
 PARTITION p202811 VALUES LESS THAN (202812) ENGINE = InnoDB,
 PARTITION p202812 VALUES LESS THAN (202901) ENGINE = InnoDB,
 PARTITION p202901 VALUES LESS THAN (202902) ENGINE = InnoDB,
 PARTITION p202902 VALUES LESS THAN (202903) ENGINE = InnoDB,
 PARTITION p202903 VALUES LESS THAN (202904) ENGINE = InnoDB,
 PARTITION p202904 VALUES LESS THAN (202905) ENGINE = InnoDB,
 PARTITION p202905 VALUES LESS THAN (202906) ENGINE = InnoDB,
 PARTITION p202906 VALUES LESS THAN (202907) ENGINE = InnoDB,
 PARTITION p202907 VALUES LESS THAN (202908) ENGINE = InnoDB,
 PARTITION p202908 VALUES LESS THAN (202909) ENGINE = InnoDB,
 PARTITION p202909 VALUES LESS THAN (202910) ENGINE = InnoDB,
 PARTITION p202910 VALUES LESS THAN (202911) ENGINE = InnoDB,
 PARTITION p202911 VALUES LESS THAN (202912) ENGINE = InnoDB,
 PARTITION p202912 VALUES LESS THAN (203001) ENGINE = InnoDB,
 PARTITION p203001 VALUES LESS THAN (203002) ENGINE = InnoDB,
 PARTITION p203002 VALUES LESS THAN (203003) ENGINE = InnoDB,
 PARTITION p203003 VALUES LESS THAN (203004) ENGINE = InnoDB,
 PARTITION p203004 VALUES LESS THAN (203005) ENGINE = InnoDB,
 PARTITION p203005 VALUES LESS THAN (203006) ENGINE = InnoDB,
 PARTITION p203006 VALUES LESS THAN (203007) ENGINE = InnoDB,
 PARTITION p203007 VALUES LESS THAN (203008) ENGINE = InnoDB,
 PARTITION p203008 VALUES LESS THAN (203009) ENGINE = InnoDB,
 PARTITION p203009 VALUES LESS THAN (203010) ENGINE = InnoDB,
 PARTITION p203010 VALUES LESS THAN (203011) ENGINE = InnoDB,
 PARTITION p203011 VALUES LESS THAN (203012) ENGINE = InnoDB,
 PARTITION p203012 VALUES LESS THAN (203101) ENGINE = InnoDB,
 PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_pds_sync_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_pds_sync_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `status` enum('running','success','partial','failed') CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL DEFAULT 'running',
  `trips_fetched` int unsigned NOT NULL DEFAULT '0',
  `trips_created` int unsigned NOT NULL DEFAULT '0',
  `trips_updated` int unsigned NOT NULL DEFAULT '0',
  `trips_unchanged` int unsigned NOT NULL DEFAULT '0',
  `trips_total_api` int unsigned DEFAULT NULL COMMENT 'Total trips reported by API',
  `pages_fetched` int unsigned NOT NULL DEFAULT '0',
  `error_message` text CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci,
  `duration_ms` int unsigned DEFAULT NULL,
  `started_at` datetime NOT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  KEY `idx_customer_created` (`customer_id`,`created_at`) USING BTREE,
  KEY `idx_status` (`status`,`created_at`) USING BTREE,
  KEY `idx_customer_status` (`customer_id`,`status`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_stays`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_stays` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `stay_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `stay_type` enum('hotel','apartment','resort','hostel','other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'hotel',
  `location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `giata_id` int unsigned DEFAULT NULL,
  `lat` decimal(10,8) DEFAULT NULL,
  `lng` decimal(11,8) DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_json` json DEFAULT NULL,
  `check_in` timestamp NOT NULL,
  `check_out` timestamp NOT NULL,
  `duration_nights` int unsigned DEFAULT NULL,
  `details_json` json DEFAULT NULL,
  `raw_meta` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `idx_trip_stay` (`trip_id`,`stay_id`) USING BTREE,
  KEY `idx_stay_dates` (`check_in`,`check_out`) USING BTREE,
  KEY `idx_stay_coords` (`lat`,`lng`) USING BTREE,
  KEY `idx_giata` (`giata_id`) USING BTREE,
  KEY `idx_location_time` (`lat`,`lng`,`check_in`,`check_out`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
/*!50100 PARTITION BY RANGE (((year(`created_at`) * 100) + month(`created_at`)))
(PARTITION p202501 VALUES LESS THAN (202502) ENGINE = InnoDB,
 PARTITION p202502 VALUES LESS THAN (202503) ENGINE = InnoDB,
 PARTITION p202503 VALUES LESS THAN (202504) ENGINE = InnoDB,
 PARTITION p202504 VALUES LESS THAN (202505) ENGINE = InnoDB,
 PARTITION p202505 VALUES LESS THAN (202506) ENGINE = InnoDB,
 PARTITION p202506 VALUES LESS THAN (202507) ENGINE = InnoDB,
 PARTITION p202507 VALUES LESS THAN (202508) ENGINE = InnoDB,
 PARTITION p202508 VALUES LESS THAN (202509) ENGINE = InnoDB,
 PARTITION p202509 VALUES LESS THAN (202510) ENGINE = InnoDB,
 PARTITION p202510 VALUES LESS THAN (202511) ENGINE = InnoDB,
 PARTITION p202511 VALUES LESS THAN (202512) ENGINE = InnoDB,
 PARTITION p202512 VALUES LESS THAN (202601) ENGINE = InnoDB,
 PARTITION p202601 VALUES LESS THAN (202602) ENGINE = InnoDB,
 PARTITION p202602 VALUES LESS THAN (202603) ENGINE = InnoDB,
 PARTITION p202603 VALUES LESS THAN (202604) ENGINE = InnoDB,
 PARTITION p202604 VALUES LESS THAN (202605) ENGINE = InnoDB,
 PARTITION p202605 VALUES LESS THAN (202606) ENGINE = InnoDB,
 PARTITION p202606 VALUES LESS THAN (202607) ENGINE = InnoDB,
 PARTITION p202607 VALUES LESS THAN (202608) ENGINE = InnoDB,
 PARTITION p202608 VALUES LESS THAN (202609) ENGINE = InnoDB,
 PARTITION p202609 VALUES LESS THAN (202610) ENGINE = InnoDB,
 PARTITION p202610 VALUES LESS THAN (202611) ENGINE = InnoDB,
 PARTITION p202611 VALUES LESS THAN (202612) ENGINE = InnoDB,
 PARTITION p202612 VALUES LESS THAN (202701) ENGINE = InnoDB,
 PARTITION p202701 VALUES LESS THAN (202702) ENGINE = InnoDB,
 PARTITION p202702 VALUES LESS THAN (202703) ENGINE = InnoDB,
 PARTITION p202703 VALUES LESS THAN (202704) ENGINE = InnoDB,
 PARTITION p202704 VALUES LESS THAN (202705) ENGINE = InnoDB,
 PARTITION p202705 VALUES LESS THAN (202706) ENGINE = InnoDB,
 PARTITION p202706 VALUES LESS THAN (202707) ENGINE = InnoDB,
 PARTITION p202707 VALUES LESS THAN (202708) ENGINE = InnoDB,
 PARTITION p202708 VALUES LESS THAN (202709) ENGINE = InnoDB,
 PARTITION p202709 VALUES LESS THAN (202710) ENGINE = InnoDB,
 PARTITION p202710 VALUES LESS THAN (202711) ENGINE = InnoDB,
 PARTITION p202711 VALUES LESS THAN (202712) ENGINE = InnoDB,
 PARTITION p202712 VALUES LESS THAN (202801) ENGINE = InnoDB,
 PARTITION p202801 VALUES LESS THAN (202802) ENGINE = InnoDB,
 PARTITION p202802 VALUES LESS THAN (202803) ENGINE = InnoDB,
 PARTITION p202803 VALUES LESS THAN (202804) ENGINE = InnoDB,
 PARTITION p202804 VALUES LESS THAN (202805) ENGINE = InnoDB,
 PARTITION p202805 VALUES LESS THAN (202806) ENGINE = InnoDB,
 PARTITION p202806 VALUES LESS THAN (202807) ENGINE = InnoDB,
 PARTITION p202807 VALUES LESS THAN (202808) ENGINE = InnoDB,
 PARTITION p202808 VALUES LESS THAN (202809) ENGINE = InnoDB,
 PARTITION p202809 VALUES LESS THAN (202810) ENGINE = InnoDB,
 PARTITION p202810 VALUES LESS THAN (202811) ENGINE = InnoDB,
 PARTITION p202811 VALUES LESS THAN (202812) ENGINE = InnoDB,
 PARTITION p202812 VALUES LESS THAN (202901) ENGINE = InnoDB,
 PARTITION p202901 VALUES LESS THAN (202902) ENGINE = InnoDB,
 PARTITION p202902 VALUES LESS THAN (202903) ENGINE = InnoDB,
 PARTITION p202903 VALUES LESS THAN (202904) ENGINE = InnoDB,
 PARTITION p202904 VALUES LESS THAN (202905) ENGINE = InnoDB,
 PARTITION p202905 VALUES LESS THAN (202906) ENGINE = InnoDB,
 PARTITION p202906 VALUES LESS THAN (202907) ENGINE = InnoDB,
 PARTITION p202907 VALUES LESS THAN (202908) ENGINE = InnoDB,
 PARTITION p202908 VALUES LESS THAN (202909) ENGINE = InnoDB,
 PARTITION p202909 VALUES LESS THAN (202910) ENGINE = InnoDB,
 PARTITION p202910 VALUES LESS THAN (202911) ENGINE = InnoDB,
 PARTITION p202911 VALUES LESS THAN (202912) ENGINE = InnoDB,
 PARTITION p202912 VALUES LESS THAN (203001) ENGINE = InnoDB,
 PARTITION p203001 VALUES LESS THAN (203002) ENGINE = InnoDB,
 PARTITION p203002 VALUES LESS THAN (203003) ENGINE = InnoDB,
 PARTITION p203003 VALUES LESS THAN (203004) ENGINE = InnoDB,
 PARTITION p203004 VALUES LESS THAN (203005) ENGINE = InnoDB,
 PARTITION p203005 VALUES LESS THAN (203006) ENGINE = InnoDB,
 PARTITION p203006 VALUES LESS THAN (203007) ENGINE = InnoDB,
 PARTITION p203007 VALUES LESS THAN (203008) ENGINE = InnoDB,
 PARTITION p203008 VALUES LESS THAN (203009) ENGINE = InnoDB,
 PARTITION p203009 VALUES LESS THAN (203010) ENGINE = InnoDB,
 PARTITION p203010 VALUES LESS THAN (203011) ENGINE = InnoDB,
 PARTITION p203011 VALUES LESS THAN (203012) ENGINE = InnoDB,
 PARTITION p203012 VALUES LESS THAN (203101) ENGINE = InnoDB,
 PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `from_segment_type` enum('flight','stay') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_segment_id` bigint unsigned NOT NULL,
  `to_segment_type` enum('flight','stay') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_segment_id` bigint unsigned NOT NULL,
  `transfer_location_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transfer_lat` decimal(10,8) DEFAULT NULL,
  `transfer_lng` decimal(11,8) DEFAULT NULL,
  `transfer_country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `connection_time_minutes` int unsigned DEFAULT NULL,
  `from_arrival_time` timestamp NULL DEFAULT NULL,
  `to_departure_time` timestamp NULL DEFAULT NULL,
  `transfer_type` enum('airport','city','same_location') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'airport',
  `is_tight_connection` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`),
  KEY `idx_trip_transfers` (`trip_id`) USING BTREE,
  KEY `idx_transfer_location` (`transfer_lat`,`transfer_lng`) USING BTREE,
  KEY `idx_transfer_time` (`from_arrival_time`,`to_departure_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
/*!50100 PARTITION BY RANGE (((year(`created_at`) * 100) + month(`created_at`)))
(PARTITION p202501 VALUES LESS THAN (202502) ENGINE = InnoDB,
 PARTITION p202502 VALUES LESS THAN (202503) ENGINE = InnoDB,
 PARTITION p202503 VALUES LESS THAN (202504) ENGINE = InnoDB,
 PARTITION p202504 VALUES LESS THAN (202505) ENGINE = InnoDB,
 PARTITION p202505 VALUES LESS THAN (202506) ENGINE = InnoDB,
 PARTITION p202506 VALUES LESS THAN (202507) ENGINE = InnoDB,
 PARTITION p202507 VALUES LESS THAN (202508) ENGINE = InnoDB,
 PARTITION p202508 VALUES LESS THAN (202509) ENGINE = InnoDB,
 PARTITION p202509 VALUES LESS THAN (202510) ENGINE = InnoDB,
 PARTITION p202510 VALUES LESS THAN (202511) ENGINE = InnoDB,
 PARTITION p202511 VALUES LESS THAN (202512) ENGINE = InnoDB,
 PARTITION p202512 VALUES LESS THAN (202601) ENGINE = InnoDB,
 PARTITION p202601 VALUES LESS THAN (202602) ENGINE = InnoDB,
 PARTITION p202602 VALUES LESS THAN (202603) ENGINE = InnoDB,
 PARTITION p202603 VALUES LESS THAN (202604) ENGINE = InnoDB,
 PARTITION p202604 VALUES LESS THAN (202605) ENGINE = InnoDB,
 PARTITION p202605 VALUES LESS THAN (202606) ENGINE = InnoDB,
 PARTITION p202606 VALUES LESS THAN (202607) ENGINE = InnoDB,
 PARTITION p202607 VALUES LESS THAN (202608) ENGINE = InnoDB,
 PARTITION p202608 VALUES LESS THAN (202609) ENGINE = InnoDB,
 PARTITION p202609 VALUES LESS THAN (202610) ENGINE = InnoDB,
 PARTITION p202610 VALUES LESS THAN (202611) ENGINE = InnoDB,
 PARTITION p202611 VALUES LESS THAN (202612) ENGINE = InnoDB,
 PARTITION p202612 VALUES LESS THAN (202701) ENGINE = InnoDB,
 PARTITION p202701 VALUES LESS THAN (202702) ENGINE = InnoDB,
 PARTITION p202702 VALUES LESS THAN (202703) ENGINE = InnoDB,
 PARTITION p202703 VALUES LESS THAN (202704) ENGINE = InnoDB,
 PARTITION p202704 VALUES LESS THAN (202705) ENGINE = InnoDB,
 PARTITION p202705 VALUES LESS THAN (202706) ENGINE = InnoDB,
 PARTITION p202706 VALUES LESS THAN (202707) ENGINE = InnoDB,
 PARTITION p202707 VALUES LESS THAN (202708) ENGINE = InnoDB,
 PARTITION p202708 VALUES LESS THAN (202709) ENGINE = InnoDB,
 PARTITION p202709 VALUES LESS THAN (202710) ENGINE = InnoDB,
 PARTITION p202710 VALUES LESS THAN (202711) ENGINE = InnoDB,
 PARTITION p202711 VALUES LESS THAN (202712) ENGINE = InnoDB,
 PARTITION p202712 VALUES LESS THAN (202801) ENGINE = InnoDB,
 PARTITION p202801 VALUES LESS THAN (202802) ENGINE = InnoDB,
 PARTITION p202802 VALUES LESS THAN (202803) ENGINE = InnoDB,
 PARTITION p202803 VALUES LESS THAN (202804) ENGINE = InnoDB,
 PARTITION p202804 VALUES LESS THAN (202805) ENGINE = InnoDB,
 PARTITION p202805 VALUES LESS THAN (202806) ENGINE = InnoDB,
 PARTITION p202806 VALUES LESS THAN (202807) ENGINE = InnoDB,
 PARTITION p202807 VALUES LESS THAN (202808) ENGINE = InnoDB,
 PARTITION p202808 VALUES LESS THAN (202809) ENGINE = InnoDB,
 PARTITION p202809 VALUES LESS THAN (202810) ENGINE = InnoDB,
 PARTITION p202810 VALUES LESS THAN (202811) ENGINE = InnoDB,
 PARTITION p202811 VALUES LESS THAN (202812) ENGINE = InnoDB,
 PARTITION p202812 VALUES LESS THAN (202901) ENGINE = InnoDB,
 PARTITION p202901 VALUES LESS THAN (202902) ENGINE = InnoDB,
 PARTITION p202902 VALUES LESS THAN (202903) ENGINE = InnoDB,
 PARTITION p202903 VALUES LESS THAN (202904) ENGINE = InnoDB,
 PARTITION p202904 VALUES LESS THAN (202905) ENGINE = InnoDB,
 PARTITION p202905 VALUES LESS THAN (202906) ENGINE = InnoDB,
 PARTITION p202906 VALUES LESS THAN (202907) ENGINE = InnoDB,
 PARTITION p202907 VALUES LESS THAN (202908) ENGINE = InnoDB,
 PARTITION p202908 VALUES LESS THAN (202909) ENGINE = InnoDB,
 PARTITION p202909 VALUES LESS THAN (202910) ENGINE = InnoDB,
 PARTITION p202910 VALUES LESS THAN (202911) ENGINE = InnoDB,
 PARTITION p202911 VALUES LESS THAN (202912) ENGINE = InnoDB,
 PARTITION p202912 VALUES LESS THAN (203001) ENGINE = InnoDB,
 PARTITION p203001 VALUES LESS THAN (203002) ENGINE = InnoDB,
 PARTITION p203002 VALUES LESS THAN (203003) ENGINE = InnoDB,
 PARTITION p203003 VALUES LESS THAN (203004) ENGINE = InnoDB,
 PARTITION p203004 VALUES LESS THAN (203005) ENGINE = InnoDB,
 PARTITION p203005 VALUES LESS THAN (203006) ENGINE = InnoDB,
 PARTITION p203006 VALUES LESS THAN (203007) ENGINE = InnoDB,
 PARTITION p203007 VALUES LESS THAN (203008) ENGINE = InnoDB,
 PARTITION p203008 VALUES LESS THAN (203009) ENGINE = InnoDB,
 PARTITION p203009 VALUES LESS THAN (203010) ENGINE = InnoDB,
 PARTITION p203010 VALUES LESS THAN (203011) ENGINE = InnoDB,
 PARTITION p203011 VALUES LESS THAN (203012) ENGINE = InnoDB,
 PARTITION p203012 VALUES LESS THAN (203101) ENGINE = InnoDB,
 PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_travellers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_travellers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `external_traveller_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `traveller_type` enum('adult','child','infant') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'adult',
  `first_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salutation` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `nationality` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`),
  UNIQUE KEY `uk_trip_traveller` (`trip_id`,`external_traveller_id`,`created_at`),
  KEY `idx_nationality` (`nationality`) USING BTREE,
  KEY `idx_passport_country` (`passport_country`) USING BTREE,
  KEY `idx_trip_nationality` (`trip_id`,`nationality`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
/*!50100 PARTITION BY RANGE (((year(`created_at`) * 100) + month(`created_at`)))
(PARTITION p202501 VALUES LESS THAN (202502) ENGINE = InnoDB,
 PARTITION p202502 VALUES LESS THAN (202503) ENGINE = InnoDB,
 PARTITION p202503 VALUES LESS THAN (202504) ENGINE = InnoDB,
 PARTITION p202504 VALUES LESS THAN (202505) ENGINE = InnoDB,
 PARTITION p202505 VALUES LESS THAN (202506) ENGINE = InnoDB,
 PARTITION p202506 VALUES LESS THAN (202507) ENGINE = InnoDB,
 PARTITION p202507 VALUES LESS THAN (202508) ENGINE = InnoDB,
 PARTITION p202508 VALUES LESS THAN (202509) ENGINE = InnoDB,
 PARTITION p202509 VALUES LESS THAN (202510) ENGINE = InnoDB,
 PARTITION p202510 VALUES LESS THAN (202511) ENGINE = InnoDB,
 PARTITION p202511 VALUES LESS THAN (202512) ENGINE = InnoDB,
 PARTITION p202512 VALUES LESS THAN (202601) ENGINE = InnoDB,
 PARTITION p202601 VALUES LESS THAN (202602) ENGINE = InnoDB,
 PARTITION p202602 VALUES LESS THAN (202603) ENGINE = InnoDB,
 PARTITION p202603 VALUES LESS THAN (202604) ENGINE = InnoDB,
 PARTITION p202604 VALUES LESS THAN (202605) ENGINE = InnoDB,
 PARTITION p202605 VALUES LESS THAN (202606) ENGINE = InnoDB,
 PARTITION p202606 VALUES LESS THAN (202607) ENGINE = InnoDB,
 PARTITION p202607 VALUES LESS THAN (202608) ENGINE = InnoDB,
 PARTITION p202608 VALUES LESS THAN (202609) ENGINE = InnoDB,
 PARTITION p202609 VALUES LESS THAN (202610) ENGINE = InnoDB,
 PARTITION p202610 VALUES LESS THAN (202611) ENGINE = InnoDB,
 PARTITION p202611 VALUES LESS THAN (202612) ENGINE = InnoDB,
 PARTITION p202612 VALUES LESS THAN (202701) ENGINE = InnoDB,
 PARTITION p202701 VALUES LESS THAN (202702) ENGINE = InnoDB,
 PARTITION p202702 VALUES LESS THAN (202703) ENGINE = InnoDB,
 PARTITION p202703 VALUES LESS THAN (202704) ENGINE = InnoDB,
 PARTITION p202704 VALUES LESS THAN (202705) ENGINE = InnoDB,
 PARTITION p202705 VALUES LESS THAN (202706) ENGINE = InnoDB,
 PARTITION p202706 VALUES LESS THAN (202707) ENGINE = InnoDB,
 PARTITION p202707 VALUES LESS THAN (202708) ENGINE = InnoDB,
 PARTITION p202708 VALUES LESS THAN (202709) ENGINE = InnoDB,
 PARTITION p202709 VALUES LESS THAN (202710) ENGINE = InnoDB,
 PARTITION p202710 VALUES LESS THAN (202711) ENGINE = InnoDB,
 PARTITION p202711 VALUES LESS THAN (202712) ENGINE = InnoDB,
 PARTITION p202712 VALUES LESS THAN (202801) ENGINE = InnoDB,
 PARTITION p202801 VALUES LESS THAN (202802) ENGINE = InnoDB,
 PARTITION p202802 VALUES LESS THAN (202803) ENGINE = InnoDB,
 PARTITION p202803 VALUES LESS THAN (202804) ENGINE = InnoDB,
 PARTITION p202804 VALUES LESS THAN (202805) ENGINE = InnoDB,
 PARTITION p202805 VALUES LESS THAN (202806) ENGINE = InnoDB,
 PARTITION p202806 VALUES LESS THAN (202807) ENGINE = InnoDB,
 PARTITION p202807 VALUES LESS THAN (202808) ENGINE = InnoDB,
 PARTITION p202808 VALUES LESS THAN (202809) ENGINE = InnoDB,
 PARTITION p202809 VALUES LESS THAN (202810) ENGINE = InnoDB,
 PARTITION p202810 VALUES LESS THAN (202811) ENGINE = InnoDB,
 PARTITION p202811 VALUES LESS THAN (202812) ENGINE = InnoDB,
 PARTITION p202812 VALUES LESS THAN (202901) ENGINE = InnoDB,
 PARTITION p202901 VALUES LESS THAN (202902) ENGINE = InnoDB,
 PARTITION p202902 VALUES LESS THAN (202903) ENGINE = InnoDB,
 PARTITION p202903 VALUES LESS THAN (202904) ENGINE = InnoDB,
 PARTITION p202904 VALUES LESS THAN (202905) ENGINE = InnoDB,
 PARTITION p202905 VALUES LESS THAN (202906) ENGINE = InnoDB,
 PARTITION p202906 VALUES LESS THAN (202907) ENGINE = InnoDB,
 PARTITION p202907 VALUES LESS THAN (202908) ENGINE = InnoDB,
 PARTITION p202908 VALUES LESS THAN (202909) ENGINE = InnoDB,
 PARTITION p202909 VALUES LESS THAN (202910) ENGINE = InnoDB,
 PARTITION p202910 VALUES LESS THAN (202911) ENGINE = InnoDB,
 PARTITION p202911 VALUES LESS THAN (202912) ENGINE = InnoDB,
 PARTITION p202912 VALUES LESS THAN (203001) ENGINE = InnoDB,
 PARTITION p203001 VALUES LESS THAN (203002) ENGINE = InnoDB,
 PARTITION p203002 VALUES LESS THAN (203003) ENGINE = InnoDB,
 PARTITION p203003 VALUES LESS THAN (203004) ENGINE = InnoDB,
 PARTITION p203004 VALUES LESS THAN (203005) ENGINE = InnoDB,
 PARTITION p203005 VALUES LESS THAN (203006) ENGINE = InnoDB,
 PARTITION p203006 VALUES LESS THAN (203007) ENGINE = InnoDB,
 PARTITION p203007 VALUES LESS THAN (203008) ENGINE = InnoDB,
 PARTITION p203008 VALUES LESS THAN (203009) ENGINE = InnoDB,
 PARTITION p203009 VALUES LESS THAN (203010) ENGINE = InnoDB,
 PARTITION p203010 VALUES LESS THAN (203011) ENGINE = InnoDB,
 PARTITION p203011 VALUES LESS THAN (203012) ENGINE = InnoDB,
 PARTITION p203012 VALUES LESS THAN (203101) ENGINE = InnoDB,
 PARTITION p_future VALUES LESS THAN MAXVALUE ENGINE = InnoDB) */;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_trip_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_trip_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `trip_id` bigint unsigned NOT NULL,
  `location_type` enum('departure','arrival','stay','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_type` enum('flight_segment','stay','transfer') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_id` bigint unsigned NOT NULL,
  `lat` decimal(10,8) NOT NULL,
  `lng` decimal(11,8) NOT NULL,
  `location_code` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  KEY `idx_trip_locations` (`trip_id`) USING BTREE,
  KEY `idx_time_range` (`start_time`,`end_time`) USING BTREE,
  KEY `idx_country` (`country_code`) USING BTREE,
  KEY `idx_coords_time` (`lat`,`lng`,`start_time`,`end_time`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `td_trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `td_trips` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned DEFAULT NULL,
  `provider_id` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `external_trip_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `provider_sent_at` timestamp NOT NULL,
  `booking_reference` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trip_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cruise_compass_cruise_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_cruise` tinyint(1) NOT NULL DEFAULT '0',
  `schema_version` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.1',
  `computed_start_at` timestamp NULL DEFAULT NULL,
  `computed_end_at` timestamp NULL DEFAULT NULL,
  `countries_visited` json DEFAULT NULL,
  `nationalities` json DEFAULT NULL,
  `travel_modes` json DEFAULT NULL,
  `with_minors` tinyint(1) NOT NULL DEFAULT '0',
  `tour_operators` json DEFAULT NULL,
  `individual_contents` json DEFAULT NULL,
  `note` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cover_media` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visits` int unsigned NOT NULL DEFAULT '0',
  `last_visited_at` timestamp NULL DEFAULT NULL,
  `last_important_change_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','completed','cancelled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `is_archived` tinyint(1) NOT NULL DEFAULT '0',
  `is_test_data` tinyint(1) NOT NULL DEFAULT '0',
  `pds_share_url` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pds_tid` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pds_share_created_at` timestamp NULL DEFAULT NULL,
  `raw_payload` json DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`,`created_at`) USING BTREE,
  UNIQUE KEY `uk_provider_trip` (`provider_id`,`external_trip_id`,`created_at`) USING BTREE,
  KEY `idx_computed_end_at` (`computed_end_at`) USING BTREE,
  KEY `idx_status` (`status`) USING BTREE,
  KEY `idx_is_archived` (`is_archived`) USING BTREE,
  KEY `idx_computed_dates` (`computed_start_at`,`computed_end_at`) USING BTREE,
  KEY `idx_provider_sent_at` (`provider_sent_at`) USING BTREE,
  KEY `idx_archival` (`computed_end_at`,`is_archived`) USING BTREE,
  KEY `idx_customer_status` (`customer_id`,`status`) USING BTREE,
  KEY `idx_customer_dates` (`customer_id`,`computed_start_at`,`computed_end_at`) USING BTREE,
  KEY `td_trips_is_test_data_index` (`is_test_data`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_data_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_data_changes` (
  `country_id` bigint unsigned NOT NULL,
  `nationality_id` int unsigned NOT NULL,
  `cruise_code` varchar(12) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `important_change_at` timestamp NULL DEFAULT NULL,
  `last_change_at` timestamp NULL DEFAULT NULL,
  UNIQUE KEY `tourism_cruise_data_changes_country_id_nationality_id_cruise_unq` (`country_id`,`nationality_id`,`cruise_code`) USING BTREE,
  KEY `tourism_cruise_data_changes_nationality_id_foreign` (`nationality_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_data_changes_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tourism_cruise_data_changes_nationality_id_foreign` FOREIGN KEY (`nationality_id`) REFERENCES `nationalities` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_line_cruise_compass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_line_cruise_compass` (
  `line_id` smallint unsigned NOT NULL,
  `cruise_compass_id` bigint unsigned NOT NULL,
  UNIQUE KEY `tourism_cruise_line_cruise_compass_line_id_cruise_compass_id_unq` (`line_id`,`cruise_compass_id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_line_cruise_compass_cruise_compass_id_unique` (`cruise_compass_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_line_cruise_compass_line_id_foreign` FOREIGN KEY (`line_id`) REFERENCES `tourism_cruise_lines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_line_tour_operators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_line_tour_operators` (
  `line_id` smallint unsigned NOT NULL,
  `operator_id` smallint unsigned NOT NULL,
  UNIQUE KEY `tourism_cruise_line_tour_operators_line_id_operator_id_unique` (`line_id`,`operator_id`) USING BTREE,
  KEY `tourism_cruise_line_tour_operators_operator_id_foreign` (`operator_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_line_tour_operators_line_id_foreign` FOREIGN KEY (`line_id`) REFERENCES `tourism_cruise_lines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tourism_cruise_line_tour_operators_operator_id_foreign` FOREIGN KEY (`operator_id`) REFERENCES `tourism_tour_operators` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_lines` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_customization_account_id` int unsigned DEFAULT NULL,
  `data_customization_restricted_access` tinyint(1) DEFAULT NULL,
  `use_default_content` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_lines_name_unique` (`name`) USING BTREE,
  UNIQUE KEY `tourism_cruise_lines_code_unique` (`code`) USING BTREE,
  KEY `tourism_cruise_lines_data_customization_account_id_foreign` (`data_customization_account_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_lines_data_customization_account_id_foreign` FOREIGN KEY (`data_customization_account_id`) REFERENCES `client_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_ports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_ports` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `country_id` bigint unsigned DEFAULT NULL,
  `code` char(3) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `geocode_lat` decimal(10,7) DEFAULT NULL,
  `geocode_lng` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_ports_code_unique` (`code`) USING BTREE,
  KEY `tourism_cruise_ports_country_id_foreign` (`country_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_ports_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_route_courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_route_courses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cruise_compass_route_id` bigint unsigned NOT NULL,
  `day` smallint unsigned NOT NULL,
  `port_id` smallint unsigned NOT NULL,
  `arrive_at` time DEFAULT NULL,
  `depart_at` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `tourism_cruise_route_courses_cruise_compass_route_id_foreign` (`cruise_compass_route_id`) USING BTREE,
  KEY `tourism_cruise_route_courses_port_id_foreign` (`port_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_route_courses_cruise_compass_route_id_foreign` FOREIGN KEY (`cruise_compass_route_id`) REFERENCES `tourism_cruise_route_cruise_compass` (`cruise_compass_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `tourism_cruise_route_courses_port_id_foreign` FOREIGN KEY (`port_id`) REFERENCES `tourism_cruise_ports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_route_cruise_compass`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_route_cruise_compass` (
  `route_id` bigint unsigned NOT NULL,
  `cruise_compass_id` bigint unsigned NOT NULL,
  UNIQUE KEY `tourism_cruise_route_cruise_compass_route_id_cruise_compass_id_u` (`route_id`,`cruise_compass_id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_route_cruise_compass_cruise_compass_id_unique` (`cruise_compass_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_route_cruise_compass_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `tourism_cruise_routes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_route_cruises`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_route_cruises` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cruise_compass_id` varchar(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cruise_compass_route_id` bigint unsigned NOT NULL,
  `start_date` date NOT NULL,
  `duration_in_days` smallint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_route_cruises_cruise_compass_id_unique` (`cruise_compass_id`) USING BTREE,
  KEY `tourism_cruise_route_cruises_cruise_compass_route_id_foreign` (`cruise_compass_route_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_route_cruises_cruise_compass_route_id_foreign` FOREIGN KEY (`cruise_compass_route_id`) REFERENCES `tourism_cruise_route_cruise_compass` (`cruise_compass_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `ship_id` int unsigned NOT NULL,
  `name` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_routes_ship_id_name_unique` (`ship_id`,`name`) USING BTREE,
  CONSTRAINT `tourism_cruise_routes_ship_id_foreign` FOREIGN KEY (`ship_id`) REFERENCES `tourism_cruise_ships` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tourism_cruise_ships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tourism_cruise_ships` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `line_id` smallint unsigned NOT NULL,
  `cruise_compass_id` bigint unsigned NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `tourism_cruise_ships_line_id_name_unique` (`line_id`,`name`) USING BTREE,
  UNIQUE KEY `tourism_cruise_ships_cruise_compass_id_unique` (`cruise_compass_id`) USING BTREE,
  CONSTRAINT `tourism_cruise_ships_line_id_foreign` FOREIGN KEY (`line_id`) REFERENCES `tourism_cruise_lines` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `travel_alert_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `travel_alert_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'business',
  `business_type` json DEFAULT NULL,
  `company` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `street` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `postal_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `country` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `existing_billing` enum('ja','nein') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `trial_expires_at` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_language_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_language_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `language_code` varchar(5) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `country_code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notification_preferences` json DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `two_factor_secret` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `two_factor_recovery_codes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `two_factor_confirmed_at` timestamp NULL DEFAULT NULL,
  `is_admin` tinyint(1) DEFAULT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_adr_head_cooperation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_adr_head_cooperation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_de` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content_en` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_user` int DEFAULT NULL,
  `created_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_user` int DEFAULT NULL,
  `updated_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `webold_adr_head_branch_active_index` (`active`) USING BTREE,
  KEY `webold_adr_head_cooperation_code_index` (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_client_account_legacy_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_client_account_legacy_options` (
  `account_id` int unsigned NOT NULL,
  `account_type` int DEFAULT '1' COMMENT '1=Testaccount VA, 2=Testaccount RB, 3=Veranstalter, 4=Reisebüro, 5=Reisebarater	',
  `client_type` int DEFAULT '1',
  `office_count` int DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `revised` tinyint NOT NULL DEFAULT '0',
  `live_from` date DEFAULT NULL,
  `end_of_use` date DEFAULT NULL,
  `zoho_crm_id` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_visa_service` int DEFAULT NULL,
  `show_visa_service_link` varchar(250) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `show_visa_service_text` varchar(1000) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visa_places` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DE',
  `show_travel_warning` tinyint DEFAULT '1',
  `travel_warning_country` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'de',
  `response_api_version` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '0.6',
  `response_api_status` int unsigned DEFAULT '1',
  `agency_address_position` int DEFAULT NULL,
  `use_report` int DEFAULT '2',
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `myjack_agency_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `providers` json NOT NULL,
  `tech_access` int DEFAULT '0',
  `cooperations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`account_id`) USING BTREE,
  CONSTRAINT `client_account_legacy_options_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `client_accounts` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_client_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_client_accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `passolution_company_id` tinyint unsigned DEFAULT NULL,
  `account_id` int unsigned DEFAULT NULL,
  `organization_id` bigint unsigned DEFAULT NULL,
  `language_id` tinyint unsigned DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address_line_1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_id` int unsigned DEFAULT NULL,
  `phone` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `webold_client_accounts_account_id_foreign` (`account_id`) USING BTREE,
  KEY `webold_client_accounts_language_id_foreign` (`language_id`) USING BTREE,
  KEY `webold_client_accounts_country_id_foreign` (`country_id`) USING BTREE,
  KEY `webold_client_accounts_passolution_company_id_foreign` (`passolution_company_id`) USING BTREE,
  KEY `webold_client_accounts_organization_id_foreign` (`organization_id`) USING BTREE,
  CONSTRAINT `webold_client_accounts_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `webold_client_accounts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `webold_client_accounts_country_id_foreign` FOREIGN KEY (`country_id`) REFERENCES `webold_countries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `webold_client_accounts_language_id_foreign` FOREIGN KEY (`language_id`) REFERENCES `webold_pds_languages` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `webold_client_accounts_organization_id_foreign` FOREIGN KEY (`organization_id`) REFERENCES `webold_adr_head_cooperation` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `webold_client_accounts_passolution_company_id_foreign` FOREIGN KEY (`passolution_company_id`) REFERENCES `webold_passolution_companies` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_client_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_client_users` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `office_id` int unsigned DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `name` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_active_at` timestamp NULL DEFAULT NULL,
  `access_expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `client_users_account_id_foreign` (`account_id`) USING BTREE,
  KEY `client_users_office_id_foreign` (`office_id`) USING BTREE,
  CONSTRAINT `client_users_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `client_accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `client_users_office_id_foreign` FOREIGN KEY (`office_id`) REFERENCES `client_offices` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_countries` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_local` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_en` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_fr` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_it` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_nl` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_pl` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_es` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_pt` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_be` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name_ru` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flag` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_image_versions` json DEFAULT NULL,
  `continent` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capital` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `population` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `area` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coastline` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `governmentform` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currencycode` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dialingprefix` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `birthrate` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deathrate` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lifeexpectancy` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transitvisa` int DEFAULT NULL,
  `transitvisatext` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `url1` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prio` int DEFAULT '99',
  `google_static_map_code` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL,
  `created_user` int DEFAULT NULL,
  `created_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_user` int DEFAULT NULL,
  `updated_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `active` tinyint DEFAULT '0',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `countrycode` (`code`) USING BTREE,
  KEY `prio` (`prio`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_passolution_companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_passolution_companies` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vat_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zoho_organization_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_pds_languages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_pds_languages` (
  `id` tinyint unsigned NOT NULL AUTO_INCREMENT,
  `position` tinyint unsigned DEFAULT NULL,
  `code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `webold_pds_languages_code_unique` (`code`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webold_usersweb`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webold_usersweb` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignto` int DEFAULT '0',
  `idpaymentuser` int DEFAULT '0',
  `idcontact` int DEFAULT NULL,
  `revised` tinyint(1) DEFAULT '0',
  `idsec` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `username` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `level` int unsigned DEFAULT NULL,
  `role` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'Member',
  `password` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `passwordold` varchar(36) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `hasnewpassword` tinyint(1) DEFAULT '0',
  `activationpassword` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `securequestion` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `secureanswer` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `email` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `realname` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `forename` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `surname` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `address1` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `zip` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `city` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `phone` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `birthday` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `agency` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `providers` json NOT NULL,
  `accounttype` int DEFAULT '1' COMMENT '1=Testaccount VA, 2=Testaccount RB, 3=Veranstalter, 4=Reisebüro, 5=Reisebarater',
  `feeinstall` decimal(15,2) DEFAULT NULL,
  `feemonth` decimal(15,2) DEFAULT NULL,
  `feeinterval` int DEFAULT NULL COMMENT '1=monatlich, 2=jährlich',
  `accessmaxyear` int DEFAULT NULL COMMENT 'maximale jährige Zugriffszahlen',
  `access2018` int DEFAULT NULL,
  `access2019` int DEFAULT NULL,
  `access2020` int DEFAULT NULL,
  `access2021` int DEFAULT NULL,
  `access2022` int DEFAULT NULL,
  `testvalidity` date DEFAULT NULL,
  `testrenewals` int DEFAULT '0',
  `livefrom` date DEFAULT NULL,
  `endofuse` date DEFAULT NULL,
  `canceltype` int DEFAULT '0',
  `canceldate` date DEFAULT NULL,
  `linkmaxopen` int DEFAULT '1',
  `linkmaxtodeparture` int DEFAULT '1',
  `linkmaxfromcreate` int DEFAULT '1',
  `clienttype` int DEFAULT '1',
  `cooperation` json DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `techaccess` int DEFAULT '0',
  `poa` int DEFAULT NULL,
  `addcontent` tinyint(1) DEFAULT '0',
  `mailable` tinyint(1) DEFAULT '0',
  `usereport` int DEFAULT '2',
  `visaplaces` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'DE',
  `showvisaservice` int DEFAULT NULL,
  `showvisaservicelink` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `showvisaservicetext` varchar(1000) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `showtravelwarning` tinyint(1) DEFAULT '1',
  `travelwarningcountry` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT 'de',
  `responseapiversion` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT '0.6',
  `responseapistatus` int unsigned DEFAULT '1',
  `info1` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `info2` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `info3` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `info4` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `info5` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `info6` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `remember_token` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `favdestination` json DEFAULT NULL,
  `favnationality` json DEFAULT NULL,
  `favlanguage` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `sitelanguage` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `logo` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `officeNum` int DEFAULT NULL,
  `street` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `land` varchar(10) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `handy` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `fax` varchar(30) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `website` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `nameAccount` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `bank` varchar(100) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `theywere` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `bic` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `ust` varchar(50) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `comment` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `zohoAccountID` varbinary(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_user` int DEFAULT NULL,
  `created_ip` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `updated_user` int DEFAULT NULL,
  `updated_ip` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL,
  `active` varchar(1) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT '0',
  `providers1` json DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE KEY `usersweb_username_unique` (`username`) USING BTREE,
  KEY `agency` (`agency`,`linkmaxopen`,`linkmaxtodeparture`,`linkmaxfromcreate`,`clienttype`,`techaccess`) USING BTREE,
  KEY `accounttype` (`accounttype`,`testvalidity`) USING BTREE,
  KEY `username` (`username`) USING BTREE,
  KEY `usersweb_mailable_index` (`mailable`) USING BTREE,
  KEY `usersweb_addcontent_index` (`addcontent`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `websites`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `websites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort_order` int NOT NULL DEFAULT '0',
  `customer_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  KEY `websites_customer_id_foreign` (`customer_id`) USING BTREE,
  KEY `websites_branch_id_foreign` (`branch_id`) USING BTREE,
  CONSTRAINT `websites_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `websites_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_08_07_000001_create_continents_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_08_07_000002_create_countries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_08_07_000003_create_regions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_08_07_000004_create_cities_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_08_07_000005_create_airports_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_08_07_000006_create_prompt_templates_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_08_07_000007_create_ai_examples_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_08_07_000008_create_disaster_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_08_07_000009_create_ai_processing_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_08_07_000010_create_ai_quotas_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_08_07_000011_create_ai_cost_alerts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_08_07_000012_create_ai_job_progress_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_08_07_000013_create_ai_usage_tracking_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_08_07_000014_create_ai_system_health_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_08_07_000015_create_user_language_preferences_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_08_07_101200_check_and_create_missing_risk_radar_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_08_07_105351_add_is_capital_to_cities_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_08_07_121311_add_gdacs_fields_to_disaster_events_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2025_08_07_121559_add_soft_deletes_to_disaster_events_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2024_08_12_091500_create_custom_events_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2024_08_12_102000_add_role_and_status_to_users_table',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2025_08_12_084249_add_marker_icon_to_custom_events_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2025_08_15_084518_add_is_admin_to_users_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2025_08_17_095933_add_missing_fields_to_custom_events_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2025_08_17_105512_add_severity_to_custom_events_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2025_08_17_130659_create_custom_events_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2025_08_17_130702_create_disaster_events_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2025_08_17_130705_create_continents_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2025_08_17_130709_create_countries_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2025_08_18_000001_alter_custom_events_coordinates_precision',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2025_08_18_000002_add_country_to_custom_events',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2025_08_19_000003_create_social_links_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2025_08_17_130712_create_regions_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2025_08_17_130715_create_cities_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2025_08_17_130718_create_airports_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2025_08_17_130720_create_disaster_events_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2025_08_21_000001_add_soft_deletes_to_airports_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2025_08_21_000002_add_soft_deletes_to_all_tables',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2025_08_22_153125_create_infosystem_entries_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2025_08_27_072849_add_missing_countries_to_countries_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2025_08_27_074534_add_schengen_field_to_countries_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2025_08_28_120000_add_is_gdacs_to_disaster_events_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2025_08_17_130844_add_admin_fields_to_users_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2025_09_09_131527_create_event_types_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2025_09_09_132058_seed_event_types_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2025_09_09_132126_add_event_type_id_to_custom_events_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2025_09_09_132300_migrate_existing_custom_events_to_event_types',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2025_09_09_134522_add_event_type_id_to_disaster_events_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2025_09_09_134713_migrate_existing_disaster_events_to_event_types',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2025_09_10_143247_create_event_categories_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2025_09_10_143319_add_event_category_id_to_custom_events_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2025_09_14_050549_add_data_source_to_custom_events_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2025_09_14_050608_add_published_tracking_to_infosystem_entries_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2025_09_17_043345_add_archived_to_custom_events_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2025_09_18_062259_create_custom_event_event_type_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2025_09_18_121718_add_info_to_priority_enum_in_custom_events_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2025_09_18_122346_create_event_clicks_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2025_09_26_080135_create_country_custom_event_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2025_09_26_080329_migrate_existing_country_data_to_pivot_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2025_09_26_082546_add_coordinates_to_country_custom_event_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2025_09_30_135050_add_sort_order_to_continents_table',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2025_10_01_123142_set_all_country_coordinates_to_null',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2025_10_01_123251_set_all_event_coordinates_to_null',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2025_10_01_124841_create_ai_prompts_table',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2025_10_08_111530_add_is_regional_capital_to_cities_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2025_10_11_080049_create_event_display_settings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2025_10_11_080400_add_selected_display_event_type_id_to_custom_events_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2025_10_15_120026_add_categories_to_infosystem_entries_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2025_10_20_085707_make_country_code_nullable_in_infosystem_entries_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2025_10_22_184630_fix_infosystem_entries_id_auto_increment',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2025_10_22_185323_fix_auto_increment_for_all_main_tables',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2025_10_22_192719_fix_auto_increment_for_system_tables',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2025_10_21_214330_create_entry_conditions_logs_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2025_10_22_152207_add_coordinates_to_countries',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2025_10_23_122332_create_booking_locations_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2025_10_24_074916_fix_all_tables_auto_increment_and_defaults',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2025_10_24_080016_create_city_custom_event_pivot_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2025_10_24_080016_create_custom_event_region_pivot_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2025_10_24_081623_add_security_timeslot_url_to_airports_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2025_10_24_083239_add_website_to_airports_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2025_10_27_101528_update_cities_table_schema',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_10_24_103950_create_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2025_10_24_110238_add_two_factor_columns_to_users_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2025_10_24_141535_add_customer_type_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2025_10_24_143651_add_business_type_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_10_24_144212_change_business_type_to_json_in_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_10_24_150704_add_company_and_billing_address_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_10_24_153253_add_additional_fields_to_company_addresses',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_10_24_155832_add_house_number_fields_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_10_24_165416_add_passolution_oauth_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_10_24_174834_add_passolution_subscription_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_10_24_191148_add_hide_profile_completion_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_10_24_193505_add_directory_listing_active_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_10_25_092001_add_branch_management_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_10_26_065315_create_branches_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_10_26_081251_add_app_code_to_branches_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_10_26_084435_create_notifications_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_10_26_111340_fix_jobs_table_auto_increment',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_10_26_113148_add_scheduled_deletion_to_branches',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_10_26_114534_create_branch_exports_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_10_26_115540_add_status_to_branch_exports_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_10_29_214330_increase_airports_coordinates_precision',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_11_09_080535_add_region_and_city_to_country_custom_event_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_11_10_070618_add_additional_info_to_airports_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_11_10_072251_create_airlines_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_11_10_083820_add_branch_id_to_booking_locations_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_11_10_112728_extend_nationality_column_in_entry_conditions_logs',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_11_10_113632_add_missing_columns_to_airports_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_11_10_115549_add_terminal_to_airline_airport_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_11_10_120000_ensure_booking_locations_table_exists',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_11_11_063922_add_sso_fields_to_customers_table',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_11_21_112201_create_sso_logs_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_11_22_075721_add_version_fields_to_sso_logs_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_11_24_100204_add_pds_customer_number_to_customers_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_11_24_165503_add_passolution_roles_to_customers_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_11_25_080142_add_pds_api_token_fields_to_customers_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_12_07_000001_create_td_trips_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_12_07_000002_create_td_air_legs_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_12_07_000003_create_td_flight_segments_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_12_07_000004_create_td_stays_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_12_07_000005_create_td_transfers_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_12_07_000006_create_td_trip_locations_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_12_07_000007_create_td_pds_share_links_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_12_07_000008_create_td_import_logs_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_12_07_000009_create_td_travellers_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_12_08_091542_create_share_links_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_12_10_060000_add_airport_fields_to_airport_codes_1_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_12_10_060001_create_airline_airport_code_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_12_11_100000_migrate_airports_data_to_airport_codes',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_12_22_100000_create_plugin_clients_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_12_22_100001_create_plugin_keys_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_12_22_100002_create_plugin_domains_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_12_22_100003_create_plugin_usage_events_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_12_21_150000_create_info_sources_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_12_21_160000_create_info_source_items_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_12_22_184802_make_customer_id_nullable_in_plugin_clients',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_12_22_191300_add_is_active_to_plugin_domains',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_12_22_191659_add_address_fields_to_plugin_clients',50);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_12_23_080347_create_plugin_email_verifications_table',51);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_12_23_081709_change_form_data_column_to_text',52);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_01_16_000000_add_allow_app_access_to_plugin_clients',53);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_01_23_100001_create_folder_folders_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_01_23_100002_create_folder_customers_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_01_23_100003_create_folder_participants_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_01_23_100004_create_folder_itineraries_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_01_23_100005_create_folder_flight_services_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_01_23_100006_create_folder_flight_segments_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_01_23_100007_create_folder_hotel_services_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_01_23_100008_create_folder_ship_services_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_01_23_100009_create_folder_car_rental_services_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_01_23_100010_create_folder_timeline_locations_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_01_23_100011_create_folder_itinerary_participant_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_01_23_100012_create_folder_import_logs_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_01_24_084500_add_custom_fields_to_folder_folders_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_01_24_095245_add_airport_and_country_ids_to_folder_flight_segments_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_01_25_092716_add_auto_refresh_settings_to_customers_table',54);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_01_26_090409_change_avatar_column_to_text_in_customers_table',55);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_01_27_100000_add_gtm_api_fields_to_customers_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_01_27_100001_create_gtm_api_request_logs_table',56);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_01_27_130731_add_uuid_to_event_tables',57);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_01_30_235339_create_customer_feature_overrides_table',58);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_01_31_083617_add_visumpoint_to_customer_feature_overrides',59);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_02_04_095248_modify_folder_participants_salutation_default',60);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_02_11_100000_create_api_clients_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_02_11_100001_add_api_client_fields_to_custom_events_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_02_11_100002_create_api_client_request_logs_table',61);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_02_14_100001_create_labels_table',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_02_14_100002_create_label_pivot_tables',62);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_02_19_100000_create_event_groups_table',63);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_02_19_100001_add_can_create_events_to_api_clients_table',64);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_02_23_100001_create_pds_trip_label_table',65);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_02_28_120000_create_travel_alert_orders_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_02_28_130000_add_trial_expires_at_to_travel_alert_orders_table',66);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_03_03_100000_add_risk_profile_to_countries_table',67);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_03_04_100000_add_notifications_enabled_to_customers_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_03_04_100001_create_notification_templates_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_03_04_100002_create_notification_rules_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_03_04_100003_create_notification_rule_recipients_table',68);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_03_04_100004_remove_logic_operator_from_notification_rules_table',69);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_03_10_100000_create_notification_logs_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_03_10_100001_create_notification_unsubscribe_tokens_table',70);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_03_12_071639_populate_missing_country_coordinates',71);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_03_12_195244_add_customer_id_to_custom_events_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_03_12_204851_add_customer_events_enabled_to_customer_feature_overrides_table',72);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_03_15_090343_create_employees_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_03_15_091104_add_salutation_and_title_to_employees_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_03_15_091322_add_mobile_and_notes_to_employees_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_03_15_092538_create_departments_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_03_15_092612_add_department_id_to_employees_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_03_15_095246_create_phone_numbers_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_03_15_095247_create_email_addresses_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_03_15_100003_create_websites_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_03_15_100011_add_notes_to_phone_numbers_and_email_addresses',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_03_15_100634_add_department_id_to_phone_numbers_and_email_addresses',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_03_15_102442_add_sort_order_to_contact_tables',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_03_15_115046_create_org_nodes_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_03_15_120431_add_code_to_org_nodes_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_03_15_120733_add_relation_label_to_org_nodes_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_03_15_123644_create_branch_org_node_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_03_15_125516_add_branch_id_to_contact_tables',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_03_15_130405_add_customer_and_contract_number_to_branch_org_node',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_03_15_131332_add_dates_to_branch_org_node',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_03_15_134114_create_branch_contacts_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_03_15_140850_add_visibility_to_custom_events_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_03_15_140857_create_custom_event_org_node_table',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_03_15_142601_add_dates_to_custom_event_org_node',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2026_03_15_144856_add_visibility_dates_to_custom_events',73);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2026_03_15_212210_add_template_and_test_to_notification_logs',74);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2026_03_16_205217_update_system_notification_template_content',75);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2026_03_17_194512_add_source_to_custom_events_table',76);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2026_03_20_100001_partition_td_import_logs_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2026_03_20_100002_partition_td_trip_locations_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2026_03_20_100003_partition_td_flight_segments_table',77);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2026_03_20_200001_add_customer_id_to_td_trips_table',78);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2026_03_20_200002_partition_td_trips_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2026_03_20_200003_create_td_pds_sync_log_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2026_03_20_300001_add_pds_sync_enabled_to_customers_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2026_03_20_300002_add_travel_links_enabled_to_customers_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2026_03_21_100001_add_pds_detail_fields_to_td_trips_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2026_03_21_100002_add_is_cruise_to_td_trips_table',79);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2026_03_23_204422_add_source_frontend_fields_to_custom_events_table',80);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2026_03_25_181605_add_source_to_notification_rules_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2026_03_25_182611_create_notification_queue_logs_table',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2026_03_25_185041_add_source_to_notification_templates_and_create_travel_alert_template',81);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2026_03_26_100000_partition_notification_queue_logs_table',82);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2026_03_26_100001_partition_notification_logs_table',83);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2026_03_27_100000_update_travel_alert_system_template_content',84);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2026_03_30_100000_add_is_test_data_to_td_trips_table',85);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2026_03_30_120000_add_affected_trips_count_to_notification_logs',86);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2026_03_31_100000_add_uuid_to_plugin_domains_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2026_04_02_100001_partition_td_air_legs_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2026_04_02_100002_partition_td_stays_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2026_04_02_100003_partition_td_transfers_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2026_04_02_100004_partition_td_travellers_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2026_04_02_100005_partition_td_pds_share_links_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2026_04_03_073552_add_has_seen_travel_alert_tour_to_customers_table',87);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2026_04_03_081802_rename_platform_tour_and_add_travel_alert_tour_to_customers_table',88);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2026_04_03_083104_add_has_seen_gtm_tour_to_customers_table',89);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2026_04_03_083654_add_all_tour_flags_to_customers_table',90);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2026_04_03_090237_add_has_seen_settings_tour_to_customers_table',91);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2026_04_05_105437_add_login_code_to_customers_table',92);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2026_04_06_120000_add_customer_type_fields_to_travel_alert_orders_table',93);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2026_04_06_140000_create_employee_groups_table',94);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2026_04_06_160000_seed_default_employee_groups',95);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2026_04_06_170000_add_is_system_to_employee_groups_table',96);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2026_04_06_180000_add_active_dates_to_employees_table',97);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2026_04_06_190000_add_legacy_password_to_customers_table',98);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2026_04_08_220251_add_legacy_client_account_fields_to_customers_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2026_04_09_080648_add_legacy_ids_to_employees_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2026_04_09_080652_add_legacy_ids_to_employees_table',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2026_04_09_112847_drop_unique_email_from_customers_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2026_04_12_081246_add_app_code_to_customers_table',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2026_04_12_090000_create_customer_access_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (257,'2026_04_12_100000_add_assign_to_to_customers_table',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2026_04_12_213343_add_legacy_usersweb_fields_to_employees_table',105);
