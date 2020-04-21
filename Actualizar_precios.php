#!/usr/bin/php

<?php

include 'Clases-Magento.php';

#-- Creo objeto tipo Token
$toquen_magento = new Token();
$clave_token = $toquen_magento->obtener_token($username, $password, $token_url);

echo $clave_token."\r\n";

$headers = array('Content-Type:application/json','Authorization:Bearer '.$clave_token);
 
// Creo URL de REST API para productos
$apiUrl = $url."/V1/products";

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
$Articulos_magento = mysqli_query ( $enlace, $Consulta_precios );

#-- Inicia una nueva sesión y devuelve el manipulador curl para el uso de las funciones curl_setopt(), curl_exec(), y curl_close().
$chp = curl_init();

#-- Con los productos obtenidos chequeo existencia del Archivo en Directorio
while ($art_magento = mysqli_fetch_array($Articulos_magento)):

    $arti = new Articulo();
    $data = $arti->cargar_matriz_precios_stock($art_magento["sku"],$art_magento["price"],$art_magento["special_price"],$art_magento["qty"]);

    print_r($data);

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
    echo "\r\nArticulo modificado. - " . date('d/m/Y H:i:s') . "\r\n";

    unset($arti);
        
endwhile;

curl_close($chp);
//curl_close($ch);

echo "Proceso Finalizado. - " . date('d/m/Y H:i:s');

/* cerrar la conexion */
mysqli_close ( $enlace );

?>
