CREATE DATABASE IF NOT EXISTS zimnat_policy_renewal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE zimnat_policy_renewal;

DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS policies;
DROP TABLE IF EXISTS clients;
DROP TABLE IF EXISTS role_permissions;
DROP TABLE IF EXISTS permissions;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS app_settings;
DROP TABLE IF EXISTS document_types;
DROP TABLE IF EXISTS schema_migrations;
DROP TABLE IF EXISTS users;

CREATE TABLE schema_migrations (
  migration VARCHAR(190) PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
  slug VARCHAR(120) PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  category VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_slug VARCHAR(120) NOT NULL,
  PRIMARY KEY (role_id, permission_slug),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_slug) REFERENCES permissions(slug) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE app_settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE document_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  role VARCHAR(80) NOT NULL DEFAULT 'viewer',
  password_hash VARCHAR(255) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  INDEX idx_users_role (role),
  INDEX idx_users_active (is_active),
  INDEX idx_users_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_code VARCHAR(40) NOT NULL UNIQUE,
  client_type ENUM('Individual', 'Corporate', 'SME', 'Group') NOT NULL DEFAULT 'Individual',
  display_name VARCHAR(140) NOT NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(40) NULL,
  alternate_phone VARCHAR(40) NULL,
  national_id VARCHAR(80) NULL,
  tax_number VARCHAR(80) NULL,
  address_line1 VARCHAR(180) NULL,
  suburb VARCHAR(120) NULL,
  city VARCHAR(120) NULL,
  province VARCHAR(120) NULL,
  country VARCHAR(120) NOT NULL DEFAULT 'Zimbabwe',
  preferred_contact ENUM('Phone', 'Email', 'SMS', 'WhatsApp') NOT NULL DEFAULT 'Phone',
  segment ENUM('Retail', 'VIP', 'SME', 'Corporate') NOT NULL DEFAULT 'Retail',
  status ENUM('Active', 'Inactive', 'Watchlist') NOT NULL DEFAULT 'Active',
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  UNIQUE KEY uq_clients_email (email),
  INDEX idx_clients_name (display_name),
  INDEX idx_clients_phone (phone),
  INDEX idx_clients_city (city),
  INDEX idx_clients_segment (segment),
  INDEX idx_clients_status (status),
  INDEX idx_clients_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE policies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  client_id INT UNSIGNED NOT NULL,
  policy_number VARCHAR(60) NOT NULL UNIQUE,
  insurance_type VARCHAR(80) NOT NULL,
  premium_amount DECIMAL(12,2) NOT NULL,
  payment_frequency ENUM('Monthly', 'Quarterly', 'Annually') NOT NULL DEFAULT 'Monthly',
  start_date DATE NOT NULL,
  renewal_date DATE NOT NULL,
  reminder_days INT UNSIGNED NOT NULL DEFAULT 30,
  reminder_status ENUM('Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved') NOT NULL DEFAULT 'Pending',
  reminder_note VARCHAR(255) NULL,
  reminder_last_contacted_at DATETIME NULL,
  reminder_snoozed_until DATE NULL,
  status ENUM('Active', 'Expired', 'Pending Renewal') NOT NULL DEFAULT 'Active',
  assigned_to INT UNSIGNED NULL,
  notes TEXT NULL,
  created_by INT UNSIGNED NULL,
  updated_by INT UNSIGNED NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  deleted_at DATETIME NULL,
  CONSTRAINT fk_policies_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT,
  CONSTRAINT fk_policies_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_policies_updated_by FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_policies_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT chk_policies_premium_positive CHECK (premium_amount > 0),
  CONSTRAINT chk_policies_dates_ordered CHECK (renewal_date >= start_date),
  CONSTRAINT chk_policies_reminder_days CHECK (reminder_days BETWEEN 1 AND 365),
  CONSTRAINT chk_policies_number_format CHECK (policy_number REGEXP '^[A-Z0-9-]+$'),
  INDEX idx_policies_client (client_id),
  INDEX idx_policies_status (status),
  INDEX idx_policies_reminder_status (reminder_status),
  INDEX idx_policies_renewal_date (renewal_date),
  INDEX idx_policies_type (insurance_type),
  INDEX idx_policies_assigned (assigned_to),
  INDEX idx_policies_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE documents (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  policy_id INT UNSIGNED NOT NULL,
  uploaded_by INT UNSIGNED NULL,
  document_type VARCHAR(120) NOT NULL DEFAULT 'Other',
  original_name VARCHAR(255) NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  mime_type VARCHAR(120) NOT NULL,
  size_bytes INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documents_policy FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE CASCADE,
  CONSTRAINT fk_documents_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_documents_policy (policy_id),
  INDEX idx_documents_type (document_type),
  INDEX idx_documents_mime (mime_type)
) ENGINE=InnoDB;

CREATE TABLE activity_logs (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  policy_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL,
  details_json JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_activity_policy FOREIGN KEY (policy_id) REFERENCES policies(id) ON DELETE SET NULL,
  INDEX idx_activity_created (created_at),
  INDEX idx_activity_policy (policy_id),
  INDEX idx_activity_action (action)
) ENGINE=InnoDB;

INSERT INTO schema_migrations (migration) VALUES
('2026_05_14_document_type_varchar.sql'),
('2026_05_14_enterprise_workflow.sql'),
('2026_05_14_reminder_workflow.sql'),
('2026_05_14_settings_module.sql'),
('2026_05_15_clients_table.sql'),
('2026_05_15_hardening.sql');

INSERT INTO users (name, email, role, password_hash) VALUES
('Anna Dube', 'admin@zimnat.test', 'admin', '$2y$12$oW6bvHrH8nR7XfgNF/ogxODlLtDywQezLsrli6N/ipzlXsVgczI6.'),
('Michael Sibanda', 'officer@zimnat.test', 'policy_officer', '$2y$12$oW6bvHrH8nR7XfgNF/ogxODlLtDywQezLsrli6N/ipzlXsVgczI6.'),
('Viewer User', 'viewer@zimnat.test', 'viewer', '$2y$12$oW6bvHrH8nR7XfgNF/ogxODlLtDywQezLsrli6N/ipzlXsVgczI6.');

INSERT INTO roles (slug, name, description, is_system) VALUES
('admin', 'Admin', 'Full system administration access.', 1),
('policy_officer', 'Policy Officer', 'Operational access for policy and renewal work.', 1),
('viewer', 'Viewer', 'Read-only access to operational records.', 1);

INSERT INTO permissions (slug, name, category) VALUES
('dashboard.view', 'View dashboard', 'Dashboard'),
('policy.view', 'View policies', 'Policies'),
('policy.create', 'Create policies', 'Policies'),
('policy.update', 'Update policies', 'Policies'),
('policy.delete', 'Delete policies', 'Policies'),
('document.view', 'View documents', 'Documents'),
('document.upload', 'Upload documents', 'Documents'),
('document.delete', 'Delete documents', 'Documents'),
('reminder.manage', 'Manage reminders', 'Reminders'),
('client.view', 'View clients', 'Clients'),
('user.manage', 'Manage users', 'Administration'),
('role.manage', 'Manage roles and permissions', 'Administration'),
('audit.view', 'View system activity', 'Administration'),
('settings.manage', 'Manage system settings', 'Administration');

INSERT INTO role_permissions (role_id, permission_slug)
SELECT roles.id, permissions.slug FROM roles CROSS JOIN permissions WHERE roles.slug = 'admin';

INSERT INTO role_permissions (role_id, permission_slug)
SELECT roles.id, permissions.slug FROM roles
INNER JOIN permissions ON permissions.slug IN ('dashboard.view', 'policy.view', 'policy.create', 'policy.update', 'policy.delete', 'document.view', 'document.upload', 'document.delete', 'reminder.manage', 'client.view')
WHERE roles.slug = 'policy_officer';

INSERT INTO role_permissions (role_id, permission_slug)
SELECT roles.id, permissions.slug FROM roles
INNER JOIN permissions ON permissions.slug IN ('dashboard.view', 'policy.view', 'document.view', 'client.view')
WHERE roles.slug = 'viewer';

INSERT INTO app_settings (setting_key, setting_value) VALUES
('default_reminder_days', '30'),
('renewal_window_days', '30'),
('default_snooze_days', '7'),
('escalation_days', '5');

INSERT INTO document_types (name, is_required, sort_order) VALUES
('Identity Document', 1, 10),
('Policy Form', 1, 20),
('Proof of Payment', 1, 30),
('Signed Renewal Form', 1, 40),
('Beneficiary Form', 1, 50);

INSERT INTO clients
  (client_code, client_type, display_name, email, phone, alternate_phone, national_id, tax_number, address_line1, suburb, city, province, country, preferred_contact, segment, status, notes)
VALUES
('CL-2026-0001', 'Individual', 'Conor Moyo', 'conor.moyo@example.com', '+263 77 100 1001', '+263 71 100 1001', '63-145678-Q-12', NULL, '18 Samora Machel Avenue', 'Eastlea', 'Harare', 'Harare', 'Zimbabwe', 'Phone', 'Retail', 'Active', 'Employer group policy contact prefers morning calls.'),
('CL-2026-0002', 'Individual', 'Jessica Taderera', 'jessica.t@example.com', '+263 77 100 1002', '+263 71 100 1002', '63-227890-W-43', NULL, '42 Josiah Tongogara Street', 'Avondale', 'Harare', 'Harare', 'Zimbabwe', 'WhatsApp', 'VIP', 'Active', 'Renewal decisions handled directly by client.'),
('CL-2026-0003', 'Individual', 'Jacob Phiri', 'jacob.phiri@example.com', '+263 77 100 1003', NULL, '08-334455-K-21', NULL, '9 Leopold Takawira Avenue', 'Suburbs', 'Bulawayo', 'Bulawayo', 'Zimbabwe', 'SMS', 'Retail', 'Watchlist', 'Missed previous renewal window; needs closer follow-up.'),
('CL-2026-0004', 'Individual', 'Emily Roberts', 'emily.roberts@example.com', '+263 77 100 1004', '+263 78 100 1004', '75-667788-Z-10', NULL, '21 Second Street', 'Murambi', 'Mutare', 'Manicaland', 'Zimbabwe', 'Email', 'Retail', 'Active', 'Requested premium review before renewal.'),
('CL-2026-0005', 'Corporate', 'Kevin Mutasa Trading', 'kevin.mutasa@example.com', '+263 77 100 1005', '+263 242 700 105', NULL, '200145879', 'Stand 905 Industrial Road', 'Workington', 'Harare', 'Harare', 'Zimbabwe', 'Email', 'Corporate', 'Active', 'Corporate account with annual billing.'),
('CL-2026-0006', 'SME', 'Olivia Jena', 'olivia.jena@example.com', '+263 77 100 1006', NULL, '32-998877-P-54', '100778221', '4 Main Street', 'Mkoba', 'Gweru', 'Midlands', 'Zimbabwe', 'Phone', 'SME', 'Active', 'Documents pending validation.'),
('CL-2026-0007', 'Individual', 'Sam Williams', 'sam.williams@example.com', '+263 77 100 1007', '+263 71 100 1007', '12-776655-H-33', NULL, '67 Simon Mazorodze Road', 'Rhodene', 'Masvingo', 'Masvingo', 'Zimbabwe', 'WhatsApp', 'Retail', 'Inactive', 'Expired education plan awaiting client response.');

INSERT INTO policies
  (client_id, policy_number, insurance_type, premium_amount, payment_frequency, start_date, renewal_date, reminder_days, status, assigned_to, notes, created_by, updated_by)
VALUES
((SELECT id FROM clients WHERE client_code = 'CL-2026-0001'), 'ZLA-2026-1001', 'Life Assurance', 245.00, 'Monthly', '2025-06-01', '2026-06-01', 30, 'Active', 2, 'Employer group policy.', 2, 2),
((SELECT id FROM clients WHERE client_code = 'CL-2026-0002'), 'ZLA-2026-1002', 'Funeral Cover', 80.00, 'Monthly', '2025-05-27', '2026-05-22', 14, 'Pending Renewal', 2, 'Follow up before month-end.', 2, 2),
((SELECT id FROM clients WHERE client_code = 'CL-2026-0003'), 'ZLA-2026-1003', 'Education Plan', 150.00, 'Quarterly', '2025-02-15', '2026-02-15', 30, 'Expired', 2, 'Renewal missed; assign officer.', 2, 2),
((SELECT id FROM clients WHERE client_code = 'CL-2026-0004'), 'ZLA-2026-1004', 'Life Assurance', 310.00, 'Monthly', '2025-05-25', '2026-05-24', 21, 'Pending Renewal', 2, 'Client requested premium review.', 2, 2),
((SELECT id FROM clients WHERE client_code = 'CL-2026-0005'), 'ZLA-2026-1005', 'Retirement Annuity', 420.00, 'Annually', '2025-09-01', '2026-09-01', 45, 'Active', 1, NULL, 2, 2),
((SELECT id FROM clients WHERE client_code = 'CL-2026-0006'), 'ZLA-2026-1006', 'Funeral Cover', 60.00, 'Monthly', '2025-04-10', '2026-05-28', 14, 'Pending Renewal', 2, 'Documents pending validation.', 2, 2),
((SELECT id FROM clients WHERE client_code = 'CL-2026-0007'), 'ZLA-2026-1007', 'Education Plan', 190.00, 'Quarterly', '2025-03-20', '2026-04-20', 30, 'Expired', 1, NULL, 2, 2);

INSERT INTO activity_logs (user_id, action, description) VALUES
(1, 'seed', 'Initial system users and policies were created'),
(2, 'policy_review', 'Renewal queue reviewed for upcoming policies');
