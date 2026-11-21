<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\PasswordHasher;
use App\Core\View;

final class TeacherController
{
    public function index(): void
    {
        $user = Auth::requirePermission('users.manage');
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);

        $query = $db->prepare(
            'SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.status,
                    s.name AS school_name, b.name AS branch_name,
                    GROUP_CONCAT(DISTINCT r.label ORDER BY r.label SEPARATOR ", ") AS roles,
                    GROUP_CONCAT(DISTINCT CONCAT(c.name, " / ", sec.name, " / ", sub.name)
                        ORDER BY c.name, sec.name, sub.name SEPARATOR ", ") AS allocations
             FROM users u
             LEFT JOIN schools s ON s.id = u.school_id
             LEFT JOIN branches b ON b.id = u.branch_id
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             LEFT JOIN teacher_allocations ta ON ta.teacher_id = u.id AND ta.status = "active"
             LEFT JOIN sections sec ON sec.id = ta.section_id
             LEFT JOIN classes c ON c.id = sec.class_id
             LEFT JOIN subjects sub ON sub.id = ta.subject_id
             WHERE r.name IN ("teacher", "administrative_staff", "it_support")
             AND (:is_super = 1 OR u.school_id = :school_id)
             AND (:branch_unrestricted = 1 OR u.branch_id = :branch_id)
             GROUP BY u.id, u.first_name, u.last_name, u.email, u.phone, u.status, s.name, b.name
             ORDER BY u.last_name, u.first_name'
        );
        $query->execute([
            'is_super' => $isSuper ? 1 : 0,
            'school_id' => $user['school_id'],
            'branch_unrestricted' => $user['branch_id'] === null ? 1 : 0,
            'branch_id' => $user['branch_id'],
        ]);

        $sections = $db->prepare(
            'SELECT sec.id, sec.name AS section_name, c.name AS class_name
             FROM sections sec
             INNER JOIN classes c ON c.id = sec.class_id
             WHERE sec.status = "active" AND c.status = "active"
               AND (:is_super = 1 OR c.school_id = :school_id)
             ORDER BY c.grade_level, c.name, sec.name'
        );
        $sections->execute([
            'is_super' => $isSuper ? 1 : 0,
            'school_id' => $user['school_id'],
        ]);

        $subjects = $db->prepare(
            'SELECT id, code, name
             FROM subjects
             WHERE status = "active"
               AND (:is_super = 1 OR school_id = :school_id)
             ORDER BY name'
        );
        $subjects->execute([
            'is_super' => $isSuper ? 1 : 0,
            'school_id' => $user['school_id'],
        ]);

        View::render('teachers/index', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'teachers' => $query->fetchAll(),
            'sections' => $sections->fetchAll(),
            'subjects' => $subjects->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function store(): void
    {
        $user = Auth::requirePermission('users.manage');
        Csrf::validateRequest();

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $roleName = (string) ($_POST['role'] ?? 'teacher');
        $sectionId = filter_var($_POST['section_id'] ?? null, FILTER_VALIDATE_INT);
        $subjectId = filter_var($_POST['subject_id'] ?? null, FILTER_VALIDATE_INT);

        $allowedRoles = ['teacher', 'administrative_staff', 'it_support'];
        if (!in_array($roleName, $allowedRoles, true)) {
            $_SESSION['flash_error'] = 'Select a valid staff role.';
            header('Location: /teachers', true, 303);
            exit;
        }
        if ($roleName === 'teacher' && (!$sectionId || !$subjectId)) {
            $_SESSION['flash_error'] = 'Teachers require a class/section and subject/course.';
            header('Location: /teachers', true, 303);
            exit;
        }

        if (
            $firstName === '' || mb_strlen($firstName) > 100 ||
            $lastName === '' || mb_strlen($lastName) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254 ||
            mb_strlen($phone) > 32 ||
            mb_strlen($password) < 14 || mb_strlen($password) > 4096
        ) {
            $_SESSION['flash_error'] = 'Use valid names, email and a password of at least 14 characters.';
            header('Location: /teachers', true, 303);
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $existing = $db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existing->execute(['email' => $email]);
            $existingId = $existing->fetchColumn();
            if ($existingId) {
                throw new \RuntimeException('An account already uses that email address.');
            }

            $insert = $db->prepare(
                'INSERT INTO users
                    (school_id, branch_id, first_name, last_name, email, phone, password_hash, status, auth_version, created_at)
                 VALUES (:school_id, :branch_id, :first_name, :last_name, :email, :phone, :password_hash, "active", 1, CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'school_id' => $user['school_id'],
                'branch_id' => $user['branch_id'],
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'password_hash' => PasswordHasher::hash($password),
            ]);
            $teacherId = (int) $db->lastInsertId();

            $assign = $db->prepare(
                'INSERT INTO user_roles (user_id, role_id, assigned_by, expires_at, created_at)
                 SELECT :teacher_id, r.id, :assigned_by, NULL, CURRENT_TIMESTAMP
                 FROM roles r WHERE r.name = :role_name LIMIT 1'
            );
            $assign->execute([
                'teacher_id' => $teacherId,
                'assigned_by' => $user['id'],
                'role_name' => $roleName,
            ]);

            if ($roleName === 'teacher') {
                $isSuper = in_array('super_administrator', $user['roles'], true);
                $section = $db->prepare(
                    'SELECT sec.id, c.school_id
                     FROM sections sec
                     INNER JOIN classes c ON c.id = sec.class_id
                     WHERE sec.id = :section_id AND sec.status = "active"
                       AND (:is_super = 1 OR c.school_id = :school_id)
                     LIMIT 1'
                );
                $section->execute([
                    'section_id' => (int) $sectionId,
                    'is_super' => $isSuper ? 1 : 0,
                    'school_id' => $user['school_id'],
                ]);
                $sectionRow = $section->fetch();

                $subject = $db->prepare(
                    'SELECT id FROM subjects
                     WHERE id = :subject_id AND status = "active"
                       AND (:is_super = 1 OR school_id = :school_id)
                     LIMIT 1'
                );
                $subject->execute([
                    'subject_id' => (int) $subjectId,
                    'is_super' => $isSuper ? 1 : 0,
                    'school_id' => $user['school_id'],
                ]);
                if (!$sectionRow || !$subject->fetchColumn()) {
                    throw new \RuntimeException('The selected section or subject is outside your school scope.');
                }

                $academicYear = $db->prepare(
                    'SELECT id FROM academic_years
                     WHERE school_id = :school_id
                     ORDER BY is_current DESC, starts_on DESC
                     LIMIT 1'
                );
                $academicYear->execute(['school_id' => $sectionRow['school_id']]);
                $academicYearId = $academicYear->fetchColumn();
                if (!$academicYearId) {
                    throw new \RuntimeException('No academic year is configured for this school.');
                }

                $allocation = $db->prepare(
                    'INSERT INTO teacher_allocations
                        (teacher_id, section_id, subject_id, academic_year_id, status, created_at)
                     VALUES (:teacher_id, :section_id, :subject_id, :academic_year_id, "active", CURRENT_TIMESTAMP)'
                );
                $allocation->execute([
                    'teacher_id' => $teacherId,
                    'section_id' => (int) $sectionId,
                    'subject_id' => (int) $subjectId,
                    'academic_year_id' => (int) $academicYearId,
                ]);
            }

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The teacher account could not be created.';
            header('Location: /teachers', true, 303);
            exit;
        }

        AuditLogger::record((int) $user['id'], 'users.staff_created', 'user', $teacherId, [
            'email' => $email,
            'role' => $roleName,
            'section_id' => $roleName === 'teacher' ? (int) $sectionId : null,
            'subject_id' => $roleName === 'teacher' ? (int) $subjectId : null,
        ]);
        $_SESSION['flash_success'] = $roleName === 'teacher'
            ? 'Teacher account and subject allocation created.'
            : 'Staff account created.';
        header('Location: /teachers', true, 303);
        exit;
    }
}
