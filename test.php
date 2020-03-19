#!/usr/bin/php

<?php

$token = 'gl4gpkwtdysux61ymbfznwavekp5hlkq';
  
$requestUrl = 'http://http://34.82.252.252/index.php/rest/V1/products/';

$headers = array("Authorization: Bearer $token");
 
$ch = curl_init($requestUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
 
$result=  json_decode($result);
print_r($result);

?>