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

 Date: 08/04/2026 21:48:12
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for webold_pds_languages
-- ----------------------------
DROP TABLE IF EXISTS `webold_pds_languages`;
CREATE TABLE `webold_pds_languages`  (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `position` tinyint(3) UNSIGNED NULL DEFAULT NULL,
  `code` char(2) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `webold_pds_languages_code_unique`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 22 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of webold_pds_languages
-- ----------------------------
INSERT INTO `webold_pds_languages` VALUES (1, 1, 'de', 'German', NULL);
INSERT INTO `webold_pds_languages` VALUES (2, 2, 'en', 'Englisch', NULL);
INSERT INTO `webold_pds_languages` VALUES (3, 3, 'fr', 'French', NULL);
INSERT INTO `webold_pds_languages` VALUES (4, 4, 'it', 'Italian', NULL);
INSERT INTO `webold_pds_languages` VALUES (5, 5, 'nl', 'Dutch', NULL);
INSERT INTO `webold_pds_languages` VALUES (6, 6, 'pl', 'Polish', NULL);
INSERT INTO `webold_pds_languages` VALUES (7, 7, 'es', 'Spanish', NULL);
INSERT INTO `webold_pds_languages` VALUES (8, 8, 'pt', 'Portuguese', NULL);
INSERT INTO `webold_pds_languages` VALUES (9, 9, 'ru', 'Russian', NULL);
INSERT INTO `webold_pds_languages` VALUES (10, 10, 'bg', 'Bulgarian', NULL);
INSERT INTO `webold_pds_languages` VALUES (11, 11, 'cs', 'Czech', NULL);
INSERT INTO `webold_pds_languages` VALUES (12, 12, 'da', 'Danish', NULL);
INSERT INTO `webold_pds_languages` VALUES (13, 13, 'el', 'Greek', NULL);
INSERT INTO `webold_pds_languages` VALUES (14, 14, 'fi', 'Finnish', NULL);
INSERT INTO `webold_pds_languages` VALUES (15, 15, 'hu', 'Hungarian', NULL);
INSERT INTO `webold_pds_languages` VALUES (16, 16, 'nb', 'Norwegian', NULL);
INSERT INTO `webold_pds_languages` VALUES (17, 17, 'ro', 'Romanian', NULL);
INSERT INTO `webold_pds_languages` VALUES (18, 18, 'sk', 'Slovak', NULL);
INSERT INTO `webold_pds_languages` VALUES (19, 19, 'sl', 'Slovenian', NULL);
INSERT INTO `webold_pds_languages` VALUES (20, 20, 'sv', 'Swedish', NULL);
INSERT INTO `webold_pds_languages` VALUES (21, 21, 'tr', 'Turkish', NULL);

SET FOREIGN_KEY_CHECKS = 1;
