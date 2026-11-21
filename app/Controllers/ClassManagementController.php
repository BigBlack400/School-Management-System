<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class ClassManagementController
{
    public function index(): void
    {
        $user = Auth::requirePermission('academics.manage');
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);

        $years = $db->prepare(
            'SELECT id, name, starts_on, ends_on, is_current
             FROM academic_years
             WHERE ' . ($isSuper ? '1 = 1' : 'school_id = :school_id') . '
             ORDER BY is_current DESC, starts_on DESC'
        );
        $yearParams = $isSuper ? [] : ['school_id' => $user['school_id']];
        $years->execute($yearParams);

        $classes = $db->prepare(
            'SELECT c.id, c.name, c.grade_level, c.status, c.branch_id,
                    ay.id AS academic_year_id, ay.name AS academic_year,
                    COUNT(sec.id) AS section_count
             FROM classes c
             INNER JOIN academic_years ay ON ay.id = c.academic_year_id
             LEFT JOIN sections sec ON sec.class_id = c.id
             WHERE ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
             GROUP BY c.id, c.name, c.grade_level, c.status, c.branch_id,
                      ay.id, ay.name
             ORDER BY c.grade_level, c.name'
        );
        $classParams = $isSuper ? [] : ['school_id' => $user['school_id']];
        $classes->execute($classParams);

        $sections = $db->prepare(
            'SELECT sec.id, sec.name, sec.capacity, sec.status,
                    c.id AS class_id, c.name AS class_name, c.grade_level
             FROM sections sec
             INNER JOIN classes c ON c.id = sec.class_id
             WHERE ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
             ORDER BY c.grade_level, c.name, sec.name'
        );
        $sectionParams = $isSuper ? [] : ['school_id' => $user['school_id']];
        $sections->execute($sectionParams);

        View::render('academics/classes', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'academicYears' => $years->fetchAll(),
            'classes' => $classes->fetchAll(),
            'sections' => $sections->fetchAll(),
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function store(): void
    {
        $user = Auth::requirePermission('academics.manage');
        Csrf::validateRequest();

        $type = (string) ($_POST['type'] ?? '');
        $name = trim((string) ($_POST['name'] ?? ''));
        $gradeLevel = filter_var($_POST['grade_level'] ?? null, FILTER_VALIDATE_INT);
        $academicYearId = filter_var($_POST['academic_year_id'] ?? null, FILTER_VALIDATE_INT);
        $classId = filter_var($_POST['class_id'] ?? null, FILTER_VALIDATE_INT);
        $capacity = filter_var($_POST['capacity'] ?? null, FILTER_VALIDATE_INT);
        $status = (string) ($_POST['status'] ?? 'active');

        if ($name === '' || mb_strlen($name) > 120 ||
            !in_array($status, ['active', 'inactive'], true)) {
            $this->fail('Enter a valid name and status.');
        }

        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $db->beginTransaction();
        try {
            if ($type === 'class') {
                if (!$academicYearId ||
                    $gradeLevel === false ||
                    $gradeLevel !== null && ($gradeLevel < 0 || $gradeLevel > 65535)) {
                    throw new \RuntimeException('Select an academic year and enter a valid class level.');
                }

                $year = $db->prepare(
                    'SELECT id, school_id FROM academic_years
                     WHERE id = :id
                       AND ' . ($isSuper ? '1 = 1' : 'school_id = :school_id') . '
                     LIMIT 1'
                );
                $yearParams = ['id' => (int) $academicYearId];
                if (!$isSuper) {
                    $yearParams['school_id'] = $user['school_id'];
                }
                $year->execute($yearParams);
                $yearRow = $year->fetch();
                if (!$yearRow) {
                    throw new \RuntimeException('The academic year is outside your school scope.');
                }

                $duplicate = $db->prepare(
                    'SELECT id FROM classes
                     WHERE school_id = :school_id AND academic_year_id = :academic_year_id AND name = :name
                     LIMIT 1'
                );
                $duplicate->execute([
                    'school_id' => $yearRow['school_id'],
                    'academic_year_id' => (int) $academicYearId,
                    'name' => $name,
                ]);
                if ($duplicate->fetchColumn()) {
                    throw new \RuntimeException('A class with that name already exists for this academic year.');
                }

                $insert = $db->prepare(
                    'INSERT INTO classes
                        (school_id, branch_id, academic_year_id, name, grade_level, status, created_at)
                     VALUES (:school_id, :branch_id, :academic_year_id, :name, :grade_level, :status, CURRENT_TIMESTAMP)'
                );
                $insert->execute([
                    'school_id' => $yearRow['school_id'],
                    'branch_id' => $user['branch_id'],
                    'academic_year_id' => (int) $academicYearId,
                    'name' => $name,
                    'grade_level' => $gradeLevel === false || $gradeLevel === null ? null : (int) $gradeLevel,
                    'status' => $status,
                ]);
                $recordId = (int) $db->lastInsertId();
                $action = 'academics.class_created';
            } elseif ($type === 'section') {
                if (!$classId ||
                    $capacity === false ||
                    $capacity !== null && ($capacity < 1 || $capacity > 65535)) {
                    throw new \RuntimeException('Select a class and enter a valid capacity.');
                }

                $classQuery = $db->prepare(
                    'SELECT c.id FROM classes c
                     WHERE c.id = :id
                       AND ' . ($isSuper ? '1 = 1' : 'c.school_id = :school_id') . '
                     LIMIT 1'
                );
                $classParams = ['id' => (int) $classId];
                if (!$isSuper) {
                    $classParams['school_id'] = $user['school_id'];
                }
                $classQuery->execute($classParams);
                if (!$classQuery->fetchColumn()) {
                    throw new \RuntimeException('The selected class is outside your school scope.');
                }

                $duplicate = $db->prepare(
                    'SELECT id FROM sections WHERE class_id = :class_id AND name = :name LIMIT 1'
                );
                $duplicate->execute(['class_id' => (int) $classId, 'name' => $name]);
                if ($duplicate->fetchColumn()) {
                    throw new \RuntimeException('A section with that name already exists for this class.');
                }

                $insert = $db->prepare(
                    'INSERT INTO sections (class_id, name, capacity, status)
                     VALUES (:class_id, :name, :capacity, :status)'
                );
                $insert->execute([
                    'class_id' => (int) $classId,
                    'name' => $name,
                    'capacity' => $capacity === false || $capacity === null ? null : (int) $capacity,
                    'status' => $status,
                ]);
                $recordId = (int) $db->lastInsertId();
                $action = 'academics.section_created';
            } else {
                throw new \RuntimeException('Select class or section creation.');
            }
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->fail($exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The class or section could not be created.');
        }

        AuditLogger::record((int) $user['id'], $action, $type === 'class' ? 'class' : 'section', $recordId, [
            'name' => $name,
            'status' => $status,
        ]);
        $_SESSION['flash_success'] = $type === 'class'
            ? 'Class created successfully.'
            : 'Section created successfully.';
        header('Location: /academics/classes', true, 303);
        exit;
    }

    private function fail(string $message): never
    {
        $_SESSION['flash_error'] = $message;
        header('Location: /academics/classes', true, 303);
        exit;
    }
}
