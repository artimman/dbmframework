--
-- Database Schema
-- Author: Design by Malina
-- Baza danych: dbm_cms
-- Info: For international use, default COLLATE=utf8mb4_unicode_ci, for example for Polish set COLLATE=utf8mb4_polish_ci which will enable sorting taking into account Polish diacritics.
--

-- --------------------------------------------------------

-- Table `dbm_user`
CREATE TABLE `dbm_user` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(64) NOT NULL,
  `email` VARCHAR(180) NOT NULL,
  `password` VARCHAR(128) NOT NULL,
  `roles` VARCHAR(20) NOT NULL DEFAULT 'USER',
  `token` VARCHAR(50) DEFAULT NULL,
  `verified` TINYINT(1) NOT NULL DEFAULT '0',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_login` (`login`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `dbm_user_details`
CREATE TABLE `dbm_user_details` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `fullname` VARCHAR(100) DEFAULT NULL,
  `profession` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `website` VARCHAR(150) DEFAULT NULL,
  `avatar` VARCHAR(50) DEFAULT NULL,
  `biography` TEXT,
  `business` VARCHAR(100) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_details_user_id` (`user_id`),
  CONSTRAINT `fk_user_details_user` FOREIGN KEY (`user_id`) REFERENCES `dbm_user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `dbm_remember_me`
CREATE TABLE `dbm_remember_me` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` VARCHAR(50) NOT NULL,
  `validator` VARCHAR(100) NOT NULL,
  `expiry` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_remember_me_user_id` (`user_id`),
  CONSTRAINT `fk_remember_me_user` FOREIGN KEY (`user_id`) REFERENCES `dbm_user`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table `dbm_reset_password`
CREATE TABLE `dbm_reset_password` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(180) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `expires` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Data Insertions
INSERT INTO `dbm_user` (`id`, `login`, `email`, `password`, `roles`, `token`, `verified`, `created_at`) VALUES
(1, 'Admin', 'admin@mail.com', '$2y$10$30xGoLBAGXNwt5mSb2CU0uJ/hsrHlHVHCsWo3TF2wXGWVuqw3PR/m', 'ADMIN', '50a9ead33e94f4b56cd9475483ce9105e8a5bf6f', 1, '2021-01-01 12:00:00'),
(2, 'John', 'john@mail.com', '$2y$10$YENFQ6axxkDxPyvhEYLBX.ld46LupE6sO7to91glQL0ZxU9XyA.yK', 'USER', '545932a772cae4455b882a4cb6551c7ba0c7b6a3', 1, '2021-01-02 12:00:00'),
(3, 'Lucy', 'lucy@mail.com', '$2y$10$YENFQ6axxkDxPyvhEYLBX.ld46LupE6sO7to91glQL0ZxU9XyA.yK', 'USER', 'ac8fe01e8de57f4c0d1d54cc7a2bcd871d3f7dee', 0, '2021-01-03 12:00:00');

INSERT INTO `dbm_user_details` (`id`, `user_id`, `fullname`, `profession`, `phone`, `website`, `avatar`, `biography`, `business`, `address`, `created_at`) VALUES
(1, 1, 'Arthur Malinowski', 'Full Stack Developer', '+48 600 000 000', 'www.dbm.org.pl', 'avatar-1.jpg', 'This is the story of a designer and entrepreneur.', 'Design by Malina Ltd.', 'Raspberry Land, PL', NOW()),
(2, 2, 'John Doe', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW()),
(3, 3, 'Lucy Johansson', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NOW());
