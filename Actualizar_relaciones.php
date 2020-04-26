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
        curl_setopt($ch, CURLOPT_URL, $dir_token);
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

#-- Creo objeto tipo Token
$toquen_magento = new Token();
$clave_token = $toquen_magento->obtener_token($username, $password, $token_url);

echo $clave_token;
$headers = array('Content-Type:application/json','Authorization:Bearer '.$clave_token);
 
// Creo URL de REST API para productos
$apiUrl = $url."/V1/products";
 

$Consulta_inicial = "SELECT * FROM articulos_magento a WHERE a.related_skus != '' AND a.related_position = '1,2,3' limit 1;";

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */
if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}
// Obtengo datos con la consulta anterior desde la Base de Datos
$Articulos_magento = mysqli_query ( $enlace, $Consulta_inicial );

#-- Inicia una nueva sesión y devuelve el manipulador curl para el uso de las funciones curl_setopt(), curl_exec(), y curl_close().
$chp = curl_init();

#-- Con los productos obtenidos chequeo existencia del Archivo en Directorio
while ($art_magento = mysqli_fetch_array($Articulos_magento)):

    $relaciones = explode(",", $art_magento["related_skus"]);
    echo $relaciones[0]; // porción1
    echo $relaciones[1]; // porción2
    echo $relaciones[2]; // porción3
    $posiciones = explode(",", $art_magento["related_position"]);
    echo $posiciones[0]; // porción1
    echo $posiciones[1]; // porción2
    echo $posiciones[2]; // porción3

    $data = [
        "product" => [
            "sku" => $art_magento["sku"],
            //"attribute_set_id" => 4,
            //"status" => 1,
            //"visibility" => 4,
            "type_id" => "simple",
            "product_links" => [
                [
                    "sku" => $art_magento["sku"],
                    "link_type" => "related",
                    //"linked_product_sku" => $art_magento["related_skus"],
                    "linked_product_sku" => $relaciones[0],
                    "linked_product_type" => "simple",
                    //"position" => $art_magento["related_position"]
                    "position" => $posiciones[0]
                ],
    
                [
                    "sku" => $art_magento["sku"],
                    "link_type" => "related",
                    //"linked_product_sku" => $art_magento["related_skus"],
                    "linked_product_sku" => $relaciones[1],
                    "linked_product_type" => "simple",
                    //"position" => $art_magento["related_position"]
                    "position" => $posiciones[1]
                ],
                [
                    "sku" => $art_magento["sku"],
                    "link_type" => "related",
                    //"linked_product_sku" => $art_magento["related_skus"],
                    "linked_product_sku" => $relaciones[2],
                    "linked_product_type" => "simple",
                    //"position" => $art_magento["related_position"]
                    "position" => $posiciones[2]
                ]
            ]
        ]
    ];
    $data_string = json_encode($data);
     
    //$ch = curl_init();
    curl_setopt($chp,CURLOPT_URL, $apiUrl);
    curl_setopt($chp, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($chp, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($chp, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($chp, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chp, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($chp);
     
    $response = json_decode($response, TRUE);
    print_r($response);
    echo "\r\nArticulo modificado. - " . date('d/m/Y H:i:s') . "\r\n";;
        
endwhile;

curl_close($chp);
//curl_close($ch);

echo "Proceso Finalizado. - " . date('d/m/Y H:i:s');

/* cerrar la conexion */
mysqli_close ( $enlace );

?>
