<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

final class SchoolSettingsController
{
    public function edit(): void
    {
        $user = Auth::requirePermission('schools.manage');
        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $schoolQuery = $db->prepare(
            'SELECT id, name, code, timezone, currency, status
             FROM schools
             WHERE ' . ($isSuper ? '1 = 1' : 'id = :school_id') . '
             ORDER BY id
             LIMIT 2'
        );
        $params = $isSuper ? [] : ['school_id' => $user['school_id']];
        $schoolQuery->execute($params);

        $selectedId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $school = null;
        foreach ($schoolQuery->fetchAll() as $candidate) {
            if ($selectedId === false || $selectedId === null || (int) $candidate['id'] === (int) $selectedId) {
                $school = $candidate;
                break;
            }
        }
        if (!$school) {
            http_response_code(404);
            exit('School not found.');
        }

        View::render('school/settings', [
            'user' => $user,
            'csrf' => Csrf::token(),
            'school' => $school,
            'message' => $_SESSION['flash_success'] ?? null,
            'error' => $_SESSION['flash_error'] ?? null,
        ]);
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);
    }

    public function update(): void
    {
        $user = Auth::requirePermission('schools.manage');
        Csrf::validateRequest();

        $schoolId = filter_var($_POST['school_id'] ?? null, FILTER_VALIDATE_INT);
        $name = trim((string) ($_POST['name'] ?? ''));
        $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
        $timezone = trim((string) ($_POST['timezone'] ?? 'UTC'));
        $currency = strtoupper(trim((string) ($_POST['currency'] ?? 'USD')));

        if (
            !$schoolId ||
            $name === '' || mb_strlen($name) > 180 ||
            $code === '' || mb_strlen($code) > 40 ||
            mb_strlen($timezone) > 64 ||
            !preg_match('/\A[A-Z]{3}\z/', $currency)
        ) {
            $_SESSION['flash_error'] = 'Enter a valid school name, code, IANA timezone, and three-letter currency code.';
            header('Location: /school/settings', true, 303);
            exit;
        }

        $db = Database::connection();
        $isSuper = in_array('super_administrator', $user['roles'], true);
        $db->beginTransaction();
        try {
            $target = $db->prepare(
                'SELECT id, name, code FROM schools
                 WHERE id = :id AND ' . ($isSuper ? '1 = 1' : 'id = :school_id') . ' LIMIT 1'
            );
            $targetParams = ['id' => (int) $schoolId];
            if (!$isSuper) {
                $targetParams['school_id'] = $user['school_id'];
            }
            $target->execute($targetParams);
            $before = $target->fetch();
            if (!$before) {
                throw new \RuntimeException('The school is outside your authorized scope.');
            }

            $timezoneValid = false;
            foreach (timezone_identifiers_list() as $identifier) {
                if ($identifier === $timezone) {
                    $timezoneValid = true;
                    break;
                }
            }
            if (!$timezoneValid) {
                throw new \RuntimeException('Use a valid IANA timezone name.');
            }

            $duplicate = $db->prepare('SELECT id FROM schools WHERE code = :code AND id <> :id LIMIT 1');
            $duplicate->execute(['code' => $code, 'id' => (int) $schoolId]);
            if ($duplicate->fetchColumn()) {
                throw new \RuntimeException('That school code is already in use.');
            }

            $db->prepare(
                'UPDATE schools
                 SET name = :name, code = :code, timezone = :timezone, currency = :currency,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            )->execute([
                'name' => $name,
                'code' => $code,
                'timezone' => $timezone,
                'currency' => $currency,
                'id' => (int) $schoolId,
            ]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $_SESSION['flash_error'] = $exception instanceof \RuntimeException
                ? $exception->getMessage()
                : 'The school settings could not be updated.';
            header('Location: /school/settings?id=' . (int) $schoolId, true, 303);
            exit;
        }

        AuditLogger::record((int) $user['id'], 'schools.settings_updated', 'school', (int) $schoolId, [
            'before_name' => $before['name'],
            'name' => $name,
            'code' => $code,
            'timezone' => $timezone,
            'currency' => $currency,
        ]);
        $_SESSION['flash_success'] = 'School settings updated.';
        header('Location: /school/settings?id=' . (int) $schoolId, true, 303);
        exit;
    }
}
