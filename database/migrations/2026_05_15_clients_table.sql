USE zimnat_policy_renewal;

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
  UNIQUE KEY uq_clients_email (email),
  INDEX idx_clients_name (display_name),
  INDEX idx_clients_phone (phone),
  INDEX idx_clients_city (city),
  INDEX idx_clients_segment (segment),
  INDEX idx_clients_status (status)
) ENGINE=InnoDB;

INSERT INTO clients
  (client_code, client_type, display_name, email, phone, national_id, city, province, country, preferred_contact, segment, status, notes)
SELECT
  CONCAT('CL-', YEAR(CURDATE()), '-', LPAD(MIN(id), 4, '0')),
  'Individual',
  TRIM(client_name),
  NULLIF(LOWER(TRIM(client_email)), ''),
  NULLIF(TRIM(client_phone), ''),
  CONCAT('MIG-', LPAD(MIN(id), 6, '0')),
  'Harare',
  'Harare',
  'Zimbabwe',
  'Phone',
  'Retail',
  'Active',
  'Migrated from policy contact fields.'
FROM policies
GROUP BY TRIM(client_name), NULLIF(LOWER(TRIM(client_email)), ''), NULLIF(TRIM(client_phone), '');

UPDATE clients SET
  alternate_phone = '+263 71 100 1001',
  national_id = '63-145678-Q-12',
  address_line1 = '18 Samora Machel Avenue',
  suburb = 'Eastlea',
  city = 'Harare',
  province = 'Harare',
  preferred_contact = 'Phone',
  segment = 'Retail',
  notes = 'Employer group policy contact prefers morning calls.'
WHERE display_name = 'Conor Moyo';

UPDATE clients SET
  alternate_phone = '+263 71 100 1002',
  national_id = '63-227890-W-43',
  address_line1 = '42 Josiah Tongogara Street',
  suburb = 'Avondale',
  city = 'Harare',
  province = 'Harare',
  preferred_contact = 'WhatsApp',
  segment = 'VIP',
  notes = 'Renewal decisions handled directly by client.'
WHERE display_name = 'Jessica Taderera';

UPDATE clients SET
  national_id = '08-334455-K-21',
  address_line1 = '9 Leopold Takawira Avenue',
  suburb = 'Suburbs',
  city = 'Bulawayo',
  province = 'Bulawayo',
  preferred_contact = 'SMS',
  segment = 'Retail',
  status = 'Watchlist',
  notes = 'Missed previous renewal window; needs closer follow-up.'
WHERE display_name = 'Jacob Phiri';

UPDATE clients SET
  alternate_phone = '+263 78 100 1004',
  national_id = '75-667788-Z-10',
  address_line1 = '21 Second Street',
  suburb = 'Murambi',
  city = 'Mutare',
  province = 'Manicaland',
  preferred_contact = 'Email',
  segment = 'Retail',
  notes = 'Requested premium review before renewal.'
WHERE display_name = 'Emily Roberts';

UPDATE clients SET
  client_type = 'Corporate',
  display_name = 'Kevin Mutasa Trading',
  alternate_phone = '+263 242 700 105',
  national_id = NULL,
  tax_number = '200145879',
  address_line1 = 'Stand 905 Industrial Road',
  suburb = 'Workington',
  city = 'Harare',
  province = 'Harare',
  preferred_contact = 'Email',
  segment = 'Corporate',
  notes = 'Corporate account with annual billing.'
WHERE display_name = 'Kevin Mutasa';

UPDATE clients SET
  client_type = 'SME',
  national_id = '32-998877-P-54',
  tax_number = '100778221',
  address_line1 = '4 Main Street',
  suburb = 'Mkoba',
  city = 'Gweru',
  province = 'Midlands',
  preferred_contact = 'Phone',
  segment = 'SME',
  notes = 'Documents pending validation.'
WHERE display_name = 'Olivia Jena';

UPDATE clients SET
  alternate_phone = '+263 71 100 1007',
  national_id = '12-776655-H-33',
  address_line1 = '67 Simon Mazorodze Road',
  suburb = 'Rhodene',
  city = 'Masvingo',
  province = 'Masvingo',
  preferred_contact = 'WhatsApp',
  segment = 'Retail',
  status = 'Inactive',
  notes = 'Expired education plan awaiting client response.'
WHERE display_name = 'Sam Williams';

ALTER TABLE policies ADD COLUMN client_id INT UNSIGNED NULL AFTER id;

UPDATE policies
INNER JOIN clients ON (
  (clients.email IS NOT NULL AND clients.email = NULLIF(LOWER(TRIM(policies.client_email)), ''))
  OR (clients.phone IS NOT NULL AND clients.phone = NULLIF(TRIM(policies.client_phone), ''))
  OR clients.display_name = TRIM(policies.client_name)
)
SET policies.client_id = clients.id
WHERE policies.client_id IS NULL;

ALTER TABLE policies MODIFY client_id INT UNSIGNED NOT NULL;
ALTER TABLE policies ADD CONSTRAINT fk_policies_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE RESTRICT;
ALTER TABLE policies ADD INDEX idx_policies_client (client_id);
DROP INDEX idx_policies_client_name ON policies;
ALTER TABLE policies
  DROP COLUMN client_name,
  DROP COLUMN client_email,
  DROP COLUMN client_phone;
