USE myportfolio;
ALTER TABLE profile
  ADD COLUMN age VARCHAR(30) NULL AFTER name,
  ADD COLUMN university VARCHAR(200) NULL AFTER age,
  ADD COLUMN programme VARCHAR(200) NULL AFTER university,
  ADD COLUMN portrait_url VARCHAR(500) DEFAULT 'assets/images/idris-portrait.png' AFTER programme;
