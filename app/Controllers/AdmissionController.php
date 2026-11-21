<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\PasswordHasher;
use App\Core\View;
use DateTimeImmutable;

final class AdmissionController
{
    public function index(): void
    {
        $user = Auth::requirePermission('students.manage');
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $params = $isSuper ? [] : ['school_id' => $user['school_id']];

        $sections = $db->prepare(
            'SELECT sec.id, sec.name AS section_name, c.name AS class_name
             FROM sections sec
             INNER JOIN classes c ON c.id = sec.class_id
             WHERE c.status = "active" AND sec.status = "active"
               AND ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
             ORDER BY c.grade_level, c.name, sec.name'
        );
        $sections->execute($params);

        $students = $db->prepare(
            'SELECT s.id, s.admission_number, s.first_name, s.last_name, s.date_of_birth,
                    s.gender, s.admission_date, s.status, s.address,
                    c.name AS class_name, sec.name AS section_name,
                    u.email AS student_email
             FROM students s
             LEFT JOIN sections sec ON sec.id = s.section_id
             LEFT JOIN classes c ON c.id = sec.class_id
             LEFT JOIN users u ON u.id = s.user_id
             WHERE ' . ($isSuper ? '1 = 1' : 's.school_id = :school_id') . '
             ORDER BY s.created_at DESC
             LIMIT 100'
        );
        $students->execute($params);

        View::render('admissions/index', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'sections' => $sections->fetchAll(),
            'students' => $students->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function store(): void
    {
        $user = Auth::requirePermission('students.manage');
        Csrf::validateRequest();

        $admissionNumber = trim((string) ($_POST['admission_number'] ?? ''));
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $dob = trim((string) ($_POST['date_of_birth'] ?? ''));
        $gender = trim((string) ($_POST['gender'] ?? 'undisclosed'));
        $admissionDate = trim((string) ($_POST['admission_date'] ?? date('Y-m-d')));
        $sectionId = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
        $address = trim((string) ($_POST['address'] ?? ''));
        $studentEmail = strtolower(trim((string) ($_POST['student_email'] ?? '')));
        $studentPassword = (string) ($_POST['student_password'] ?? '');

        if (
            mb_strlen($admissionNumber) > 60 ||
            $firstName === '' || mb_strlen($firstName) > 100 ||
            $lastName === '' || mb_strlen($lastName) > 100 ||
            mb_strlen($address) > 500 ||
            $sectionId === false || $sectionId === null ||
            !in_array($gender, ['female', 'male', 'non_binary', 'undisclosed'], true) ||
            !$this->validDate($dob, true) ||
            !$this->validDate($admissionDate)
        ) {
            $_SESSION['flash_error'] = 'Use valid student details, date of birth, admission date, gender, and section.';
            header('Location: /admissions', true, 303);
            exit;
        }

        if ($studentEmail !== '' && (
            !filter_var($studentEmail, FILTER_VALIDATE_EMAIL) ||
            mb_strlen($studentEmail) > 254 ||
            mb_strlen($studentPassword) < 14 ||
            mb_strlen($studentPassword) > 4096
        )) {
            $_SESSION['flash_error'] = 'For a student login, use a valid email and a password of at least 14 characters.';
            header('Location: /admissions', true, 303);
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $isSuper = in_array('super_administrator', $user['roles'], true);
            $section = $db->prepare(
                'SELECT sec.id, sec.capacity, c.school_id, c.branch_id
                 FROM sections sec
                 INNER JOIN classes c ON c.id = sec.class_id
                 WHERE sec.id = :section_id
                   AND sec.status = "active" AND c.status = "active"
                   AND ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
                 LIMIT 1'
            );
            $sectionParams = ['section_id' => (int) $sectionId];
            if (!$isSuper) {
                $sectionParams['school_id'] = $user['school_id'];
            }
            $section->execute($sectionParams);
            $sectionRow = $section->fetch();
            if (!$sectionRow) {
                throw new \RuntimeException('The selected class/section is not available in your school scope.');
            }

            if ($sectionRow['capacity'] !== null) {
                $count = $db->prepare('SELECT COUNT(*) FROM students WHERE section_id = :section_id AND status = "active"');
                $count->execute(['section_id' => (int) $sectionId]);
                if ((int) $count->fetchColumn() >= (int) $sectionRow['capacity']) {
                    throw new \RuntimeException('The selected section is full.');
                }
            }

            $studentUserId = null;
            if ($studentEmail !== '') {
                $existing = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
                $existing->execute(['email' => $studentEmail]);
                if ($existing->fetchColumn()) {
                    throw new \RuntimeException('A user account already uses that student email address.');
                }

                $studentUser = $db->prepare(
                    'INSERT INTO users
                        (school_id, branch_id, first_name, last_name, email, password_hash, status, auth_version, created_at)
                     VALUES (:school_id, :branch_id, :first_name, :last_name, :email, :password_hash, "active", 1, CURRENT_TIMESTAMP)'
                );
                $studentUser->execute([
                    'school_id' => $sectionRow['school_id'],
                    'branch_id' => $sectionRow['branch_id'],
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $studentEmail,
                    'password_hash' => PasswordHasher::hash($studentPassword),
                ]);
                $studentUserId = (int) $db->lastInsertId();

                $studentRole = $db->prepare(
                    'INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, created_at)
                     SELECT :user_id, r.id, :assigned_by, NULL, CURRENT_TIMESTAMP
                     FROM roles r WHERE r.name = "student" LIMIT 1'
                );
                $studentRole->execute(['user_id' => $studentUserId, 'assigned_by' => $user['id']]);
            }

            if ($admissionNumber === '') {
                $admissionNumber = 'ADM-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
            }

            $existingAdmission = $db->prepare(
                'SELECT id FROM students WHERE school_id = :school_id AND admission_number = :admission_number LIMIT 1'
            );
            $existingAdmission->execute([
                'school_id' => $sectionRow['school_id'],
                'admission_number' => $admissionNumber,
            ]);
            if ($existingAdmission->fetchColumn()) {
                throw new \RuntimeException('That admission number is already in use.');
            }

            $insert = $db->prepare(
                'INSERT INTO students
                    (school_id, branch_id, user_id, section_id, admission_number, first_name, last_name,
                     date_of_birth, gender, admission_date, status, address, created_at)
                 VALUES (:school_id, :branch_id, :user_id, :section_id, :admission_number, :first_name, :last_name,
                     :date_of_birth, :gender, :admission_date, "active", :address, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'school_id' => $sectionRow['school_id'],
                'branch_id' => $sectionRow['branch_id'],
                'user_id' => $studentUserId,
                'section_id' => (int) $sectionId,
                'admission_number' => $admissionNumber,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dob !== '' ? $dob : null,
                'gender' => $gender,
                'admission_date' => $admissionDate,
                'address' => $address !== '' ? $address : null,
            ]);
            $studentId = (int) $db->lastInsertId();

            $history = $db->prepare(
                'INSERT INTO student_history
                    (student_id, from_section_id, to_section_id, event_type, effective_date, notes, recorded_by, created_at)
                 VALUES (:student_id, NULL, :section_id, "admitted", :effective_date, :notes, :recorded_by, CURRENT_TIMESTAMP)'
            );
            $history->execute([
                'student_id' => $studentId,
                'section_id' => (int) $sectionId,
                'effective_date' => $admissionDate,
                'notes' => $studentUserId ? 'Admitted with a student portal account.' : 'Admitted without a student portal account.',
                'recorded_by' => $user['id'],
            ]);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The admission could not be completed.';
            header('Location: /admissions', true, 303);
            exit;
        }

        AuditLogger::record((int) $user['id'], 'students.admitted', 'student', $studentId, [
            'admission_number' => $admissionNumber,
            'section_id' => (int) $sectionId,
        ]);
        $_SESSION['flash_success'] = 'Admission completed. Admission number: ' . $admissionNumber;
        header('Location: /admissions', true, 303);
        exit;
    }

    private function validDate(string $value, bool $allowEmpty = false): bool
    {
        if ($value === '' && $allowEmpty) {
            return true;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
