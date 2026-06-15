-- =========================================================================
-- Kanban-Board: Tabellen anlegen
-- Feature: feature/kanban-board
-- =========================================================================

-- -------------------------------------------------------------------------
-- 1. Projekte
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `projects` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT         NULL,
    `owner_id`    INT(11)      NOT NULL,
    `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_projects_owner` (`owner_id`),
    CONSTRAINT `fk_projects_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 2. Projektmitglieder
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `project_members` (
    `id`         INT(11)                  NOT NULL AUTO_INCREMENT,
    `project_id` INT(11)                  NOT NULL,
    `user_id`    INT(11)                  NOT NULL,
    `role`       ENUM('owner','member')   NOT NULL DEFAULT 'member',
    `joined_at`  TIMESTAMP                NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_project_user` (`project_id`, `user_id`),
    CONSTRAINT `fk_pm_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pm_user`    FOREIGN KEY (`user_id`)    REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 3. Tasks
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tasks` (
    `id`          INT(11)                            NOT NULL AUTO_INCREMENT,
    `project_id`  INT(11)                            NOT NULL,
    `title`       VARCHAR(200)                       NOT NULL,
    `description` TEXT                               NULL,
    `status`      ENUM('todo','inprogress','done')   NOT NULL DEFAULT 'todo',
    `assigned_to` INT(11)                            NULL,
    `created_by`  INT(11)                            NOT NULL,
    `deadline`    DATE                               NULL,
    `created_at`  TIMESTAMP                          NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tasks_project`  (`project_id`),
    KEY `idx_tasks_assigned` (`assigned_to`),
    CONSTRAINT `fk_tasks_project`  FOREIGN KEY (`project_id`)  REFERENCES `projects` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tasks_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
    CONSTRAINT `fk_tasks_creator`  FOREIGN KEY (`created_by`)  REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------------------
-- 4. Task-Kommentare
-- -------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `task_comments` (
    `id`           INT(11)   NOT NULL AUTO_INCREMENT,
    `task_id`      INT(11)   NOT NULL,
    `user_id`      INT(11)   NOT NULL,
    `comment_text` TEXT      NOT NULL,
    `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tc_task` (`task_id`),
    CONSTRAINT `fk_tc_task` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tc_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
