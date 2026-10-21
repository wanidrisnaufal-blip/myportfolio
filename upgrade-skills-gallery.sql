ALTER TABLE profile
  ADD COLUMN skills_label VARCHAR(120) NOT NULL DEFAULT '03 — CAPABILITIES',
  ADD COLUMN skills_title VARCHAR(180) NOT NULL DEFAULT 'Learning by',
  ADD COLUMN skills_emphasis VARCHAR(120) NOT NULL DEFAULT 'making.',
  ADD COLUMN skills_intro TEXT,
  ADD COLUMN skills_empty_text VARCHAR(180) NOT NULL DEFAULT 'Skills to be added',
  ADD COLUMN gallery_title VARCHAR(180) NOT NULL DEFAULT 'A visual journal',
  ADD COLUMN gallery_intro TEXT;

CREATE TABLE IF NOT EXISTS skill_categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  sort_order INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1
);

INSERT IGNORE INTO skill_categories (name, sort_order, is_published) VALUES
  ('UI/UX Design', 1, 1),
  ('Frontend Development', 2, 1),
  ('Backend Development', 3, 1),
  ('Database', 4, 1),
  ('Tools and Workflow', 5, 1);

CREATE TABLE IF NOT EXISTS gallery (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(180) NOT NULL,
  caption VARCHAR(500) DEFAULT NULL,
  image_url VARCHAR(500) NOT NULL,
  image_alt VARCHAR(255) NOT NULL,
  sort_order INT NOT NULL DEFAULT 0,
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
