<?php
declare(strict_types=1);

use App\Controllers\AttendanceController;
use App\Controllers\AuthController;
use App\Controllers\FeeInvoiceController;
use App\Controllers\GuardianDashboardController;
use App\Controllers\PasswordResetController;
use App\Controllers\ProfileController;
use App\Controllers\StudentDashboardController;
use App\Controllers\TeacherController;
use App\Controllers\SchoolControlController;
use App\Controllers\AdmissionController;
use App\Controllers\UserCredentialsController;
use App\Controllers\StatusManagementController;
use App\Controllers\SchoolSettingsController;
use App\Controllers\ReportingController;
use App\Controllers\ITSupportDashboardController;
use App\Controllers\ClassFeeController;
use App\Controllers\ITRoleManagementController;
use App\Controllers\ClassManagementController;
use App\Controllers\ExamTimetableController;
use App\Controllers\TeacherWorkspaceController;
use App\Controllers\PublicSiteController;
use App\Core\Router;
use App\Core\Environment;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'TeacherController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'SchoolControlController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'AdmissionController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'UserCredentialsController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'StatusManagementController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'SchoolSettingsController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ReportingController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ITSupportDashboardController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ClassFeeController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ClassManagementController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ExamTimetableController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'TeacherWorkspaceController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'ITRoleManagementController.php';
require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR . 'PublicSiteController.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('sms_session');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$router = new Router();
$router->get('/', static fn () => AuthController::home());
$router->get('/about', static fn () => (new PublicSiteController())->about());
$router->get('/apply', static fn () => (new PublicSiteController())->admissions());
$router->post('/apply', static fn () => (new PublicSiteController())->submitApplication());
$router->get('/login', static fn () => AuthController::showLogin());
$router->post('/login', static fn () => AuthController::login());
$router->post('/logout', static fn () => AuthController::logout());
$router->get('/password/forgot', static fn () => (new PasswordResetController())->showRequest());
$router->post('/password/forgot', static fn () => (new PasswordResetController())->request());
$router->get('/password/reset', static fn () => (new PasswordResetController())->showReset());
$router->post('/password/reset', static fn () => (new PasswordResetController())->reset());
$router->get('/profile', static fn () => (new ProfileController())->edit());
$router->post('/profile', static fn () => (new ProfileController())->update());
$router->get('/users/credentials', static fn () => (new UserCredentialsController())->index());
$router->post('/users/credentials', static fn () => (new UserCredentialsController())->update());
$router->get('/status/manage', static fn () => (new StatusManagementController())->index());
$router->post('/status/manage', static fn () => (new StatusManagementController())->update());
$router->get('/school/settings', static fn () => (new SchoolSettingsController())->edit());
$router->post('/school/settings', static fn () => (new SchoolSettingsController())->update());
$router->get('/reports', static fn () => (new ReportingController())->index());
$router->get('/it-support', static fn () => (new ITSupportDashboardController())->index());
$router->get('/it-support/roles', static fn () => (new ITRoleManagementController())->index());
$router->post('/it-support/roles', static fn () => (new ITRoleManagementController())->update());
$router->get('/school/control', static fn () => (new SchoolControlController())->index());
$router->get('/admissions', static fn () => (new AdmissionController())->index());
$router->post('/admissions', static fn () => (new AdmissionController())->store());
$router->get('/teachers', static fn () => (new TeacherController())->index());
$router->post('/teachers', static fn () => (new TeacherController())->store());
$router->get('/dashboard', static fn () => AuthController::dashboard());
$router->get('/student/dashboard', static fn () => (new StudentDashboardController())->index());
$router->get('/guardian/dashboard', static fn () => (new GuardianDashboardController())->index());
$router->get('/attendance', static fn () => (new AttendanceController())->index());
$router->post('/attendance', static fn () => (new AttendanceController())->store());
$router->get('/fees/invoices', static fn () => (new FeeInvoiceController())->index());
$router->post('/fees/invoices', static fn () => (new FeeInvoiceController())->create());
$router->get('/fees/invoices/download', static fn () => (new FeeInvoiceController())->download());
$router->get('/fees/class-fees', static fn () => (new ClassFeeController())->index());
$router->post('/fees/class-fees', static fn () => (new ClassFeeController())->store());
$router->get('/academics/classes', static fn () => (new ClassManagementController())->index());
$router->post('/academics/classes', static fn () => (new ClassManagementController())->store());
$router->get('/exams/timetable', static fn () => (new ExamTimetableController())->index());
$router->post('/exams/timetable', static fn () => (new ExamTimetableController())->store());
$router->get('/teacher/workspace', static fn () => (new TeacherWorkspaceController())->index());
$router->post('/teacher/marks', static fn () => (new TeacherWorkspaceController())->saveMarks());
$router->post('/teacher/assignments', static fn () => (new TeacherWorkspaceController())->createAssignment());

try {
    $router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    if (Environment::get('APP_DEBUG', 'false') === 'true') {
        echo '<pre>' . e($exception->getMessage()) . '</pre>';
    } else {
        echo 'An unexpected error occurred. Please try again later.';
    }
}
