CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100) NOT NULL,
    email      VARCHAR(255) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    avatar_url TEXT         DEFAULT NULL,
    created_at BIGINT       NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boards (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id    INT UNSIGNED NOT NULL,
    title       VARCHAR(120) NOT NULL,
    description TEXT         NOT NULL DEFAULT '',
    color       VARCHAR(20)  NOT NULL DEFAULT 'pine',
    is_archived TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  BIGINT       NOT NULL,
    updated_at  BIGINT       NOT NULL,
    INDEX idx_boards_owner (owner_id, is_archived),
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notes (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    board_id    INT UNSIGNED NOT NULL,
    author_id   INT UNSIGNED NOT NULL,
    content     TEXT         NOT NULL DEFAULT '',
    pos_x       INT          NOT NULL DEFAULT 60,
    pos_y       INT          NOT NULL DEFAULT 60,
    width       INT          NOT NULL DEFAULT 220,
    height      INT          NOT NULL DEFAULT 180,
    color       VARCHAR(20)  NOT NULL DEFAULT 'yellow',
    is_archived TINYINT(1)   NOT NULL DEFAULT 0,
    updated_at  BIGINT       NOT NULL,
    INDEX idx_notes_board (board_id, is_archived),
    FOREIGN KEY (board_id)  REFERENCES boards(id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS board_members (
    board_id INT UNSIGNED NOT NULL,
    user_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (board_id, user_id),
    FOREIGN KEY (board_id) REFERENCES boards(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS activity (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    type        VARCHAR(50)  NOT NULL,
    board_id    INT UNSIGNED DEFAULT NULL,
    board_title VARCHAR(120) DEFAULT NULL,
    note_id     INT UNSIGNED DEFAULT NULL,
    created_at  BIGINT       NOT NULL,
    INDEX idx_activity_user (user_id, created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
