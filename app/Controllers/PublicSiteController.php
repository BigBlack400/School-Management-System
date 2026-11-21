<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class PublicSiteController
{
    public function home(): void
    {
        $school = $this->school();
        $db = Database::connection();
        $stats = [
            'students' => (int) $db->query("SELECT COUNT(*) FROM students WHERE status = 'active'")->fetchColumn(),
            'teachers' => (int) $db->query("SELECT COUNT(DISTINCT u.id)
                FROM users u INNER JOIN user_roles ur ON ur.user_id=u.id
                INNER JOIN roles r ON r.id=ur.role_id
                WHERE r.name='teacher' AND u.status='active'")->fetchColumn(),
            'classes' => (int) $db->query("SELECT COUNT(*) FROM classes WHERE status='active'")->fetchColumn(),
        ];
        View::render('public/home', [
            'school' => $school,
            'stats' => $stats,
            'title' => ($school['name'] ?? 'School') . ' | Welcome',
        ]);
    }

    public function about(): void
    {
        View::render('public/about', [
            'school' => $this->school(),
            'title' => 'About us',
        ]);
    }

    public function admissions(): void
    {
        $db = Database::connection();
        $classes = $db->query(
            'SELECT c.id, c.name, c.grade_level
             FROM classes c WHERE c.status="active"
             ORDER BY c.grade_level, c.name'
        )->fetchAll();
        View::render('public/admissions', [
            'school' => $this->school(),
            'classes' => $classes,
            'csrf' => Csrf::token(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
            'title' => 'Student application',
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function submitApplication(): void
    {
        Csrf::validateRequest();
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
        $dateOfBirth = trim((string) ($_POST['date_of_birth'] ?? ''));
        $guardianName = trim((string) ($_POST['guardian_name'] ?? ''));
        $guardianPhone = trim((string) ($_POST['guardian_phone'] ?? ''));
        $address = trim((string) ($_POST['address'] ?? ''));
        $notes = trim((string) ($_POST['notes'] ?? ''));

        if (
            $firstName === '' || mb_strlen($firstName) > 100 ||
            $lastName === '' || mb_strlen($lastName) > 100 ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254 ||
            mb_strlen($phone) > 32 || mb_strlen($guardianName) > 120 ||
            mb_strlen($guardianPhone) > 32 || mb_strlen($address) > 500 ||
            mb_strlen($notes) > 2000 || !$classId
        ) {
            $_SESSION['flash_error'] = 'Please complete the application with valid contact and student details.';
            header('Location: /apply', true, 303);
            exit;
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $school = $this->school();
            $class = $db->prepare('SELECT id FROM classes WHERE id = :id AND school_id = :school_id AND status = "active" LIMIT 1');
            $class->execute(['id' => (int) $classId, 'school_id' => $school['id']]);
            if (!$class->fetchColumn()) {
                throw new \RuntimeException('Please select an active class.');
            }

            $insert = $db->prepare(
                'INSERT INTO student_applications
                    (school_id, class_id, first_name, last_name, date_of_birth, email, phone,
                     guardian_name, guardian_phone, address, notes, status, created_at)
                 VALUES (:school_id, :class_id, :first_name, :last_name, :date_of_birth, :email, :phone,
                         :guardian_name, :guardian_phone, :address, :notes, "submitted", CURRENT_TIMESTAMP)'
            );
            $insert->execute([
                'school_id' => $school['id'],
                'class_id' => (int) $classId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'date_of_birth' => $dateOfBirth !== '' ? $dateOfBirth : null,
                'email' => $email,
                'phone' => $phone !== '' ? $phone : null,
                'guardian_name' => $guardianName !== '' ? $guardianName : null,
                'guardian_phone' => $guardianPhone !== '' ? $guardianPhone : null,
                'address' => $address !== '' ? $address : null,
                'notes' => $notes !== '' ? $notes : null,
            ]);
            $applicationId = (int) $db->lastInsertId();
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'Your application could not be submitted.';
            header('Location: /apply', true, 303);
            exit;
        }

        AuditLogger::record(null, 'admissions.application_submitted', 'student_application', $applicationId);
        $_SESSION['flash_success'] = 'Application submitted successfully. The admissions office will contact you.';
        header('Location: /apply', true, 303);
        exit;
    }

    /** @return array<string, mixed> */
    private function school(): array
    {
        $statement = Database::connection()->query(
            'SELECT id, name, code, timezone, currency, status FROM schools ORDER BY id LIMIT 1'
        );
        return $statement->fetch() ?: [
            'id' => 1,
            'name' => 'School',
            'code' => 'SCHOOL',
            'timezone' => 'Africa/Accra',
            'currency' => 'GHS',
        ];
    }
}
