# PAPeR – Summary of Changes

Summary of feature and implementation changes since day one.

---

## Installation and data

- **Grievance options seed data:** Added `database/seed_grievance_options.php` to seed commonly used default data for the Grievance module (vulnerabilities, respondent types, GRM channels, preferred languages, grievance types, categories). Safe to re-run; skips tables that already have data. README installation steps updated to include: run `php database/seed_grievance_options.php` after migrations.

---

## User profile and account

- **My Profile page:** New `/account` route and `AccountController`; view shows logged-in user’s profile (username, display name, email, role, linked projects). Accessible from the user dropdown in the header (top right) as “My Profile”. Edit link shown when user has `edit_users` capability.

---

## Notifications system

- **Notification icon and dropdown:** Notification bell icon added beside the user dropdown in both sidebar and top-nav layouts. Badge shows count of unread notifications; dropdown lists recent unread items with “View all notifications” link.
- **Real-time badge:** AJAX polling (no WebSockets) for notification count; polling interval 15 seconds. API: `GET /api/notifications` (returns count and list for bell dropdown).
- **Notification settings:** On Administrator General Settings (`/settings`), new “Notification Settings” card with checkboxes:
  - Notify when **New Profile** on linked projects  
  - Notify when **Profile updated** on linked projects  
  - Notify when **New Grievance** on linked projects  
  - Notify when **Grievance status change** on linked projects  
  - (New Structure uses the same preference as New Profile for linked projects.)
- **Defaults for all users:** Migration 017 and notification preference logic ensure all existing and new users have these notification options **checked by default** (stored in `user_dashboard_config`, module `notification_preferences`).
- **Clickable notifications:** Clicking a notification in the bell or on the notifications page marks it as opened (`clicked_at` set), redirects to the related entity (profile/structure/grievance view). Notifications are no longer deleted on click so they remain in history.
- **Notification message content:** Grievance status-change notifications include **from → to** status (and level) in the message. Profile-update notifications include **modified fields** in the message (same style as Activity History: field: from → to).
- **Notification history page:** New page `/notifications` (Notification History) with:
  - Paginated table of all notifications (new and opened) for the current user  
  - **Filters:** date range (From/To), **Module** (Profile, Structure, Grievance), **Project**  
  - Columns: When, Message, Type, Status (New/Opened), Action (Open)  
  - Pagination preserves filter query params  
- **Database:** Migration 016 added `notifications` table; migration 019 added `project_id` and `clicked_at` to support project filter and “opened” state. New notifications store `project_id` for filtering.

---

## Activity and audit history

- **Audit log:** New `audit_log` table (migration 018) and `App\AuditLog` service. Records entity_type, entity_id, action (e.g. created, updated, status_changed), optional JSON `changes`, created_at, created_by.
- **History sidebar:** New partial `App/Views/partials/history_sidebar.php` used on Profile, Structure, and Grievance **view** pages. Shows:
  - **Activity History:** creation and updates from audit_log with who/when and field-level changes (from → to).
  - **Status History:** (grievance only) from grievance_status_log.
- **Recording:** ProfileController, StructureController, and GrievanceController record create/update/status_changed in AuditLog. Profile update only records when there are actual field changes; boolean fields compared robustly to avoid noise.

---

## Truncate scripts

- **truncate_seed_tables.php:** Now also truncates `notifications` and `audit_log` (in addition to structures, profiles, projects). Safe to run for a “fresh” data reset; re-seed profiles/structures and grievance options as needed. Users, roles, and user preferences (including notification settings) are not truncated.

---

## Bug fixes and small improvements

- **Profile form:** Fixed parse error in profile form view (unescaped quotes in JavaScript referencing CSRF meta tag).
- **NotificationService::getForUser:** Fixed LIMIT parameter binding (PDO does not support bound LIMIT; value inlined safely).
- **StructureController::handleUpload:** Visibility changed from private to protected so `Api\StructureController` can call it.
- **Profile view:** Activity history sidebar now always visible on profile view (removed incorrect conditional that hid it when user had no structures). Removed stray `endif` that caused parse error on profile view.
- **Profile update audit:** Refined diff logic for boolean fields and “no change” detection so irrelevant updates are not logged.

---

## File and route reference

| Area | Files / routes |
|------|-----------------|
| Account | `App/Controllers/AccountController.php`, `App/Views/account/index.php`, route `/account` |
| Notifications | `App/Controllers/NotificationController.php`, `App/NotificationService.php`, `App/UserNotificationSettings.php`, `App/Views/notifications/index.php`, routes `/notifications`, `/notifications/click/{id}`, `GET /api/notifications` |
| Settings (notifications) | `App/Controllers/SettingsController.php` (updateNotifications), `App/Views/settings/index.php` (Notification Settings card) |
| Audit / history | `App/AuditLog.php`, `App/Views/partials/history_sidebar.php`, migrations 018 |
| Notifications DB | migrations 016 (notifications table), 017 (notification defaults), 019 (project_id, clicked_at) |
| Layout (bell, user menu) | `App/Views/layout/main.php` (notification dropdown, “My Profile”, “Notifications”, “View all notifications”) |

---

*This summary reflects changes implemented during development. For current structure and conventions, see DEVELOPMENTGUIDE.md.*
