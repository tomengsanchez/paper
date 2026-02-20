-- Performance indexes for structure list with 40K+ records
-- Run once. If index exists, you may see "Duplicate key name" - safe to ignore.
--
-- From project root: mysql -u root -p paper_db2 < database/add_performance_indexes.sql
-- Or from phpMyAdmin/MySQL Workbench: paste and run.

-- Composite index for faster structure list queries
-- Speeds up: SELECT ... FROM eav_entities WHERE entity_type = 'structure' ORDER BY id
CREATE INDEX idx_entity_type_id ON eav_entities(entity_type, id);
