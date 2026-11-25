-- Apply once to an existing CharterDesk database. Safe to reapply.
CREATE TABLE IF NOT EXISTS thread_participants (
  thread_id INT NOT NULL,
  seat ENUM('host', 'guest') NOT NULL,
  display_name VARCHAR(100) NULL,
  invite_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  invite_expires_at DATETIME NULL,
  PRIMARY KEY (thread_id, seat),
  UNIQUE KEY unique_participant_invite (invite_hash),
  CONSTRAINT participant_thread FOREIGN KEY (thread_id) REFERENCES threads(id) ON DELETE CASCADE
) ENGINE=InnoDB;
