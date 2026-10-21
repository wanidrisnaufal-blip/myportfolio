CREATE TABLE IF NOT EXISTS contact_portraits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_url VARCHAR(500) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO contact_portraits(image_url, sort_order)
SELECT portrait_url, 1 FROM profile
WHERE id=1 AND portrait_url IS NOT NULL AND portrait_url<>''
  AND NOT EXISTS (SELECT 1 FROM contact_portraits);
