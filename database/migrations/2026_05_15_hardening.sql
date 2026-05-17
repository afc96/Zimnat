USE zimnat_policy_renewal;

ALTER TABLE users
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD INDEX idx_users_deleted (deleted_at);

ALTER TABLE clients
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD INDEX idx_clients_deleted (deleted_at);

ALTER TABLE policies
  ADD COLUMN deleted_at DATETIME NULL AFTER updated_at,
  ADD INDEX idx_policies_deleted (deleted_at),
  ADD CONSTRAINT chk_policies_premium_positive CHECK (premium_amount > 0),
  ADD CONSTRAINT chk_policies_dates_ordered CHECK (renewal_date >= start_date),
  ADD CONSTRAINT chk_policies_reminder_days CHECK (reminder_days BETWEEN 1 AND 365),
  ADD CONSTRAINT chk_policies_number_format CHECK (policy_number REGEXP '^[A-Z0-9-]+$');

ALTER TABLE activity_logs
  ADD COLUMN details_json JSON NULL AFTER description;
