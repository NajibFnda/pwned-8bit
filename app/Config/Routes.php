<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ─── Public Routes ────────────────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->post('cek-email', 'Home::cekEmail');
$routes->get('cek-email', 'Home::index');
$routes->post('kirim-request', 'Home::kirimRequest');

$routes->get('upgrade', 'Home::upgrade');
$routes->get('/upgrade/proses/(:segment)', 'Home::prosesUpgrade/$1');
$routes->get('upgrade/nota/(:num)', 'Home::nota/$1');

// ─── Auth Routes ──────────────────────────────────────────────────────────────
$routes->get('/login', 'Auth::login');
$routes->post('/auth/process', 'Auth::process');
$routes->get('/logout', 'Auth::logout');
$routes->get('/register', 'Auth::register');
$routes->post('/auth/saveRegister', 'Auth::saveRegister');

// ─── Admin Routes ─────────────────────────────────────────────────────────────
$routes->group('admin', function($routes) {
    $routes->get('/', 'AdminController::index');
    $routes->get('users', 'AdminController::users');
    $routes->post('update-subscription/(:num)', 'AdminController::updateSubscription/$1');
    $routes->post('approve-plan/(:num)', 'AdminController::approvePendingPlan/$1');
    $routes->get('sales', 'AdminController::sales');
    $routes->get('export-csv', 'AdminController::exportCSV');
    // User Requests (Contact Us)
    $routes->get('requests', 'AdminController::requests');
    $routes->post('requests/approve/(:num)', 'AdminController::approveRequest/$1');
    $routes->post('requests/reject/(:num)', 'AdminController::rejectRequest/$1');
    $routes->get('notif-count', 'AdminController::getNotifCount');
});