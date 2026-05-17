USE zimnat_policy_renewal;

ALTER TABLE policies
  ADD COLUMN reminder_status ENUM('Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved') NOT NULL DEFAULT 'Pending' AFTER reminder_days,
  ADD COLUMN reminder_note VARCHAR(255) NULL AFTER reminder_status,
  ADD COLUMN reminder_last_contacted_at DATETIME NULL AFTER reminder_note,
  ADD COLUMN reminder_snoozed_until DATE NULL AFTER reminder_last_contacted_at,
  ADD INDEX idx_policies_reminder_status (reminder_status);
