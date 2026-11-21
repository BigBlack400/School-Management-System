-- School Management System schema for MySQL 8.0+ / MariaDB 10.6+.
-- Apply to an empty database using a privileged deployment account.
SET NAMES utf8mb4;
SET time_zone = '+00:00';
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

CREATE TABLE schools (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    code VARCHAR(40) NOT NULL UNIQUE,
    timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE branches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(180) NOT NULL,
    code VARCHAR(40) NOT NULL,
    address VARCHAR(500) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_branches_school_code (school_id, code),
    UNIQUE KEY uq_branches_id_school (id, school_id),
    CONSTRAINT fk_branches_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    label VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    is_system_role BOOLEAN NOT NULL DEFAULT TRUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(500) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NULL,
    branch_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(254) NOT NULL UNIQUE,
    phone VARCHAR(32) NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active','inactive','suspended','dismissed') NOT NULL DEFAULT 'active',
    auth_version INT UNSIGNED NOT NULL DEFAULT 1,
    email_verified_at TIMESTAMP NULL,
    last_login_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_users_school_status (school_id, status),
    CONSTRAINT fk_users_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE SET NULL,
    CONSTRAINT fk_users_branch_school FOREIGN KEY (branch_id, school_id) REFERENCES branches(id, school_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    assigned_by BIGINT UNSIGNED NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    KEY idx_user_roles_role (role_id, user_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_user_roles_assigned_by FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE academic_years (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    is_current BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_academic_year (school_id, name),
    UNIQUE KEY uq_academic_year_id_school (id, school_id),
    CONSTRAINT chk_academic_year_dates CHECK (ends_on > starts_on),
    CONSTRAINT fk_academic_year_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE terms (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    starts_on DATE NOT NULL,
    ends_on DATE NOT NULL,
    UNIQUE KEY uq_terms_year_name (academic_year_id, name),
    CONSTRAINT chk_term_dates CHECK (ends_on > starts_on),
    CONSTRAINT fk_terms_academic_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE classes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    grade_level SMALLINT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_classes_school_year_name (school_id, academic_year_id, name),
    KEY idx_classes_branch (branch_id, status),
    CONSTRAINT fk_classes_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_classes_branch_school FOREIGN KEY (branch_id, school_id) REFERENCES branches(id, school_id) ON DELETE RESTRICT,
    CONSTRAINT fk_classes_academic_year_school FOREIGN KEY (academic_year_id, school_id) REFERENCES academic_years(id, school_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sections (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    capacity SMALLINT UNSIGNED NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_sections_class_name (class_id, name),
    CONSTRAINT fk_sections_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(40) NOT NULL,
    name VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    UNIQUE KEY uq_subjects_school_code (school_id, code),
    CONSTRAINT fk_subjects_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grade_scales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    min_percent DECIMAL(5,2) NOT NULL,
    max_percent DECIMAL(5,2) NOT NULL,
    letter_grade VARCHAR(8) NOT NULL,
    gpa_points DECIMAL(4,2) NOT NULL,
    UNIQUE KEY uq_grade_scale_letter (school_id, name, letter_grade),
    KEY idx_grade_scale_lookup (school_id, min_percent, max_percent),
    CONSTRAINT chk_grade_scale_range CHECK (min_percent >= 0 AND max_percent <= 100 AND max_percent >= min_percent),
    CONSTRAINT chk_grade_scale_gpa CHECK (gpa_points >= 0 AND gpa_points <= 4),
    CONSTRAINT fk_grade_scale_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE class_subjects (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_class_subject (section_id, subject_id),
    CONSTRAINT fk_class_subject_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_class_subject_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE teacher_allocations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    teacher_id BIGINT UNSIGNED NOT NULL,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    academic_year_id BIGINT UNSIGNED NOT NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_teacher_allocation (teacher_id, section_id, subject_id, academic_year_id),
    KEY idx_teacher_allocations_section (section_id, status),
    CONSTRAINT fk_teacher_alloc_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_alloc_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_teacher_alloc_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_teacher_alloc_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE students (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    user_id BIGINT UNSIGNED NULL UNIQUE,
    section_id BIGINT UNSIGNED NULL,
    admission_number VARCHAR(60) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NULL,
    gender ENUM('female','male','non_binary','undisclosed') NOT NULL DEFAULT 'undisclosed',
    admission_date DATE NOT NULL,
    status ENUM('active','graduated','transferred','withdrawn','suspended','inactive','expelled') NOT NULL DEFAULT 'active',
    address VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_students_school_admission (school_id, admission_number),
    KEY idx_students_section_status (section_id, status),
    KEY idx_students_branch_status (branch_id, status),
    CONSTRAINT fk_students_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE RESTRICT,
    CONSTRAINT fk_students_branch_school FOREIGN KEY (branch_id, school_id) REFERENCES branches(id, school_id) ON DELETE RESTRICT,
    CONSTRAINT fk_students_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_students_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guardians (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    relationship_label VARCHAR(80) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_guardians_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_guardians (
    student_id BIGINT UNSIGNED NOT NULL,
    guardian_id BIGINT UNSIGNED NOT NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    receives_notifications BOOLEAN NOT NULL DEFAULT TRUE,
    PRIMARY KEY (student_id, guardian_id),
    KEY idx_student_guardians_guardian (guardian_id, student_id),
    CONSTRAINT fk_student_guardians_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_guardians_guardian FOREIGN KEY (guardian_id) REFERENCES guardians(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_documents (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    size_bytes BIGINT UNSIGNED NOT NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student_documents_student (student_id, created_at),
    CONSTRAINT fk_student_documents_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_documents_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    from_section_id BIGINT UNSIGNED NULL,
    to_section_id BIGINT UNSIGNED NULL,
    event_type ENUM('admitted','promoted','transferred','withdrawn','status_changed') NOT NULL,
    effective_date DATE NOT NULL,
    notes VARCHAR(1000) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student_history_student_date (student_id, effective_date),
    CONSTRAINT fk_student_history_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_history_from_section FOREIGN KEY (from_section_id) REFERENCES sections(id) ON DELETE SET NULL,
    CONSTRAINT fk_student_history_to_section FOREIGN KEY (to_section_id) REFERENCES sections(id) ON DELETE SET NULL,
    CONSTRAINT fk_student_history_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','late','excused') NOT NULL,
    remarks VARCHAR(500) NULL,
    marked_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_attendance_student_day (student_id, attendance_date),
    KEY idx_attendance_date_status (attendance_date, status),
    CONSTRAINT fk_attendance_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_marker FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE staff_attendance (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_user_id BIGINT UNSIGNED NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('present','absent','late','excused') NOT NULL,
    remarks VARCHAR(500) NULL,
    recorded_by BIGINT UNSIGNED NULL,
    UNIQUE KEY uq_staff_attendance_day (staff_user_id, attendance_date),
    KEY idx_staff_attendance_date_status (attendance_date, status),
    CONSTRAINT fk_staff_attendance_staff FOREIGN KEY (staff_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_staff_attendance_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE exams (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    term_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    exam_date DATE NOT NULL,
    starts_at TIME NULL,
    ends_at TIME NULL,
    room VARCHAR(80) NULL,
    max_marks DECIMAL(8,2) NOT NULL,
    weight_percent DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_exams_section_date (section_id, exam_date),
    CONSTRAINT chk_exam_marks CHECK (max_marks > 0),
    CONSTRAINT chk_exam_weight CHECK (weight_percent > 0 AND weight_percent <= 100),
    CONSTRAINT fk_exams_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_exams_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_exams_term FOREIGN KEY (term_id) REFERENCES terms(id) ON DELETE RESTRICT,
    CONSTRAINT fk_exams_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE grades (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    exam_id BIGINT UNSIGNED NOT NULL,
    student_id BIGINT UNSIGNED NOT NULL,
    marks_obtained DECIMAL(8,2) NOT NULL,
    letter_grade VARCHAR(8) NULL,
    teacher_comment VARCHAR(1000) NULL,
    entered_by BIGINT UNSIGNED NULL,
    entered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_grades_exam_student (exam_id, student_id),
    KEY idx_grades_student (student_id, entered_at),
    CONSTRAINT chk_grade_nonnegative CHECK (marks_obtained >= 0),
    CONSTRAINT fk_grades_exam FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    CONSTRAINT fk_grades_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    CONSTRAINT fk_grades_entered_by FOREIGN KEY (entered_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(160) NOT NULL,
    instructions TEXT NULL,
    due_date DATE NULL,
    max_marks DECIMAL(8,2) NOT NULL DEFAULT 100.00,
    status ENUM('draft','published','closed') NOT NULL DEFAULT 'published',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_assignments_section_subject_due (section_id, subject_id, due_date),
    KEY idx_assignments_teacher (teacher_id, status),
    CONSTRAINT chk_assignment_marks CHECK (max_marks > 0),
    CONSTRAINT fk_assignments_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignments_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_assignments_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fee_structures (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    branch_id BIGINT UNSIGNED NULL,
    academic_year_id BIGINT UNSIGNED NULL,
    name VARCHAR(160) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    frequency ENUM('one_time','monthly','termly','annual') NOT NULL DEFAULT 'one_time',
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fee_structures_school_status (school_id, status),
    KEY idx_fee_structures_branch_status (branch_id, status),
    CONSTRAINT chk_fee_structure_amount CHECK (amount >= 0),
    CONSTRAINT fk_fee_structures_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_fee_structures_branch_school FOREIGN KEY (branch_id, school_id) REFERENCES branches(id, school_id) ON DELETE RESTRICT,
    CONSTRAINT fk_fee_structures_year FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fee_class_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fee_structure_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NOT NULL,
    due_date DATE NULL,
    is_mandatory BOOLEAN NOT NULL DEFAULT TRUE,
    assigned_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_fee_class_assignment (fee_structure_id, class_id),
    KEY idx_fee_class_assignments_class (class_id, is_mandatory),
    CONSTRAINT fk_fee_class_assignment_fee FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE,
    CONSTRAINT fk_fee_class_assignment_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_fee_class_assignment_user FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fee_invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id BIGINT UNSIGNED NOT NULL,
    invoice_number VARCHAR(80) NOT NULL UNIQUE,
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL,
    status ENUM('draft','issued','partially_paid','paid','overdue','void') NOT NULL DEFAULT 'issued',
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_fee_invoices_student_status (student_id, status, due_date),
    CONSTRAINT chk_invoice_amounts CHECK (total_amount >= 0 AND paid_amount >= 0 AND paid_amount <= total_amount),
    CONSTRAINT fk_fee_invoices_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT,
    CONSTRAINT fk_fee_invoices_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fee_invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    fee_structure_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(8,2) NOT NULL DEFAULT 1,
    unit_amount DECIMAL(12,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    CONSTRAINT chk_invoice_item_amount CHECK (quantity > 0 AND unit_amount >= 0 AND line_total >= 0),
    CONSTRAINT fk_invoice_items_invoice FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_items_structure FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE fee_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    payment_reference VARCHAR(120) NULL UNIQUE,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    payment_method ENUM('cash','bank_transfer','card','mobile_money','other') NOT NULL,
    paid_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    received_by BIGINT UNSIGNED NULL,
    notes VARCHAR(500) NULL,
    KEY idx_fee_payments_invoice_date (invoice_id, paid_at),
    CONSTRAINT chk_fee_payment_amount CHECK (amount > 0),
    CONSTRAINT fk_fee_payments_invoice FOREIGN KEY (invoice_id) REFERENCES fee_invoices(id) ON DELETE RESTRICT,
    CONSTRAINT fk_fee_payments_receiver FOREIGN KEY (received_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE timetable_periods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(80) NOT NULL,
    starts_at TIME NOT NULL,
    ends_at TIME NOT NULL,
    UNIQUE KEY uq_periods_school_name (school_id, name),
    CONSTRAINT chk_period_times CHECK (ends_at > starts_at),
    CONSTRAINT fk_timetable_period_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE timetable_entries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    section_id BIGINT UNSIGNED NOT NULL,
    subject_id BIGINT UNSIGNED NOT NULL,
    teacher_id BIGINT UNSIGNED NOT NULL,
    period_id BIGINT UNSIGNED NOT NULL,
    weekday TINYINT UNSIGNED NOT NULL,
    room VARCHAR(80) NULL,
    UNIQUE KEY uq_timetable_section_slot (section_id, weekday, period_id),
    UNIQUE KEY uq_timetable_teacher_slot (teacher_id, weekday, period_id),
    CONSTRAINT chk_timetable_weekday CHECK (weekday BETWEEN 1 AND 7),
    CONSTRAINT fk_timetable_entry_section FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
    CONSTRAINT fk_timetable_entry_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE RESTRICT,
    CONSTRAINT fk_timetable_entry_teacher FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_timetable_entry_period FOREIGN KEY (period_id) REFERENCES timetable_periods(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    channel ENUM('email','sms','in_app') NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
    provider_reference VARCHAR(180) NULL,
    error_summary VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    KEY idx_notification_user_created (user_id, created_at),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_reset_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_password_reset_user_expiry (user_id, expires_at),
    CONSTRAINT fk_password_reset_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE login_throttles (
    throttle_key CHAR(64) PRIMARY KEY,
    failure_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until TIMESTAMP NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_user_created (user_id, created_at),
    KEY idx_audit_entity (entity_type, entity_id, created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE student_applications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    class_id BIGINT UNSIGNED NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NULL,
    email VARCHAR(254) NULL,
    phone VARCHAR(32) NULL,
    guardian_name VARCHAR(120) NULL,
    guardian_phone VARCHAR(32) NULL,
    address VARCHAR(500) NULL,
    notes TEXT NULL,
    status ENUM('submitted','reviewing','accepted','rejected','waitlisted') NOT NULL DEFAULT 'submitted',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_student_applications_school_status (school_id, status, created_at),
    CONSTRAINT fk_student_applications_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_student_applications_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE school_expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    school_id BIGINT UNSIGNED NOT NULL,
    category VARCHAR(120) NOT NULL,
    description VARCHAR(500) NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL,
    expense_date DATE NOT NULL,
    recorded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_school_expenses_school_date (school_id, expense_date),
    CONSTRAINT chk_school_expense_amount CHECK (amount >= 0),
    CONSTRAINT fk_school_expenses_school FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    CONSTRAINT fk_school_expenses_recorded_by FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO schools (id, name, code, timezone, currency) VALUES
    (1, 'School', 'SCHOOL-001', 'UTC', 'USD');
INSERT INTO branches (id, school_id, name, code) VALUES
    (1, 1, 'Main Campus', 'MAIN');

INSERT INTO roles (name, label, description) VALUES
    ('super_administrator', 'Super Administrator', 'Platform-wide administration and school management'),
    ('school_administrator', 'School Administrator', 'Administration for an assigned school'),
    ('head_teacher', 'Head Teacher', 'School-wide academic, financial, digital learning, security, reporting, and workflow authority'),
    ('administrative_staff', 'Administrative Staff', 'Fee processing, basic student records, and front-desk scheduling'),
    ('it_support', 'IT Support', 'System maintenance, account management, and security monitoring'),
    ('teacher', 'Teacher', 'Teaching, attendance, and gradebook access'),
    ('student', 'Student', 'Personal academic and fee information'),
    ('parent_guardian', 'Parent / Guardian', 'Linked children information');

INSERT INTO permissions (name, description) VALUES
    ('attendance.manage', 'View and record attendance for authorized sections'),
    ('fees.manage', 'Create and manage school fee invoices'),
    ('students.manage', 'Manage student records and admissions'),
    ('academics.manage', 'Manage academic structure, exams, and grades'),
    ('users.manage', 'Manage users and role assignments'),
    ('reports.view', 'View authorized operational reports'),
    ('schools.manage', 'Manage schools and branches'),
    ('budgets.approve', 'Approve school budgets and oversee payroll permissions'),
    ('fee_collections.view', 'View real-time fee collection summaries'),
    ('fee_collections.process', 'Process fee payments and basic fee workflows'),
    ('payroll.manage', 'Oversee payroll permissions'),
    ('digital_learning.monitor', 'Monitor course content and participation analytics'),
    ('integrations.manage', 'Manage digital learning integrations'),
    ('security.manage', 'Configure security settings and monitoring'),
    ('audit.manage', 'Configure and review automated audit logs'),
    ('academic_reports.manage', 'Generate term reports, GPA reports, and failure alerts'),
    ('attendance_compliance.manage', 'Manage truancy notifications and compliance reports'),
    ('configuration.approve', 'Approve system configuration changes'),
    ('workflow.manage', 'Manage and approve workflow automation rules'),
    ('migration.manage', 'Oversee data migration accuracy'),
    ('vendor.manage', 'Oversee vendor SLAs and update schedules');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('super_administrator', 'school_administrator');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE (r.name = 'teacher' AND p.name IN ('attendance.manage', 'academics.manage', 'reports.view'));

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'head_teacher';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'administrative_staff'
  AND p.name IN ('fees.manage','fee_collections.view','fee_collections.process','students.manage','reports.view');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name = 'it_support'
  AND p.name IN ('users.manage','security.manage','audit.manage','integrations.manage','reports.view');

INSERT INTO academic_years (school_id, name, starts_on, ends_on, is_current)
VALUES (1, 'Academic Year 2026', '2026-01-01', '2026-12-31', TRUE);
INSERT INTO terms (academic_year_id, name, starts_on, ends_on)
VALUES (1, 'Term 1', '2026-01-01', '2026-04-30');
INSERT INTO classes (school_id, branch_id, academic_year_id, name, grade_level)
VALUES (1, 1, 1, 'Grade 1', 1);
INSERT INTO sections (class_id, name, capacity)
VALUES (1, 'A', 30);
INSERT INTO subjects (school_id, code, name) VALUES
    (1, 'MATH-01', 'Mathematics'),
    (1, 'ENG-01', 'English');
INSERT INTO class_subjects (section_id, subject_id) VALUES (1, 1), (1, 2);

INSERT INTO grade_scales (school_id, name, min_percent, max_percent, letter_grade, gpa_points) VALUES
    (1, 'Default 4.0', 90.00, 100.00, 'A', 4.00),
    (1, 'Default 4.0', 80.00, 89.99, 'B', 3.00),
    (1, 'Default 4.0', 70.00, 79.99, 'C', 2.00),
    (1, 'Default 4.0', 60.00, 69.99, 'D', 1.00),
    (1, 'Default 4.0', 0.00, 59.99, 'F', 0.00);

INSERT INTO fee_structures (school_id, branch_id, academic_year_id, name, amount, currency, frequency)
VALUES (1, 1, 1, 'Annual tuition', 1200.00, 'GHS', 'annual');

INSERT INTO students
    (school_id, branch_id, section_id, admission_number, first_name, last_name, date_of_birth, admission_date, status)
VALUES
    (1, 1, 1, 'DEMO-0001', 'Sample', 'Student', '2015-06-15', '2026-01-05', 'active');

INSERT INTO student_history (student_id, to_section_id, event_type, effective_date, notes)
VALUES (1, 1, 'admitted', '2026-01-05', 'Synthetic demonstration record; no account or real student data.');

INSERT INTO attendance (student_id, attendance_date, status, remarks)
VALUES (1, CURRENT_DATE - INTERVAL 1 DAY, 'present', 'Synthetic demonstration record.');

INSERT INTO exams (section_id, subject_id, term_id, name, exam_date, max_marks, weight_percent)
VALUES (1, 1, 1, 'Example mathematics assessment', '2026-03-15', 100.00, 100.00);

INSERT INTO grades (exam_id, student_id, marks_obtained, letter_grade)
VALUES (1, 1, 92.00, 'A');

INSERT INTO fee_invoices
    (student_id, invoice_number, issue_date, due_date, total_amount, paid_amount, currency, status)
VALUES
    (1, 'DEMO-INV-0001', '2026-11-01', '2026-11-30', 1200.00, 0.00, 'USD', 'issued');

INSERT INTO fee_invoice_items (invoice_id, fee_structure_id, description, quantity, unit_amount, line_total)
VALUES (1, 1, 'Example annual tuition', 1, 1200.00, 1200.00);
