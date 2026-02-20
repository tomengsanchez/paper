-- Denormalized table for fast structure list (40K+ records)
-- 1. Run: mysql -u root -p paper_db2 < database/add_structure_list_table.sql
-- 2. Populate: php database/rebuild_structure_list.php
--
-- After this, the structure list page uses this table when no search is applied,
-- making it much faster and avoiding gateway timeout on shared hosting.

CREATE TABLE IF NOT EXISTS structure_list (
    id INT PRIMARY KEY,
    strid VARCHAR(100) NOT NULL DEFAULT '',
    owner_id INT DEFAULT NULL,
    owner_name VARCHAR(255) DEFAULT NULL,
    structure_tag VARCHAR(255) DEFAULT NULL,
    description TEXT,
    INDEX idx_strid (strid),
    INDEX idx_owner_name (owner_name(100)),
    INDEX idx_structure_tag (structure_tag(100))
);
