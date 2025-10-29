-- Drop kolom image jika ada
ALTER TABLE courses DROP COLUMN IF EXISTS image;

-- Tambah kolom image_path jika belum ada
ALTER TABLE courses ADD COLUMN IF NOT EXISTS image_path VARCHAR(255) NOT NULL DEFAULT 'assets/default-course.jpg';

-- Update record yang sudah ada dengan default value jika image_path kosong
UPDATE courses SET image_path = 'assets/default-course.jpg' WHERE image_path = ''; 