# Implementation Plan: LYDO System

## Overview

Implement the four LYDO services completely and correctly in the existing PHP/MySQL codebase. Tasks are ordered to address foundational gaps first (schema, shared utilities), then complete each service, and finally wire up cross-cutting concerns (notifications, certificates, export, activity logging).

## Tasks

- [ ] 1. Database schema migration — add all missing tables and columns
  - Create `volunteer_programs`, `volunteer_registrations`, `volunteer_attendance` tables
  - Create `scholarship_batches`, `scholarship_applications`, `scholarship_documents` tables
  - Add missing columns to `assistance_requests`: `title`, `activity_type`, `objectives`, `expected_outcome`, `participants`, `target_date`, `target_venue`, `budget_requested`, `scheduled_date`, `decline_reason`, `organization_id`, `representative_name`, `contact_number`, `contact_email`
  - Add missing columns to `assistance_documents`: `filename`, `original_name`, `file_size`
  - Add `assistance_timeline` and `assistance_comments` tables
  - Rename `accreditation_documents.file_path` to `filename` (or add `filename` column) for consistency with PHP code
  - Add `link`, `title`, `category` columns to `notifications` table
  - Add `organization_id_text` column to `assistance_requests`
  - _Requirements: 2.1, 3.1, 4.1, 5.1, 8.1_

- [ ] 2. Shared file validator utility
  - [ ] 2.1 Create `shared/file_validator.php` with `validateUpload(array $file, array $allowedExts, int $maxMB): array` returning `['ok' => bool, 'error' => string]`
    - Check extension against allowed list (case-insensitive)
    - Check file size against max bytes
    - _Requirements: 2.4, 5.6, 8.3, 8.4_
  - [ ]* 2.2 Write property test for file type rejection (Property 5)
    - **Property 5: File type rejection**
    - **Validates: Requirements 2.4, 5.6, 8.3**
    - Generate random invalid extensions; assert all return `ok = false`
  - [ ]* 2.3 Write property test for file size rejection (Property 6)
    - **Property 6: File size rejection**
    - **Validates: Requirements 2.4, 5.6, 8.4**
    - Generate random sizes > 10 MB; assert all return `ok = false`

- [ ] 3. Shared notification helper — reconcile and standardize
  - Verify `shared/notify.php` `notifyUser()` signature matches all call sites in `admin2/assistance.php`, `admin2/accreditation.php`, `admin2/scholarship_admin.php`, `admin2/volunteer_admin.php`
  - Fix the `admin2/assistance.php` call that incorrectly inserts into `notifications` using `admin_id` instead of `user_id`
  - Ensure `notifyApprovalUpdate()` is called on every status change for accreditation, assistance, scholarship, and volunteer
  - _Requirements: 2.11, 2.12, 3.3, 3.11, 5.15, 7.1, 7.4_

- [ ] 4. Admin activity logging
  - [ ] 4.1 Create `shared/activity_log.php` with `logAdminAction(PDO $pdo, int $adminId, string $action, string $details = '', string $ip = ''): void`
    - Inserts into `admin_activity_log`
    - _Requirements: 6.6_
  - [ ] 4.2 Add `logAdminAction()` calls to all significant admin actions in `admin2/accreditation.php` (approve, reject, verify doc, advance step)
    - _Requirements: 6.6_
  - [ ] 4.3 Add `logAdminAction()` calls to `admin2/assistance.php` (status update, comment)
    - _Requirements: 6.6_
  - [ ] 4.4 Add `logAdminAction()` calls to `admin2/volunteer_admin.php` (status update, attendance, certificate issue)
    - _Requirements: 6.6_
  - [ ] 4.5 Add `logAdminAction()` calls to `admin2/scholarship_admin.php` (status update, doc verify, batch create/update)
    - _Requirements: 6.6_

- [ ] 5. Checkpoint — Ensure schema migration runs cleanly and shared utilities are importable
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Accreditation service — complete and harden
  - [ ] 6.1 Apply `validateUpload()` from `shared/file_validator.php` in `youth/accreditation.php` before `move_uploaded_file`; reject and display per-file errors without losing other temp files
    - _Requirements: 2.4, 8.3, 8.4, 8.5, 8.6_
  - [ ] 6.2 Fix the orphaned `move_uploaded_file` call in `youth/accreditation.php` (the bare `move_uploaded_file` after the temp-file loop that references undefined `$type` and `$fname`)
    - _Requirements: 2.1_
  - [ ] 6.3 Add `notifyApprovalUpdate()` call in `admin2/accreditation.php` approve and reject actions
    - _Requirements: 2.11, 2.12_
  - [ ] 6.4 Implement accreditation PDF certificate generation in `youth/certificate.php` (separate route for `?type=accreditation&app=ID`)
    - Query `accreditation_applications` where `id = ?` and `submitted_by = $_SESSION['user_id']` and `status = 'approved'`
    - Render HTML certificate with organization name, certificate number, approval date, validity date
    - Add print/save-as-PDF controls matching the volunteer certificate style
    - _Requirements: 2.9, 9.1, 9.3, 9.4_
  - [ ] 6.5 Add certificate download link in `youth/accreditation.php` when application status is `approved`
    - _Requirements: 2.9_
  - [ ]* 6.6 Write property test for accreditation duplicate prevention (Property 1)
    - **Property 1: Accreditation duplicate prevention**
    - **Validates: Requirements 2.2**
  - [ ]* 6.7 Write property test for workflow step completeness on approval (Property 2)
    - **Property 2: Workflow step completeness on approval**
    - **Validates: Requirements 2.7**
  - [ ]* 6.8 Write property test for certificate number uniqueness (Property 3)
    - **Property 3: Certificate number uniqueness**
    - **Validates: Requirements 2.7**
  - [ ]* 6.9 Write property test for document count invariant (Property 4)
    - **Property 4: Document count invariant after submission**
    - **Validates: Requirements 2.1, 2.3**

- [ ] 7. Assistance service — complete and harden
  - [ ] 7.1 Apply `validateUpload()` in `youth/assistance.php` final submit handler for each uploaded document
    - _Requirements: 8.3, 8.4_
  - [ ] 7.2 Ensure `assistance_timeline` insert uses `done_by` column (admin ID) in `admin2/assistance.php` status update action
    - _Requirements: 3.4_
  - [ ] 7.3 Add `notifyApprovalUpdate()` call in `admin2/assistance.php` status update action to notify the submitting youth user
    - _Requirements: 3.11_
  - [ ] 7.4 Enforce decline reason requirement: in `admin2/assistance.php`, when `new_status = 'declined'`, validate that `decline_reason` is non-empty before updating
    - _Requirements: 3.6_
  - [ ] 7.5 Notify all active admins on new assistance request submission in `youth/assistance.php` (insert into `notifications` for each active admin using `user_id` — or add a separate admin notifications mechanism)
    - _Requirements: 3.3_
  - [ ]* 7.6 Write property test for notification delivery on status change (Property 11)
    - **Property 11: Notification delivery on status change**
    - **Validates: Requirements 3.11, 2.11, 2.12, 5.15**

- [ ] 8. Volunteer service — complete and harden
  - [ ] 8.1 Verify `volunteer_programs`, `volunteer_registrations`, `volunteer_attendance` tables exist (created in Task 1); confirm all queries in `youth/volunteer.php` and `admin2/volunteer_admin.php` match the schema
    - _Requirements: 4.1, 4.2_
  - [ ] 8.2 Enforce slot limit in `youth/volunteer.php` registration handler: count approved registrations before inserting; reject if `>= slots`
    - _Requirements: 4.5_
  - [ ] 8.3 Add `notifyApprovalUpdate()` call in `admin2/volunteer_admin.php` when registration status is updated
    - _Requirements: 4.6_
  - [ ] 8.4 Confirm `youth/certificate.php` volunteer certificate route is accessible and authorization check (user must own the registration) is enforced
    - _Requirements: 4.9, 9.2, 9.3, 9.4_
  - [ ]* 8.5 Write property test for volunteer duplicate registration prevention (Property 7)
    - **Property 7: Volunteer duplicate registration prevention**
    - **Validates: Requirements 4.4**
  - [ ]* 8.6 Write property test for volunteer slot enforcement (Property 8)
    - **Property 8: Volunteer slot enforcement**
    - **Validates: Requirements 4.5**
  - [ ]* 8.7 Write property test for attendance hours accumulation (Property 9)
    - **Property 9: Attendance hours accumulation**
    - **Validates: Requirements 4.7**

- [ ] 9. Scholarship service — complete and harden
  - [ ] 9.1 Verify `scholarship_batches`, `scholarship_applications`, `scholarship_documents` tables exist (created in Task 1); confirm all queries in `youth/scholarship.php` and `admin2/scholarship_admin.php` match the schema
    - _Requirements: 5.1, 5.2_
  - [ ] 9.2 Apply `validateUpload()` in `youth/scholarship.php` apply handler for each uploaded document
    - _Requirements: 5.6, 8.3, 8.4_
  - [ ] 9.3 Add `notifyApprovalUpdate()` call in `admin2/scholarship_admin.php` `update_status` action
    - _Requirements: 5.15_
  - [ ] 9.4 Enforce rejection reason requirement in `admin2/scholarship_admin.php`: when `status = 'rejected'`, validate that `rejection_reason` is non-empty
    - _Requirements: 5.11_
  - [ ] 9.5 Add beneficiaries list view in `admin2/scholarship_admin.php` (filter applications where `status IN ('approved','beneficiary')`)
    - _Requirements: 5.12_
  - [ ]* 9.6 Write property test for scholarship duplicate application prevention (Property 10)
    - **Property 10: Scholarship duplicate application prevention**
    - **Validates: Requirements 5.4**

- [ ] 10. Checkpoint — Ensure all four service flows work end-to-end
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Notifications — read-state and topbar count
  - [ ] 11.1 Verify `youth/notifications.php` marks all unread notifications as `is_read = 1` when the page is loaded
    - _Requirements: 7.2_
  - [ ] 11.2 Verify `youth/topbar.php` displays the unread notification count badge using the `$notifCount` variable already computed in each page
    - _Requirements: 7.3_
  - [ ]* 11.3 Write property test for notification read-state transition (Property 12)
    - **Property 12: Notification read-state transition**
    - **Validates: Requirements 7.2**

- [ ] 12. Certificate authorization hardening
  - [ ] 12.1 In `youth/certificate.php`, add a route for accreditation certificates (`?type=accreditation&app=ID`) that queries `accreditation_applications` with `submitted_by = $_SESSION['user_id']` guard
    - _Requirements: 9.1, 9.3, 9.4_
  - [ ] 12.2 Confirm the volunteer certificate route already enforces `r.user_id = $_SESSION['user_id']` in the SQL query (it does — verify no bypass exists)
    - _Requirements: 9.3, 9.4_
  - [ ]* 12.3 Write property test for certificate access authorization (Property 14)
    - **Property 14: Certificate access authorization**
    - **Validates: Requirements 9.3, 9.4**

- [ ] 13. Data export — scholarship
  - [ ] 13.1 Implement or verify `admin2/scholarship_export.php` generates a CSV with columns: applicant name, school, course, year level, GWA, household income, batch name, status, exam score
    - _Requirements: 10.1_
  - [ ] 13.2 Add batch and status filter parameters to the export URL and apply them in the query
    - _Requirements: 10.2_
  - [ ] 13.3 Add `logAdminAction()` call when an export is generated
    - _Requirements: 10.3_

- [ ] 14. Unique filename generation — verify and harden
  - [ ] 14.1 Confirm all file upload handlers (accreditation, assistance, scholarship) use `uniqid($prefix, true)` for filename generation; update any that use predictable names
    - _Requirements: 8.2_
  - [ ]* 14.2 Write property test for unique filename generation (Property 13)
    - **Property 13: Unique filename generation**
    - **Validates: Requirements 8.2**

- [ ] 15. Admin panel — access control and filter completeness
  - [ ] 15.1 Verify `requireLogin()` is called at the top of every file in `admin2/`; add it to any file missing it
    - _Requirements: 6.4, 6.5_
  - [ ] 15.2 Verify status filter dropdowns are present and functional in `admin2/accreditation.php`, `admin2/assistance.php`, `admin2/volunteer_admin.php`, `admin2/scholarship_admin.php`
    - _Requirements: 6.1, 6.2_

- [ ] 16. Final checkpoint — Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP
- Task 1 (schema migration) must be completed before any service task
- Tasks 2–4 (shared utilities) should be completed before service tasks 6–9
- Each property test references a specific property from `design.md`
- Property tests should run a minimum of 100 iterations each
- Tag format for property tests: `// Feature: lydo-system, Property N: <property_text>`
