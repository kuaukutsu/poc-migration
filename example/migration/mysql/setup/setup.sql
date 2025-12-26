CREATE TABLE IF NOT EXISTS `%SYSTEM_TABLE%` (
    `name` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
    `atime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
