CREATE TABLE IF NOT EXISTS `%SYSTEM_TABLE%`
(
    `name` varchar(512) COLLATE utf8mb4_unicode_ci NOT NULL,
    `version` int(11) unsigned DEFAULT 0,
    `atime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`name`),
    KEY `i_%SYSTEM_TABLE%_version` (`version`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
