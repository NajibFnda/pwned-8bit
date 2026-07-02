<?php
// Script diagnostik — jalankan: php test_api.php

// Test 1: cURL dasar ke XposedOrNot API
echo "=== DIAGNOSTIC: XposedOrNot API ===" . PHP_EOL;
echo "PHP Version: " . PHP_VERSION . PHP_EOL;
echo "cURL enabled: " . (extension_loaded('curl') ? 'YES' : 'NO') . PHP_EOL;

if (!extension_loaded('curl')) {
    echo "ERROR: cURL extension tidak aktif di PHP!" . PHP_EOL;
    exit(1);
}

$curlInfo = curl_version();
echo "cURL version: " . $curlInfo['version'] . PHP_EOL;
echo "SSL version:  " . $curlInfo['ssl_version'] . PHP_EOL;
echo PHP_EOL;

// Test 2: Koneksi ke API
echo "=== TEST KONEKSI ===" . PHP_EOL;
$url = 'https://api.xposedornot.com/v1/breach-analytics?email=test@example.com';
echo "Target URL: " . $url . PHP_EOL;

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,  // Disable SSL verify untuk test
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_USERAGENT      => 'PWNED-Test/1.0',
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
    ],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
$errno    = curl_errno($ch);
curl_close($ch);

echo "HTTP Status Code: " . $httpCode . PHP_EOL;
echo "cURL Error (#" . $errno . "): " . ($error ?: 'none') . PHP_EOL;
echo "Response (200 chars): " . substr($response, 0, 200) . PHP_EOL;
echo PHP_EOL;

// Test 3: Coba dengan SSL verify AKTIF
echo "=== TEST SSL VERIFY ON ===" . PHP_EOL;
$ch2 = curl_init($url);
curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_USERAGENT      => 'PWNED-Test/1.0',
]);
$response2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
$error2    = curl_error($ch2);
$errno2    = curl_errno($ch2);
curl_close($ch2);

echo "HTTP Status Code: " . $httpCode2 . PHP_EOL;
echo "cURL Error (#" . $errno2 . "): " . ($error2 ?: 'none') . PHP_EOL;
echo PHP_EOL;

// Test 4: CI4 Services check
echo "=== TEST CI4 SERVICES ===" . PHP_EOL;
define('FCPATH', __DIR__ . '/public/');
chdir(__DIR__);
require_once 'vendor/autoload.php';

$app = new \CodeIgniter\Boot();
\CodeIgniter\Boot::bootWeb();

try {
    $client = \Config\Services::curlrequest([
        'baseURI' => 'https://api.xposedornot.com',
        'timeout' => 10,
        'verify'  => false,
        'headers' => ['Accept' => 'application/json'],
    ]);
    $resp = $client->get('/v1/breach-analytics?email=test@example.com');
    echo "CI4 CURLRequest OK! HTTP: " . $resp->getStatusCode() . PHP_EOL;
    echo "Response (150 chars): " . substr($resp->getBody(), 0, 150) . PHP_EOL;
} catch (\Throwable $e) {
    echo "CI4 CURLRequest ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Class: " . get_class($e) . PHP_EOL;
}
