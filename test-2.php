#!/usr/bin/php

<?php

$token = 'k2pris0urlucc3bc8k5tlutlnodbm9jj';
  
$requestUrl = 'http://34.82.252.252/index.php/rest/V1/products/';

$headers = array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
);
$data = array(
    'product' => array( 'sku' => '365469', 'custom_attributes' => array('0' => array( 'attribute_code' => 'marca_vehiculo', 'value' => 'chino' ) )
    )
);
  
$data = json_encode($data);
  
$ch = curl_init($requestUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
  
$result=  json_decode($result);
print_r($result);
?>