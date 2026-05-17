USE zimnat_policy_renewal;

ALTER TABLE users MODIFY role VARCHAR(80) NOT NULL DEFAULT 'viewer';

CREATE TABLE IF NOT EXISTS roles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(80) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  description VARCHAR(255) NULL,
  is_system TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS permissions (
  slug VARCHAR(120) PRIMARY KEY,
  name VARCHAR(140) NOT NULL,
  category VARCHAR(80) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS role_permissions (
  role_id INT UNSIGNED NOT NULL,
  permission_slug VARCHAR(120) NOT NULL,
  PRIMARY KEY (role_id, permission_slug),
  CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_slug) REFERENCES permissions(slug) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS app_settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS document_types (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL UNIQUE,
  is_required TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO roles (slug, name, description, is_system) VALUES
('admin', 'Admin', 'Full system administration access.', 1),
('policy_officer', 'Policy Officer', 'Operational access for policy and renewal work.', 1),
('viewer', 'Viewer', 'Read-only access to operational records.', 1);

INSERT IGNORE INTO permissions (slug, name, category) VALUES
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

INSERT IGNORE INTO role_permissions (role_id, permission_slug)
SELECT roles.id, permissions.slug FROM roles CROSS JOIN permissions WHERE roles.slug = 'admin';

INSERT IGNORE INTO role_permissions (role_id, permission_slug)
SELECT roles.id, permissions.slug FROM roles
INNER JOIN permissions ON permissions.slug IN ('dashboard.view', 'policy.view', 'policy.create', 'policy.update', 'policy.delete', 'document.view', 'document.upload', 'document.delete', 'reminder.manage', 'client.view')
WHERE roles.slug = 'policy_officer';

INSERT IGNORE INTO role_permissions (role_id, permission_slug)
SELECT roles.id, permissions.slug FROM roles
INNER JOIN permissions ON permissions.slug IN ('dashboard.view', 'policy.view', 'document.view', 'client.view')
WHERE roles.slug = 'viewer';

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('default_reminder_days', '30'),
('renewal_window_days', '30'),
('default_snooze_days', '7'),
('escalation_days', '5');

INSERT IGNORE INTO document_types (name, is_required, sort_order) VALUES
('Identity Document', 1, 10),
('Policy Form', 1, 20),
('Proof of Payment', 1, 30),
('Signed Renewal Form', 1, 40),
('Beneficiary Form', 1, 50);
