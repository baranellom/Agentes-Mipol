#!/usr/bin/php

<?php

$api_url = 'http://34.82.252.252/rest/V1';
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
$curl = curl_init($api_url . "/products/10?fields=name,sku,status,extension_attributes[category_links,stock_item[item_id,qty]]");
//$curl = curl_init($api_url . "/products/?searchCriteria[filter_groups][0][filters][0][field]=status&searchCriteria[filter_groups][0][filters][0][value]=2&searchCriteria[filter_groups][0][filters][0][condition_type]=eq&searchCriteria[pageSize]=10&fields=items[id,sku,name,status]");
//$curl = curl_init($api_url . "/products/?searchCriteria[filter_groups][0][filters][0][field]=status&searchCriteria[filter_groups][0][filters][0][value]=2&searchCriteria[filter_groups][0][filters][0][condition_type]=eq&fields=items[id,sku,name,status]");


curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: Bearer " . json_decode($token)));

$result = curl_exec($curl);
print_r(json_decode($result));

?>