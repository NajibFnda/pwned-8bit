<?php
$ch = curl_init('https://api.xposedornot.com/v1/breach-analytics?email=admin@gmail.com');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$r = curl_exec($ch);
echo json_encode(json_decode($r), JSON_PRETTY_PRINT);
