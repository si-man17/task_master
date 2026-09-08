CREATE TABLE IF NOT EXISTS tasks (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title       VARCHAR(255) NOT NULL,
    description TEXT NULL,
    status      ENUM('todo', 'in_progress', 'done') NOT NULL DEFAULT 'todo',
    position    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_status_position (status, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
