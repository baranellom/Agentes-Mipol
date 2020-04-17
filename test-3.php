#!/usr/bin/php

<?php

$url = "http://34.82.252.252/index.php/rest";
$token_url= $url."/V1/integration/admin/token";
 
$username= "mbaranello";
$password= "Carola123";
 
class Token  {
    #-- Propiedades
    public $user;
    public $pass;
    public $dir_token;
    public $codigo;

    #-- Metodos
    function obtener_token($user,$pass,$dir_token){

        //Authentication REST API magento 2,    
        $ch = curl_init();
        $data = array("username" => $user, "password" => $pass);
        $data_string = json_encode($data);
        
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL, $dir_token);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string) ));

        $admintoken = curl_exec($ch);
            
        $this->codigo = json_decode($admintoken);

        return $this->codigo;

    }

}

$toquen_magento = new Token();
$clave_token = $toquen_magento->obtener_token($username, $password, $token_url);

echo $clave_token;

$headers = array('Content-Type:application/json','Authorization:Bearer '.$clave_token);
 
// Createt Product REST API URL
$apiUrl = $url."/V1/products";
 
// $ch = curl_init();
$data = [
    "product" => [
        "sku" => "103888-1",
        //"name" => "Agua VMG VMG-BA682",
        "attribute_set_id" => 4,
        //"price" => 8164.92,
        "status" => 1,
        "visibility" => 4,
        "type_id" => "simple",
        //"weight" => "1",
        "extension_attributes" => [
            "category_links" => [
                  [
                      "position" => 0,
                      "category_id" => "66"
                  ]
                  //[
                  //"position" => 1,
                  //"category_id" => "7"
                  //]
            ],
            "stock_item" => [
                    "qty" => "0",
                    "is_in_stock" => 1
            ]
        ],

        "product_links" => [
            [
                "sku" => "103888-1",
                "link_type" => "related",
                "linked_product_sku" => "260638-1",
                "linked_product_type" => "virtual",
                "position" => 1
            ],

            [
                "sku" => "103888-1",
                "link_type" => "related",
                "linked_product_sku" => "362912-1",
                "linked_product_type" => "virtual",
                "position" => 2
            ]
        ]

        // "custom_attributes" => [
        //     [
        //         "attribute_code" => "description",
        //         "value" => "Description of product here"
        //     ],
        //     [
        //         "attribute_code" => "short_description",
        //         "value" => "short description of product"
        //     ],
        //     [
        //         "attribute_code" => "marca_del_producto",
        //         "value" => 5569
        //     ],
        // ]
    ]
];
$data_string = json_encode($data);
 
$ch = curl_init();
curl_setopt($ch,CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
 
$response = json_decode($response, TRUE);
print_r($response);

curl_close($ch);