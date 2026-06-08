-- =========================================================================
-- A9 – Bildergalerie: Tabelle anlegen
-- Speichert Bildinformationen mit Owner und Freigabe-Status
-- =========================================================================

CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `owner_id`       INT(11)      NOT NULL,
    `filename`       VARCHAR(255) NOT NULL COMMENT 'hashed filename on disk',
    `original_name`  VARCHAR(255) NOT NULL COMMENT 'original upload name for display',
    `size`       INT(11)      NOT NULL DEFAULT 0,
    `shared`     TINYINT(1)   NOT NULL DEFAULT 0,
    `downloads`  INT(11)      NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_gallery_owner` (`owner_id`),
    CONSTRAINT `fk_gallery_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
