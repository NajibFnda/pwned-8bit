<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('cek-email', 'Home::cekEmail');
$routes->get('/', 'Home::index');
$routes->post('cek-email', 'Home::cekEmail');
$routes->get('cek-email', 'Home::index'); 


$routes->get('upgrade', 'Home::upgrade');

$routes->get('cek-email', 'Home::index'); 


$routes->group('admin', function($routes) {
    $routes->get('/', 'AdminController::index');
    $routes->get('users', 'AdminController::users');
    $routes->post('update-subscription/(:num)', 'AdminController::updateSubscription/$1');
    $routes->get('sales', 'AdminController::sales');
});


$routes->get('/login', 'Auth::login');

$routes->post('/auth/process', 'Auth::process'); 
$routes->get('/logout', 'Auth::logout'); 

$routes->get('/register', 'Auth::register');
$routes->post('/auth/saveRegister', 'Auth::saveRegister');

$routes->get('/upgrade/proses/(:segment)', 'Home::prosesUpgrade/$1');

$routes->get('upgrade/nota/(:num)', 'Home::nota/$1');