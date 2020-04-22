#!/usr/bin/php

<?php

$api_url = 'http://34.82.252.252/index.php/rest/V1';
$userData = array("username" => "mbaranello", "password" => "Carola123");

$curl = curl_init($api_url . "/integration/admin/token");
curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($userData));
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Content-Lenght: " . strlen(json_encode($userData))));

$token = curl_exec($curl);

echo json_decode($token);

//$curl = curl_init($api_url . "/store/websites");
//$curl = curl_init($api_url . "/products/103888-1");
//$curl = curl_init($api_url . "/categories");
$curl = curl_init($api_url . "/products?searchCriteria => [
    'filterGroups' => [
      0 => [
        'filters' => [
           0 => [
             'field' => 'name',
             'value' => '%25GLACELFSUPRAROJO%25',
             'condition_type' => 'like'
           ]
           1 => [
             'field' => 'name',
             'value' => '%25ELF%25',
             'condition_type' => 'like'
           ]
        ]
      ]
    ]");


curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));

$result = curl_exec($curl);
print_r(json_decode($result));

?>