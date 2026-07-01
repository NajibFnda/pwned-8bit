<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
// Rute Halaman Utama (Cek Email & Statistik)
$routes->get('/', 'Home::index');
$routes->post('cek-email', 'Home::cekEmail');

// Rute Baru khusus Halaman Upgrade Pricing
$routes->get('upgrade', 'Home::upgrade');

// Mengelompokkan route khusus admin
$routes->group('admin', function($routes) {
    $routes->get('/', 'AdminController::index');
    $routes->get('users', 'AdminController::users');
    $routes->post('update-subscription/(:num)', 'AdminController::updateSubscription/$1');
    $routes->get('sales', 'AdminController::sales');
});

// Route untuk halaman Login
$routes->get('/login', 'Auth::login');

$routes->post('/auth/process', 'Auth::process'); // Menangkap data dari form login
$routes->get('/logout', 'Auth::logout'); // Untuk fitur keluar

$routes->get('/register', 'Auth::register');
$routes->post('/auth/saveRegister', 'Auth::saveRegister');

// Rute untuk memproses upgrade paket
$routes->get('/upgrade/proses/(:segment)', 'Home::prosesUpgrade/$1');

$routes->get('upgrade/nota/(:num)', 'Home::nota/$1');