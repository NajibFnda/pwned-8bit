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
$routes->post('profile/history/delete/(:num)', 'Home::deleteHistoryItem/$1');
$routes->post('profile/history/clear', 'Home::clearHistory');
$routes->post('upgrade/proses', 'Home::prosesUpgrade');
$routes->get('upgrade/proses/(:segment)', 'Home::prosesUpgrade/$1');
$routes->post('upgrade/nota/(:num)/confirm', 'Home::confirmUpgrade/$1');
$routes->post('upgrade/nota/(:num)/cancel', 'Home::cancelUpgrade/$1');
$routes->get('upgrade/nota/(:num)/success', 'Home::successLokal/$1');
$routes->get('upgrade/nota/(:num)', 'Home::nota/$1');
$routes->post('midtrans/webhook', 'Home::midtransWebhook');

// ─── Auth Routes ──────────────────────────────────────────────────────────────
$routes->get('/captcha/(:segment)', 'Auth::captcha/$1');
$routes->get('/login', 'Auth::login');
$routes->post('/auth/process', 'Auth::process');
$routes->post('/auth/changePassword', 'Auth::changePassword');
$routes->get('/logout', 'Auth::logout');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/saveRegister', 'Auth::saveRegister');

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
    $routes->post('announcement', 'AdminController::saveAnnouncement');
    $routes->get('notif-count', 'AdminController::getNotifCount');
});