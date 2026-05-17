USE zimnat_policy_renewal;

ALTER TABLE documents
  ADD COLUMN document_type ENUM('Identity Document', 'Policy Form', 'Proof of Payment', 'Signed Renewal Form', 'Beneficiary Form', 'Other') NOT NULL DEFAULT 'Other' AFTER uploaded_by,
  ADD INDEX idx_documents_type (document_type);

ALTER TABLE activity_logs
  ADD COLUMN policy_id INT UNSIGNED NULL AFTER user_id,
  ADD CONSTRAINT fk_activity_policy FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE SET NULL,
  ADD INDEX idx_activity_policy (policy_id);
