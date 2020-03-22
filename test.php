#!/usr/bin/php

<?php

/* $token = 'gl4gpkwtdysux61ymbfznwavekp5hlkq';
  
$requestUrl = 'http://http://34.82.252.252/index.php/rest/V1/products/';

$headers = array("Authorization: Bearer $token");
 
$ch = curl_init($requestUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
 
$result=  json_decode($result);
print_r($result); */

$text= "31288,49151,144160,172089,173499,251829,252050,345718,350688";
$ind = "";

echo substr_count($text, ',');

echo "\n";

$texto = explode(",", $text);
print_r ($texto);

for ($i=1; $i <= count($texto); $i++)
{   
    if ($i != count($texto))
        $ind = $ind . ($i) . ',';
    else
        $ind = $ind . ($i);
}

print $ind;

date_default_timezone_set('America/Argentina/Tucuman');

echo date('d/m/Y H:i:s');

?>