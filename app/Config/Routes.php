<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── Public Routes ────────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->post('cek-email', 'Home::cekEmail');
$routes->get('cek-email', 'Home::index');
$routes->get('api/breach-trends', 'Home::breachTrends');
$routes->post('kirim-request', 'Home::kirimRequest');

$routes->get('upgrade', 'Home::upgrade');
$routes->post('upgrade/redeem', 'Home::redeemCode');
$routes->get('profile', 'Home::profile');
$routes->post('profile/password', 'Home::updateProfilePassword');
$routes->post('profile/send-otp', 'Home::sendProfileOtp');
$routes->post('profile/verify-otp', 'Home::verifyProfileOtp');
$routes->post('profile/history/delete/(:num)', 'Home::deleteHistoryItem/$1');
$routes->post('profile/history/clear', 'Home::clearHistory');
$routes->post('profile/monitor/add', 'Home::addMonitorEmail');
$routes->post('profile/monitor/(:num)/toggle', 'Home::toggleMonitorEmail/$1');
$routes->post('profile/monitor/(:num)/check', 'Home::checkMonitorEmailNow/$1');
$routes->post('profile/monitor/(:num)/delete', 'Home::deleteMonitorEmail/$1');
$routes->post('profile/monitor/test-alert', 'Home::sendMonitorTestAlert');
$routes->match(['get', 'post'], 'cron/breach-alerts', 'Home::cronBreachAlerts');
$routes->post('upgrade/proses', 'Home::prosesUpgrade');
$routes->get('upgrade/proses/(:segment)', 'Home::prosesUpgrade/$1');
$routes->post('upgrade/nota/(:num)/confirm', 'Home::confirmUpgrade/$1');
$routes->post('upgrade/nota/(:num)/cancel', 'Home::cancelUpgrade/$1');
$routes->get('upgrade/nota/(:num)/success', 'Home::successLokal/$1');
$routes->get('upgrade/nota/(:num)', 'Home::nota/$1');
$routes->post('midtrans/webhook', 'Home::midtransWebhook');
$routes->post('__shield-verify', static fn() => '');

// ─── Auth Routes ──────────────────────────────────────────────────────────────
$routes->get('/captcha/(:segment)', 'Auth::captcha/$1');
$routes->get('/login', 'Auth::login');
$routes->post('/auth/process', 'Auth::process');
$routes->post('/auth/changePassword', 'Auth::changePassword');
$routes->get('/logout', 'Auth::logout');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/saveRegister', 'Auth::saveRegister');
$routes->get('/verify-otp', 'Auth::verifyRegisterOtp');
$routes->post('/verify-otp', 'Auth::submitVerifyRegisterOtp');
$routes->post('/verify-otp/resend', 'Auth::resendRegisterOtp');
$routes->get('/forgot-password', 'Auth::forgotPassword');
$routes->post('/forgot-password/send', 'Auth::sendForgotOtp');
$routes->post('/forgot-password/reset', 'Auth::resetForgotPassword');

// ─── Admin Routes ─────────────────────────────────────────────────────────────
$routes->group('admin', function($routes) {
    $routes->get('/', 'AdminController::index');
    $routes->get('users', 'AdminController::users');
    $routes->post('users/delete/(:num)', 'AdminController::deleteUser/$1');
    $routes->post('users/cleanup-spam', 'AdminController::cleanupSpamUsers');
    $routes->post('update-subscription/(:num)', 'AdminController::updateSubscription/$1');
    $routes->get('sales', 'AdminController::sales');
    $routes->post('sales/verify/(:num)', 'AdminController::verifyTransaction/$1');
    $routes->get('export-csv', 'AdminController::exportCSV');
    // User Requests (Contact Us)
    $routes->get('requests', 'AdminController::requests');
    $routes->post('requests/approve/(:num)', 'AdminController::approveRequest/$1');
    $routes->post('requests/reject/(:num)', 'AdminController::rejectRequest/$1');
    // Redeem Codes
    $routes->get('redeem', 'AdminController::redeemCodes');
    $routes->post('redeem/create', 'AdminController::createRedeemCode');
    $routes->post('redeem/toggle/(:num)', 'AdminController::toggleRedeemCode/$1');
    $routes->post('redeem/delete/(:num)', 'AdminController::deleteRedeemCode/$1');
    // Security Audit Logs & Announcement
    $routes->get('security-logs', 'AdminController::securityLogs');
    $routes->post('security-logs/clear', 'AdminController::clearOldSecurityLogs');
    $routes->post('security-logs/unban-all', 'AdminController::unbanAllIps');
    $routes->post('announcement', 'AdminController::saveAnnouncement');
    $routes->post('under-attack', 'AdminController::toggleUnderAttack');
    $routes->get('otp-settings', 'AdminController::otpSettings');
    $routes->post('otp-settings/save', 'AdminController::saveOtpSettings');
    $routes->post('otp-settings/test', 'AdminController::testOtpEmail');
    $routes->get('auto-alerts', 'AdminController::autoAlerts');
    $routes->post('auto-alerts/settings', 'AdminController::saveAutoAlertSettings');
    $routes->post('auto-alerts/regen-token', 'AdminController::regenAutoAlertToken');
    $routes->post('auto-alerts/run-now', 'AdminController::runAutoAlertBatch');
    $routes->post('auto-alerts/test', 'AdminController::testAutoAlertEmail');
    $routes->post('auto-alerts/monitor/(:num)/check', 'AdminController::checkMonitorAdmin/$1');
    $routes->post('auto-alerts/monitor/(:num)/delete', 'AdminController::deleteMonitorAdmin/$1');
    $routes->get('notif-count', 'AdminController::getNotifCount');
});