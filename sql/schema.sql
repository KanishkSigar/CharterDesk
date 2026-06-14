-- ===========================================================
-- CharterDesk - database schema
-- Run once:  mysql -u root < sql/schema.sql
-- (or import via phpMyAdmin). Matches the column set the app uses.
-- ===========================================================

CREATE DATABASE IF NOT EXISTS ime_negotiation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ime_negotiation;

-- One row per negotiation.
CREATE TABLE IF NOT EXISTS threads (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  thread_uuid   VARCHAR(64) UNIQUE NOT NULL,
  created_by    VARCHAR(100),
  status        VARCHAR(20) DEFAULT 'open',
  locked_fields LONGTEXT,                       -- JSON array of locked field ids
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- One row per offer / counter. `data` holds the full fixture terms as JSON.
CREATE TABLE IF NOT EXISTS offers (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  thread_id   INT NULL,
  thread_uuid VARCHAR(64) NOT NULL,
  version     INT DEFAULT 1,
  party       VARCHAR(100),
  role        VARCHAR(50),
  data        LONGTEXT,                          -- JSON object of fixture terms
  riders      LONGTEXT NULL,
  accepted_by VARCHAR(100) NULL,
  accepted_at DATETIME NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_offers_thread (thread_uuid),
  INDEX idx_offers_version (thread_uuid, version)
);

-- Optional: legacy single-form session flow (save_session.php / get_session.php).
CREATE TABLE IF NOT EXISTS sessions (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  session_uuid VARCHAR(64) UNIQUE NOT NULL,
  role         VARCHAR(50),
  vessel       VARCHAR(100),
  cargo        VARCHAR(100),
  quantity     VARCHAR(100),
  laycan       VARCHAR(100),
  freight      VARCHAR(100),
  riders       LONGTEXT,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
