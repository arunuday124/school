-- Run this in phpMyAdmin (select database `all_school`) or via mysql CLI
-- Adjust column types if you prefer to store images as BLOBs instead of file paths

CREATE TABLE IF NOT EXISTS `schools` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(255) NOT NULL,
  `ph_no` VARCHAR(50) DEFAULT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `description` VARCHAR(1024) DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
