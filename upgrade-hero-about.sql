USE myportfolio;
ALTER TABLE profile
  ADD COLUMN hero_label VARCHAR(160) DEFAULT 'COMPUTER SCIENCE STUDENT' AFTER name,
  ADD COLUMN hero_title VARCHAR(255) DEFAULT 'Building thoughtful digital experiences.' AFTER hero_label,
  ADD COLUMN about_title VARCHAR(255) DEFAULT 'Design-minded. Systems-focused.' AFTER intro,
  ADD COLUMN about_extra TEXT AFTER about;
