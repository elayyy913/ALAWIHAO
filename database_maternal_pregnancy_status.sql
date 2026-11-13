ALTER TABLE maternal_registration
    ADD COLUMN pregnancy_status ENUM('Active', 'Completed') NOT NULL DEFAULT 'Active' AFTER status,
    ADD INDEX idx_maternal_user_pregnancy_status (user_id, pregnancy_status, status);
