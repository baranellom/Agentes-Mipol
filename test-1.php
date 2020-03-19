#!/usr/bin/php

<?php

$url = 'http://34.82.252.252/index.php/rest/V1/integration/admin/token';
 
$data = array("username" => "mbaranello", "password" => "Juampi123");
$data_string = json_encode($data);
 
$headers = array(
    'Content-Type: application/json',
    'Content-Length: ' . strlen($data_string)
);
 
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$token = curl_exec($ch);
 
$token = json_decode($token);
echo $token;

?>