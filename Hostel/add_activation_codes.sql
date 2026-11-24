-- Run once if you already imported an older database.sql (keeps all your data).
USE hostel_db;
ALTER TABLE students
  ADD COLUMN activation_code_hash VARCHAR(255) NULL AFTER password_hash,
  ADD COLUMN activation_expires DATETIME NULL AFTER activation_code_hash,
  ADD COLUMN activation_attempts TINYINT NOT NULL DEFAULT 0 AFTER activation_expires;
-- Students without an account yet get their code from the warden panel (Students -> New code).
