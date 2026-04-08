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

 Date: 08/04/2026 21:48:51
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for webold_passolution_companies
-- ----------------------------
DROP TABLE IF EXISTS `webold_passolution_companies`;
CREATE TABLE `webold_passolution_companies`  (
  `id` tinyint(3) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `vat_identifier` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `zoho_organization_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of webold_passolution_companies
-- ----------------------------
INSERT INTO `webold_passolution_companies` VALUES (1, 'Passolution GmbH', 'DE315711773', NULL);
INSERT INTO `webold_passolution_companies` VALUES (2, 'Passolution International GmbH', 'DE325366480', NULL);

SET FOREIGN_KEY_CHECKS = 1;
