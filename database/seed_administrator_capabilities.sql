-- Seed Administrator with all capabilities (optional - Administrator bypasses checks via Auth::can)
-- Run this to show capabilities in the Roles UI for Administrator
-- Administrator is locked from editing, so this is for display consistency only

INSERT IGNORE INTO role_capabilities (role_id, capability)
SELECT r.id, 'manage_profiles' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_structure' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_grievance' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_projects' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_settings' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_user_profiles' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_users' FROM roles r WHERE r.name = 'Administrator'
UNION SELECT r.id, 'manage_roles' FROM roles r WHERE r.name = 'Administrator';
