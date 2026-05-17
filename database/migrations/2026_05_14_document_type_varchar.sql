USE zimnat_policy_renewal;

ALTER TABLE documents MODIFY document_type VARCHAR(120) NOT NULL DEFAULT 'Other';
