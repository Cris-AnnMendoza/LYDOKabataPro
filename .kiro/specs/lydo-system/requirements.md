# Requirements Document

## Introduction

The LYDO System (Local Youth Development Office – Sta. Cruz, Laguna) is a web-based platform that digitizes and manages four official LYDO services: Youth Organization Accreditation/Registration, Youth Organization Assistance, Youth Volunteer Program, and the Iskolar ng Bayan Scholarship Program. The system is partially built using PHP (admin panel at `admin2/`, youth portal at `youth/`), a React + Vite frontend (`frontend/src/`), and a MySQL database running on XAMPP.

The system serves two user groups: **youth users** (applicants and organization representatives) who access the portal at `youth/`, and **admin users** (LYDO staff) who manage applications through `admin2/`. This document defines the requirements needed to ensure all four services are fully and correctly delivered, covering gaps in the existing implementation.

---

## Glossary

- **LYDO**: Local Youth Development Office – Sta. Cruz, Laguna
- **NYC**: National Youth Commission
- **SK**: Sangguniang Kabataan
- **LGU**: Local Government Unit
- **System**: The LYDO web-based platform
- **Youth_Portal**: The PHP application at `youth/` accessed by registered youth users
- **Admin_Panel**: The PHP application at `admin2/` accessed by LYDO staff
- **Applicant**: A registered youth user who submits an application for any LYDO service
- **Organization**: A youth organization registered in the `organizations` table
- **Admin**: An authenticated user of the Admin_Panel with a role of `super_admin`, `youth_coordinator`, `barangay_admin`, or `staff_encoder`
- **Certificate**: A digitally generated PDF document issued by the System upon approval of an accreditation application
- **Batch**: A defined scholarship intake period with slots, eligibility criteria, and deadlines
- **Volunteer_Program**: A structured activity under the Youth Volunteer Program with defined slots, age range, and schedule
- **GWA**: General Weighted Average (academic grade metric used in the Philippines)

---

## Requirements

### Requirement 1: Youth User Registration and Authentication

**User Story:** As a youth resident of Sta. Cruz, Laguna, I want to register and log in to the LYDO portal, so that I can access and apply for LYDO services.

#### Acceptance Criteria

1. WHEN a visitor submits a registration form with valid personal information, THE System SHALL create a new `youth_users` record with status `approved` and redirect the user to the login page.
2. WHEN a visitor submits a registration form with an email that already exists in `youth_users`, THE System SHALL reject the submission and display a descriptive error message.
3. WHEN a registered youth user submits valid credentials, THE System SHALL authenticate the user and establish a session.
4. IF a login attempt is made with invalid credentials, THEN THE System SHALL reject the attempt and display a generic error message without revealing which field is incorrect.
5. WHEN an authenticated youth user requests logout, THE System SHALL destroy the session and redirect to the login page.
6. THE System SHALL require that a youth user's age is between 15 and 30 years old at the time of registration, consistent with the NYC definition of youth.

---

### Requirement 2: Youth Organization Accreditation / Registration Program

**User Story:** As a youth organization representative, I want to submit an accreditation application online, so that my organization can become eligible to participate in LYDO programs and collaborate with the LGU.

#### Acceptance Criteria

1. WHEN an authenticated youth user submits an accreditation application with all required fields and all five required documents, THE System SHALL create an `accreditation_applications` record with status `submitted` and initialize all six workflow steps in `accreditation_workflow` with the first step marked `completed`.
2. WHEN an authenticated youth user attempts to submit a new accreditation application while an existing application with status other than `rejected` exists for that user, THE System SHALL prevent the submission and display an informative message.
3. THE System SHALL require the following five documents for accreditation: Letter of Intent, NYC Accreditation Form, Officers and Members List, Constitution and By-Laws, and LYDO Accreditation Form.
4. IF an uploaded document exceeds 10 MB or is not one of the allowed types (PDF, DOC, DOCX, JPG, PNG), THEN THE System SHALL reject the file and display a descriptive error.
5. WHEN an Admin sets an application status to `under_review`, THE System SHALL update the application status and mark the "Document Verification" workflow step as `completed`.
6. WHEN an Admin verifies or rejects an individual document, THE System SHALL update the `accreditation_documents` record with the new status, the reviewing admin's ID, and the review timestamp.
7. WHEN an Admin approves an accreditation application, THE System SHALL set the status to `approved`, generate a unique certificate number in the format `LYDO-{YEAR}-{XXXX}`, set `valid_until` to one year from the approval date, and mark all remaining workflow steps as `completed`.
8. WHEN an Admin rejects an accreditation application, THE System SHALL set the status to `rejected` and store the rejection reason.
9. WHEN an accreditation application is approved, THE System SHALL make a downloadable PDF accreditation certificate available to the applicant in the Youth_Portal.
10. THE System SHALL display the full six-step workflow progress to the applicant in the Youth_Portal, showing completed, active, and pending steps.
11. WHEN an accreditation application is approved, THE System SHALL send an in-system notification to the applicant.
12. WHEN an accreditation application is rejected, THE System SHALL send an in-system notification to the applicant including the rejection reason.

---

### Requirement 3: Youth Organization Assistance Program

**User Story:** As a representative of an accredited youth organization, I want to submit an assistance request for a youth-led activity, so that LYDO can evaluate and provide technical support or endorsement.

#### Acceptance Criteria

1. WHEN an authenticated youth user submits an assistance request with all required fields, THE System SHALL create an `assistance_requests` record with status `submitted` and insert an initial entry in `assistance_timeline`.
2. THE System SHALL require the following fields for an assistance request: organization selection, representative name, contact number, activity title, and activity description.
3. WHEN an assistance request is submitted, THE System SHALL notify all active admin users via the `notifications` table.
4. WHEN an Admin updates the status of an assistance request, THE System SHALL record the status change in `assistance_timeline` with the admin's ID, the new status label, and an optional note.
5. WHEN an Admin sets an assistance request status to `approved`, THE System SHALL allow the Admin to set a scheduled date for the activity.
6. WHEN an Admin sets an assistance request status to `declined`, THE System SHALL require a decline reason and store it in the `assistance_requests` record.
7. WHEN an Admin posts a comment on an assistance request, THE System SHALL store the comment in `assistance_comments` with `author_type` set to `admin` and notify the submitting youth user.
8. WHEN a youth user posts a comment on their own assistance request, THE System SHALL store the comment in `assistance_comments` with `author_type` set to `youth`.
9. THE System SHALL display the full timeline of status changes to the applicant in the Youth_Portal, ordered chronologically.
10. THE System SHALL display all comments from both admin and youth users on the request detail view in both the Youth_Portal and Admin_Panel.
11. WHEN an assistance request status is updated, THE System SHALL send an in-system notification to the submitting youth user.

---

### Requirement 4: Youth Volunteer Program

**User Story:** As a youth resident, I want to register for volunteer programs and track my participation, so that I can contribute to community activities and earn recognition for my service.

#### Acceptance Criteria

1. WHEN an Admin creates a new volunteer program, THE System SHALL store the program in `volunteer_programs` with name, type, description, age range, slot count, location, start date, end date, and the creating admin's ID.
2. THE System SHALL support three program types: `youth_volunteer` (Youth Volunteer Program), `linggo_kabataan` (Linggo ng Kabataan), and `junior_officials` (Junior Officials Program).
3. WHEN an authenticated youth user registers for a volunteer program, THE System SHALL validate that the user's age falls within the program's `min_age` and `max_age` range before creating a `volunteer_registrations` record.
4. WHEN an authenticated youth user attempts to register for a program they are already registered in, THE System SHALL prevent the duplicate registration and display an informative message.
5. WHEN the number of approved registrations for a program equals the program's `slots` value, THE System SHALL prevent new registrations and display a "Slots full" message.
6. WHEN an Admin updates a volunteer registration status to `approved`, THE System SHALL allow the Admin to set an orientation date and add notes.
7. WHEN an Admin records attendance for a volunteer, THE System SHALL insert a record in `volunteer_attendance` and increment the `total_hours` field in the corresponding `volunteer_registrations` record by the recorded hours.
8. WHEN an Admin issues a certificate for a volunteer registration with status `approved`, THE System SHALL set `certificate_issued` to `1` in `volunteer_registrations`.
9. WHEN a volunteer's `certificate_issued` flag is `1`, THE System SHALL make a downloadable volunteer certificate available to the youth user in the Youth_Portal.
10. THE System SHALL display a leaderboard of volunteers ranked by total accumulated hours, showing name, barangay, number of programs, and total hours.
11. THE Youth_Portal SHALL display the youth user's own attendance records, including event name, program, date, hours, and attendance status.

---

### Requirement 5: Iskolar ng Bayan Scholarship Program Application

**User Story:** As a qualified youth resident, I want to apply for the Iskolar ng Bayan scholarship, so that I can receive financial assistance for my higher education.

#### Acceptance Criteria

1. WHEN an Admin creates a scholarship batch, THE System SHALL store the batch in `scholarship_batches` with name, school year, semester, slot count, minimum GWA, maximum household income, application start and end dates, exam date, exam time, exam venue, and description.
2. THE System SHALL display only scholarship batches with status `open` to youth users in the batch listing view.
3. WHEN an authenticated youth user submits a scholarship application for an open batch, THE System SHALL create a `scholarship_applications` record with status `submitted` and store all personal, academic, and family information fields.
4. WHEN an authenticated youth user attempts to apply for a batch they have already applied to, THE System SHALL prevent the duplicate application and display an informative message.
5. THE System SHALL require the following documents for a scholarship application: Scholarship Application Form, Birth Certificate, Copy of Grades, School ID, and Proof of Household Income / Certificate of Indigency.
6. IF an uploaded scholarship document exceeds 10 MB or is not one of the allowed types (PDF, DOC, DOCX, JPG, PNG), THEN THE System SHALL reject the file and display a descriptive error.
7. WHEN an Admin verifies or rejects a scholarship document, THE System SHALL update the `scholarship_documents` record with the new status, the reviewing admin's ID, and the review timestamp.
8. WHEN an Admin sets a scholarship application status to `for_exam`, THE System SHALL allow the Admin to set an exam schedule date.
9. WHEN an Admin records an exam score for a scholarship application, THE System SHALL store the score in `scholarship_applications.exam_score`.
10. WHEN an Admin records a qualification score for a scholarship application, THE System SHALL store the score in `scholarship_applications.qualification_score`.
11. WHEN an Admin sets a scholarship application status to `rejected`, THE System SHALL require a rejection reason and store it in `scholarship_applications.rejection_reason`.
12. WHEN a scholarship application status is set to `approved` or `beneficiary`, THE System SHALL display the applicant in the beneficiaries list in the Admin_Panel.
13. THE Youth_Portal SHALL display the applicant's exam schedule when the application status is `for_exam` and an exam schedule has been set.
14. THE Youth_Portal SHALL display the applicant's exam score and qualification score when they have been recorded.
15. WHEN a scholarship application status is updated, THE System SHALL send an in-system notification to the applicant.

---

### Requirement 6: Admin Panel – Application Management

**User Story:** As a LYDO staff member, I want to manage all service applications from a central admin panel, so that I can efficiently process, evaluate, and act on each application.

#### Acceptance Criteria

1. THE Admin_Panel SHALL display a filterable list of applications for each service, supporting filter by status.
2. THE Admin_Panel SHALL display per-status counts for each service's application list.
3. WHEN an Admin views a single application, THE Admin_Panel SHALL display all submitted information, uploaded documents with their verification status, and the current workflow or timeline.
4. THE Admin_Panel SHALL restrict access so that only authenticated admin users can view or modify application data.
5. IF an unauthenticated request is made to any Admin_Panel page, THEN THE System SHALL redirect the request to the admin login page.
6. THE Admin_Panel SHALL log all significant admin actions (approve, reject, status update, document verify) in `admin_activity_log` with the admin ID, action description, and timestamp.
7. WHILE a scholarship batch status is `open`, THE Admin_Panel SHALL allow the Admin to change the batch status to `closed`, `evaluation`, or `completed`.

---

### Requirement 7: Notifications

**User Story:** As a youth user, I want to receive in-system notifications about my application status changes, so that I stay informed without needing to check manually.

#### Acceptance Criteria

1. WHEN a notification is created for a youth user, THE System SHALL store it in the `notifications` table with `is_read` set to `0`.
2. WHEN a youth user views a page that displays notifications, THE System SHALL mark all unread notifications for that user as `is_read = 1`.
3. THE Youth_Portal topbar SHALL display the count of unread notifications for the authenticated user.
4. THE System SHALL send notifications to the applicant for the following events: accreditation application approved, accreditation application rejected, assistance request status updated, scholarship application status updated.

---

### Requirement 8: Document Upload and File Management

**User Story:** As a system operator, I want all uploaded documents to be stored securely and consistently, so that files are accessible for review and cannot be exploited.

#### Acceptance Criteria

1. THE System SHALL store uploaded files in the appropriate subdirectory under `uploads/` (e.g., `uploads/accreditation/`, `uploads/assistance/`, `uploads/scholarship/`).
2. THE System SHALL generate a unique filename for each uploaded file using `uniqid()` to prevent filename collisions.
3. IF an uploaded file's extension is not in the allowed list (pdf, doc, docx, jpg, jpeg, png), THEN THE System SHALL reject the upload and return an error.
4. IF an uploaded file exceeds 10 MB, THEN THE System SHALL reject the upload and return an error.
5. THE System SHALL use a temporary upload directory for multi-step form submissions, moving files to the permanent directory only upon final successful submission.
6. WHEN a final submission fails validation, THE System SHALL retain the temporarily uploaded files in the session so the user does not need to re-upload them.

---

### Requirement 9: Certificate Generation

**User Story:** As an approved applicant or volunteer, I want to download a certificate that proves my accreditation or volunteer service, so that I can present it as official documentation.

#### Acceptance Criteria

1. WHEN an accreditation application is approved, THE System SHALL generate a PDF certificate containing the organization name, certificate number, approval date, and validity date.
2. WHEN a volunteer registration has `certificate_issued = 1`, THE System SHALL generate a PDF volunteer certificate containing the volunteer's name, program name, total hours, and issue date.
3. THE System SHALL make certificates accessible only to the authenticated youth user who owns the record.
4. IF a certificate is requested for a record that does not belong to the authenticated user, THEN THE System SHALL return an authorization error.

---

### Requirement 10: Data Export

**User Story:** As a LYDO administrator, I want to export application data to a spreadsheet, so that I can produce reports for the LGU and NYC.

#### Acceptance Criteria

1. THE Admin_Panel SHALL provide an export function for scholarship applications that generates a downloadable file (CSV or Excel) containing applicant name, school, course, year level, GWA, household income, batch name, status, and exam score.
2. THE Admin_Panel SHALL allow filtering the export by batch and by application status before generating the file.
3. WHEN an export is generated, THE System SHALL log the export action in `admin_activity_log`.
