#!/usr/bin/php

<?php

include 'Clases-Magento.php';

#-- Creo objeto tipo Token
$toquen_magento = new Token();
$clave_token = $toquen_magento->obtener_token($username, $password, $token_url);

echo $clave_token."\r\n";

$headers = array('Content-Type:application/json','Authorization:Bearer '.$clave_token);
 
// Creo URL de REST API para productos
$apiUrl = $url."/V1/products/attributes/marca_vehiculo/options";

echo $apiUrl;

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "consultor", "mipol_123", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */
if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}
// Obtengo datos con la consulta anterior desde la Base de Datos
$Articulos_magento = mysqli_query ( $enlace, $Consulta_marca_vehiculos );

#-- Inicia una nueva sesión y devuelve el manipulador curl para el uso de las funciones curl_setopt(), curl_exec(), y curl_close().
$chp = curl_init();

#-- Revisar bien estos valores, para ajustar en fn a los que existen.
$orden = 1;
//$valor = 4;

#-- Con los productos obtenidos chequeo existencia del Archivo en Directorio
while ($art_magento = mysqli_fetch_array($Articulos_magento)):

    $arti = new MarcaVehiculo();
    $data = $arti->cargar_matriz_marca_vehiculo($art_magento["mv"],$orden);

    $orden++ ;
    //$valor = $valor + 1;
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

//$arti = new Categoria();
//$data = $arti->cargar_matriz_categoria(3,"Hijo-3",1,1,3);

//$arti = new Manufacturer();
//$data = $arti->cargar_matriz_manufacturer("Prueba4","9","6");

curl_close($chp);

echo "Proceso Finalizado. - " . date('d/m/Y H:i:s');

/* cerrar la conexion */
mysqli_close ( $enlace );

?>
