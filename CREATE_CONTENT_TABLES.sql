-- Optional: Create member_promo and member_news tables
-- Run this if you want to enable promo/news feature

USE core;

-- Table for member promos
CREATE TABLE IF NOT EXISTS `member_promo` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `deskripsi` text,
  `gambar` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active_urutan` (`is_active`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for member news
CREATE TABLE IF NOT EXISTS `member_news` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `konten` text,
  `gambar` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_active_urutan` (`is_active`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data for promo
INSERT INTO `member_promo` (`judul`, `deskripsi`, `urutan`, `is_active`) VALUES
('Promo Special Member', 'Dapatkan diskon 20% untuk semua menu', 1, 1),
('Buy 1 Get 1 Free', 'Promo minuman favorit setiap hari Jumat', 2, 1);

-- Sample data for news
INSERT INTO `member_news` (`judul`, `konten`, `urutan`, `is_active`) VALUES
('Selamat Datang Member Baru!', 'Terima kasih telah bergabung dengan Namua Coffee Family', 1, 1),
('Update Aplikasi Member', 'Aplikasi member telah diperbarui dengan fitur-fitur baru', 2, 1);

