/*
 Navicat MySQL Dump SQL

 Source Server         : prod
 Source Server Type    : MySQL
 Source Server Version : 50744 (5.7.44-google-log)
 Source Host           : 35.242.234.188:3306
 Source Schema         : dataservice

 Target Server Type    : MySQL
 Target Server Version : 50744 (5.7.44-google-log)
 File Encoding         : 65001

 Date: 08/04/2026 21:48:32
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for webold_adr_head_cooperation
-- ----------------------------
DROP TABLE IF EXISTS `webold_adr_head_cooperation`;
CREATE TABLE `webold_adr_head_cooperation`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `content_de` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `content_en` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `created_user` int(11) NULL DEFAULT NULL,
  `created_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `updated_user` int(11) NULL DEFAULT NULL,
  `updated_ip` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `active` tinyint(1) NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `webold_adr_head_branch_active_index`(`active`) USING BTREE,
  INDEX `webold_adr_head_cooperation_code_index`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 89 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of webold_adr_head_cooperation
-- ----------------------------
INSERT INTO `webold_adr_head_cooperation` VALUES (1, 'rtk', 'RTK', 'RTK', NULL, 2, '127.0.0.1', NULL, NULL, 1, '2019-12-31 06:36:45', '2019-12-31 06:36:45');
INSERT INTO `webold_adr_head_cooperation` VALUES (2, 'qta', 'QTA', 'QTA', NULL, 2, '127.0.0.1', NULL, NULL, 1, '2019-12-31 06:37:25', '2019-12-31 06:37:25');
INSERT INTO `webold_adr_head_cooperation` VALUES (3, 'rtg', 'RT Gruppe', 'RT Gruppe', NULL, 2, '127.0.0.1', 15, '169.254.1.1', 1, '2019-12-31 06:37:38', '2023-08-01 10:06:43');
INSERT INTO `webold_adr_head_cooperation` VALUES (4, 'tss', 'TSS', 'TSS', NULL, 2, '127.0.0.1', NULL, NULL, 1, '2019-12-31 06:43:40', '2019-12-31 06:43:40');
INSERT INTO `webold_adr_head_cooperation` VALUES (5, 'dtps', 'DTPS', 'DTPS', NULL, 2, '127.0.0.1', NULL, NULL, 1, '2019-12-31 06:46:48', '2019-12-31 06:46:48');
INSERT INTO `webold_adr_head_cooperation` VALUES (6, 'lcc', 'LCC', 'LCC', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-01-06 08:48:09', '2020-01-06 08:48:09');
INSERT INTO `webold_adr_head_cooperation` VALUES (7, 'alpha-p', 'Alpha Partner', 'Alpha Partner', NULL, 4, '169.254.1.1', 4, '169.254.1.1', 1, '2020-01-06 08:49:43', '2020-01-20 08:44:56');
INSERT INTO `webold_adr_head_cooperation` VALUES (8, 'alpha-t', 'AlphaTeam', 'Alpha Team', NULL, 4, '169.254.1.1', 4, '169.254.1.1', 1, '2020-01-06 08:50:04', '2020-01-20 08:45:13');
INSERT INTO `webold_adr_head_cooperation` VALUES (9, 'STA', 'STA Travel', 'STA Travel', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-01-09 11:40:31', '2020-01-09 11:40:31');
INSERT INTO `webold_adr_head_cooperation` VALUES (10, 'SOLA', 'Solamento', 'Solamento', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-01-10 10:23:11', '2020-01-10 10:23:11');
INSERT INTO `webold_adr_head_cooperation` VALUES (11, 'ANVR', 'ANVR', 'ANVR', NULL, 4, '169.254.1.1', 11, '169.254.1.1', 0, '2020-04-28 09:45:30', '2023-10-19 11:04:12');
INSERT INTO `webold_adr_head_cooperation` VALUES (12, 'lcc-int', 'LCC International', 'LCC International', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2020-05-12 08:23:38', '2020-05-12 08:23:38');
INSERT INTO `webold_adr_head_cooperation` VALUES (13, 'TUIAustria', 'TUIAustria', 'TUIAustria', NULL, 4, '169.254.1.1', 15, '169.254.1.1', 0, '2020-05-26 11:04:15', '2023-05-09 19:28:15');
INSERT INTO `webold_adr_head_cooperation` VALUES (14, 'tts', 'TTS', 'TTS', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2020-05-29 12:29:27', '2020-05-29 12:29:27');
INSERT INTO `webold_adr_head_cooperation` VALUES (15, 'BEST', 'BEST', 'BEST', NULL, 9, '169.254.1.1', 11, '169.254.1.1', 1, '2020-05-29 12:29:49', '2023-10-19 11:05:09');
INSERT INTO `webold_adr_head_cooperation` VALUES (16, 'RTK Int.', 'RTK International', 'RTK International', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-06-24 07:37:45', '2020-06-24 07:37:45');
INSERT INTO `webold_adr_head_cooperation` VALUES (17, 'TUI DE', 'TUI Eigenbüros', 'TUI Eigenbüros', NULL, 4, '169.254.1.1', 11, '169.254.1.1', 1, '2020-06-24 07:38:13', '2023-10-19 06:03:16');
INSERT INTO `webold_adr_head_cooperation` VALUES (18, 'AER', 'AER', 'AER', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-06-26 07:22:54', '2020-06-26 07:22:54');
INSERT INTO `webold_adr_head_cooperation` VALUES (19, 'TLT', 'TLT', 'TLT', NULL, 9, '169.254.1.1', 11, '169.254.1.1', 1, '2020-07-02 12:06:30', '2023-10-18 10:00:57');
INSERT INTO `webold_adr_head_cooperation` VALUES (20, 'SETO', 'SETO', 'SETO', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-07-10 12:28:46', '2020-07-10 12:28:46');
INSERT INTO `webold_adr_head_cooperation` VALUES (21, 'WLTT', 'WLTT', 'WLTT', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2020-07-15 13:45:44', '2020-07-15 13:45:44');
INSERT INTO `webold_adr_head_cooperation` VALUES (22, 'DERTOUR SK', 'DERTOUR SK', 'DERTOUR SK', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2020-07-20 15:13:25', '2020-07-20 15:13:25');
INSERT INTO `webold_adr_head_cooperation` VALUES (23, 'ltur', 'ltur', 'ltur', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-07-28 12:38:18', '2020-07-28 12:38:18');
INSERT INTO `webold_adr_head_cooperation` VALUES (24, 'STAR', 'STAR', 'STAR', NULL, 9, '169.254.1.1', 11, '169.254.1.1', 0, '2020-07-31 07:41:54', '2023-10-17 10:32:15');
INSERT INTO `webold_adr_head_cooperation` VALUES (25, 'SRV', 'SRV', 'SRV', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2020-08-11 11:50:26', '2020-08-11 11:50:26');
INSERT INTO `webold_adr_head_cooperation` VALUES (26, 'Schmetterling', 'Schmetterling', 'Schmetterling', NULL, 13, '169.254.1.1', NULL, NULL, 1, '2020-08-14 08:21:35', '2020-08-14 08:21:35');
INSERT INTO `webold_adr_head_cooperation` VALUES (27, 'MUGL', 'Mein Urlaubsglück', 'Mein Urlaubsglück', NULL, 13, '169.254.1.1', NULL, NULL, 1, '2020-09-29 14:53:22', '2020-09-29 14:53:22');
INSERT INTO `webold_adr_head_cooperation` VALUES (28, 'ETOA', 'ETOA', 'ETOA', NULL, 4, '169.254.1.1', NULL, NULL, 1, '2020-10-21 12:10:26', '2020-10-21 12:10:26');
INSERT INTO `webold_adr_head_cooperation` VALUES (29, 'APAVT', 'APAVT', 'APAVT', NULL, 4, '169.254.1.1', 11, '169.254.1.1', 0, '2020-10-26 09:29:08', '2023-10-19 11:04:48');
INSERT INTO `webold_adr_head_cooperation` VALUES (30, 'FAIR', 'FAIR', 'FAIR', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2020-12-10 08:48:33', '2020-12-10 08:48:33');
INSERT INTO `webold_adr_head_cooperation` VALUES (35, 'TUI-Eigen', 'TUI Eigen', 'TUI Eigen', NULL, 4, '169.254.1.1', 4, '169.254.1.1', 0, '2021-02-11 11:05:53', '2021-02-11 11:09:23');
INSERT INTO `webold_adr_head_cooperation` VALUES (39, 'none', 'Keine', 'none', NULL, 2, '169.254.1.1', NULL, NULL, 0, '2021-04-19 09:29:37', '2021-04-19 09:29:37');
INSERT INTO `webold_adr_head_cooperation` VALUES (40, 'TRVL', 'Travelista', 'Travelista', NULL, 13, '169.254.1.1', NULL, NULL, 1, '2021-04-21 15:20:01', '2021-04-21 15:20:01');
INSERT INTO `webold_adr_head_cooperation` VALUES (41, 'TUI Franchise', 'TUI Franchise', 'TUI Franchise', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-04 18:21:28', '2023-10-19 09:06:35');
INSERT INTO `webold_adr_head_cooperation` VALUES (42, 'OEVB', 'ÖVB', 'ÖVB', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-05-05 07:14:56', '2021-05-05 07:14:56');
INSERT INTO `webold_adr_head_cooperation` VALUES (43, 'Kuoni Restplatzbörse', 'Kuoni Restplatzbörse', 'Kuoni Restplatzbörse', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-05 07:23:01', '2023-10-19 09:41:49');
INSERT INTO `webold_adr_head_cooperation` VALUES (44, 'TUI AT', 'TUI Österreich', 'TUI Österreich', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-05-05 07:30:35', '2021-05-05 07:30:35');
INSERT INTO `webold_adr_head_cooperation` VALUES (45, 'TLT-TA', 'TLT - Take Off', 'TLT - Take Off', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-05 07:55:46', '2023-10-18 10:33:59');
INSERT INTO `webold_adr_head_cooperation` VALUES (46, 'TLT-HP', 'TLT holiday profis', 'TLT holiday profis', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-05-05 07:56:18', '2021-05-05 07:56:18');
INSERT INTO `webold_adr_head_cooperation` VALUES (47, 'TLT-FE', 'TLT Feria', 'TLT Feria', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-05-05 07:56:39', '2021-05-05 07:56:39');
INSERT INTO `webold_adr_head_cooperation` VALUES (48, 'TLT-AG', 'TLT Agenturpartner', 'TLT Agenturpartner', NULL, 2, '169.254.1.1', 2, '169.254.1.1', 1, '2021-05-05 07:57:03', '2021-05-05 08:30:55');
INSERT INTO `webold_adr_head_cooperation` VALUES (49, 'BlumHolidayTours', 'Blum Holiday Tours', 'Blum Holiday Tours', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-05 08:35:24', '2023-10-17 13:59:02');
INSERT INTO `webold_adr_head_cooperation` VALUES (50, 'HBB-HE', 'Hebbel', 'Hebbel', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-05-05 08:35:50', '2021-05-05 08:35:50');
INSERT INTO `webold_adr_head_cooperation` VALUES (51, 'Alltours eigen RB', 'Alltours eigen RB', 'Alltours eigen RB', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-05 09:22:05', '2023-10-19 11:03:25');
INSERT INTO `webold_adr_head_cooperation` VALUES (52, 'Alltours Franchise', 'Alltours Franchise', 'Alltours Franchise', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-05 09:22:26', '2023-10-19 11:03:55');
INSERT INTO `webold_adr_head_cooperation` VALUES (53, 'VA', 'Veranstalter', 'Veranstalter', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 0, '2021-05-06 09:58:45', '2023-10-18 14:44:14');
INSERT INTO `webold_adr_head_cooperation` VALUES (54, 'DER', 'DER', 'DER', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-05-25 20:30:16', '2021-05-25 20:30:16');
INSERT INTO `webold_adr_head_cooperation` VALUES (55, 'star.ch', 'STAR CH', 'STAR CH', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2021-05-27 12:34:11', '2023-10-17 10:31:06');
INSERT INTO `webold_adr_head_cooperation` VALUES (56, 'htp', 'Hotelplan', 'Hotelplan', NULL, 2, '169.254.1.1', NULL, NULL, 1, '2021-06-14 13:02:59', '2021-06-14 13:02:59');
INSERT INTO `webold_adr_head_cooperation` VALUES (57, 'Amd', 'Amondo', 'Amondo', NULL, 9, '169.254.1.1', NULL, NULL, 1, '2021-06-28 13:40:24', '2021-06-28 13:40:24');
INSERT INTO `webold_adr_head_cooperation` VALUES (58, 'keine Kooperation', 'keine Kooperation', 'keine Kooperation', NULL, 2, '169.254.1.1', 11, '169.254.1.1', 1, '2022-02-22 11:10:44', '2023-10-19 11:07:19');
INSERT INTO `webold_adr_head_cooperation` VALUES (59, 'AER Mobile', 'AER Mobile', 'AER Mobile', NULL, 13, '169.254.1.1', 11, '169.254.1.1', 1, '2022-05-04 09:39:33', '2023-10-19 11:02:20');
INSERT INTO `webold_adr_head_cooperation` VALUES (60, 'ADAC', 'ADAC', 'ADAC', NULL, 14, '169.254.1.1', NULL, NULL, 1, '2022-11-03 12:16:12', '2022-11-03 12:16:12');
INSERT INTO `webold_adr_head_cooperation` VALUES (61, 'DERPART', 'DERPART', 'DERPART', NULL, 14, '169.254.1.1', NULL, NULL, 1, '2023-04-05 13:42:06', '2023-04-05 13:42:06');
INSERT INTO `webold_adr_head_cooperation` VALUES (62, 'hl', 'Holiday Land', 'Holiday Land', NULL, 15, '169.254.1.1', NULL, NULL, 1, '2023-04-28 12:26:11', '2023-04-28 12:26:11');
INSERT INTO `webold_adr_head_cooperation` VALUES (63, 'GALERIA', 'Galeria Reisen', 'Galeria Reisen', NULL, 15, '169.254.1.1', NULL, NULL, 1, '2023-05-09 15:04:37', '2023-05-09 15:04:37');
INSERT INTO `webold_adr_head_cooperation` VALUES (64, 'slr', 'Schauinsland Reisen', 'Schauinsland Reisen', NULL, 15, '169.254.1.1', NULL, NULL, 1, '2023-05-09 15:13:52', '2023-05-09 15:13:52');
INSERT INTO `webold_adr_head_cooperation` VALUES (65, 'TUI CH', 'TUI Suisse', 'TUI Suisse', NULL, 15, '169.254.1.1', NULL, NULL, 1, '2023-05-09 18:27:22', '2023-05-09 18:27:22');
INSERT INTO `webold_adr_head_cooperation` VALUES (66, 'Gruber', 'GRUBER-reisen', 'GRUBER-reisen', NULL, 11, '169.254.1.1', 11, '169.254.1.1', 1, '2023-06-13 08:27:44', '2023-12-14 13:30:34');
INSERT INTO `webold_adr_head_cooperation` VALUES (67, 'TUIStar', 'TUI Travelstar', 'TUI Travelstar', NULL, 15, '169.254.1.1', NULL, NULL, 1, '2023-08-01 07:58:50', '2023-08-01 07:58:50');
INSERT INTO `webold_adr_head_cooperation` VALUES (68, 'reiseland', 'Reiseland', 'Reiseland', NULL, 11, '169.254.1.1', 17, '169.254.1.1', 1, '2023-08-01 10:27:51', '2024-06-05 12:11:03');
INSERT INTO `webold_adr_head_cooperation` VALUES (69, 'columbus_reisen', 'Columbus Reisen', 'Columbus Reisen', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-17 13:05:37', '2023-10-17 13:05:37');
INSERT INTO `webold_adr_head_cooperation` VALUES (70, 'ÖBB', 'ÖBB', 'ÖBB', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-17 13:13:04', '2023-10-17 13:13:04');
INSERT INTO `webold_adr_head_cooperation` VALUES (71, 'Dr.Tigges', 'Dr.Tigges', 'Dr.Tigges', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-17 13:14:38', '2023-10-17 13:14:38');
INSERT INTO `webold_adr_head_cooperation` VALUES (72, 'Alltours', 'Alltours', 'Alltours', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-17 13:27:27', '2023-10-17 13:27:27');
INSERT INTO `webold_adr_head_cooperation` VALUES (73, 'Rita AG', 'Rita AG', 'Rita AG', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-18 06:32:09', '2023-10-18 06:32:09');
INSERT INTO `webold_adr_head_cooperation` VALUES (74, 'TUI Reisecenter', 'TUI Reisecenter', 'TUI Reisecenter', NULL, 11, '169.254.1.1', 11, '169.254.1.1', 0, '2023-10-19 09:29:45', '2023-10-19 09:34:23');
INSERT INTO `webold_adr_head_cooperation` VALUES (75, 'Kuoni DER CH', 'Kuoni DER CH', 'Kuoni DER CH', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-19 09:40:46', '2023-10-19 09:40:46');
INSERT INTO `webold_adr_head_cooperation` VALUES (76, 'Protours', 'Protours', 'Protours', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2023-10-19 11:05:55', '2023-10-19 11:05:55');
INSERT INTO `webold_adr_head_cooperation` VALUES (77, 'my-travel-expert', 'My Travel Expert', 'My Travel Expert', NULL, 15, '169.254.1.1', NULL, NULL, 1, '2023-10-31 16:21:53', '2023-10-31 16:21:53');
INSERT INTO `webold_adr_head_cooperation` VALUES (78, 'Alpha', 'Alpha', 'Alpha', NULL, 17, '169.254.1.1', NULL, NULL, 1, '2023-12-14 12:35:20', '2023-12-14 12:35:21');
INSERT INTO `webold_adr_head_cooperation` VALUES (79, 'schauinsland reisen Partner', 'schauinsland reisen Partner', 'schauinsland reisen Partner', NULL, 14, '169.254.1.1', NULL, NULL, 1, '2024-01-11 16:15:09', '2024-01-11 16:15:09');
INSERT INTO `webold_adr_head_cooperation` VALUES (80, 'schauinsland reisen Team', 'schauinsland reisen Team', 'schauinsland reisen Team', NULL, 14, '169.254.1.1', NULL, NULL, 1, '2024-01-11 16:16:07', '2024-01-11 16:16:07');
INSERT INTO `webold_adr_head_cooperation` VALUES (81, 'PRO Holidays', 'PRO Holidays', 'PRO Holidays', NULL, 14, '169.254.1.1', NULL, NULL, 1, '2024-02-20 13:51:55', '2024-02-20 13:51:55');
INSERT INTO `webold_adr_head_cooperation` VALUES (82, 'Explorer Travel', 'Explorer Travel', 'Explorer Travel', NULL, 14, '169.254.1.1', NULL, NULL, 1, '2024-02-21 17:27:45', '2024-02-21 17:27:45');
INSERT INTO `webold_adr_head_cooperation` VALUES (83, 'emile weber', 'emile weber', 'emile weber', NULL, 17, '169.254.1.1', NULL, NULL, 1, '2024-06-05 12:27:26', '2024-06-05 12:27:26');
INSERT INTO `webold_adr_head_cooperation` VALUES (84, 'TGL', 'Travel Group Luxembourg s.à.r.l.', 'Travel Group Luxembourg s.à.r.l.', NULL, 17, '169.254.1.1', NULL, NULL, 1, '2024-06-05 12:27:53', '2024-06-05 12:27:53');
INSERT INTO `webold_adr_head_cooperation` VALUES (85, 'ptt_nl', 'Personal Touch Travel NL', 'Personal Touch Travel NL', 'Niederländische Reiseberater Kette', 15, '169.254.1.1', 15, '169.254.1.1', 1, '2024-08-16 11:31:02', '2024-08-16 11:34:24');
INSERT INTO `webold_adr_head_cooperation` VALUES (86, 'TPS', 'TPS (Travel Professionals Switzerland)', 'TPS', 'TPS', 14, '169.254.1.1', 14, '169.254.1.1', 1, '2024-09-10 12:04:43', '2024-09-10 12:06:28');
INSERT INTO `webold_adr_head_cooperation` VALUES (87, 'Geo Travel Network', 'Geo Travel Network', 'Geo Travel Network', 'Geo Travel Network', 14, '169.254.1.1', NULL, NULL, 1, '2025-01-08 13:48:54', '2025-01-08 13:48:54');
INSERT INTO `webold_adr_head_cooperation` VALUES (88, 'TLTRB', 'TLT Reiseberatung', 'TLT Reiseberatung', NULL, 11, '169.254.1.1', NULL, NULL, 1, '2025-11-26 07:34:20', '2025-11-26 07:34:20');

SET FOREIGN_KEY_CHECKS = 1;
