-- Fix admin password (admin123)
-- Run this if you cannot login with admin/admin123
UPDATE users SET password_hash = '$2y$10$rBrZEQ7IKxJRkXZ2fzRdpO4MHdYqOd.wERK/VbgIFLxfx6JEowMGS' WHERE username = 'admin';
