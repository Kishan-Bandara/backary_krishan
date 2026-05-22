-- =============================================
-- DST Code Table
-- Created: 2026-05-18
-- =============================================

CREATE TABLE IF NOT EXISTS `DST_Code` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `Code` varchar(50) NOT NULL,
  `CodeDescription` varchar(255) NOT NULL,
  `GSTPercentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_code` (`Code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
