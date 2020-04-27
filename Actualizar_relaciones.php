#!/usr/bin/php

<?php

include 'Clases-Magento.php';

#-- Creo objeto tipo Token
$toquen_magento = new Token();
$clave_token = $toquen_magento->obtener_token($username, $password, $token_url);

echo $clave_token."\r\n";

#-- Creo URL de REST API para productos
//$apiUrl = $url."/V1/products";

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza el proceso..." . date ( 'r' ) . "\r\n";

#-- Comprobar la conexion
if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}

#-- Chequeo que no haya otra conexion establecida actualizando productos
$Chequeo_ejecucion = mysqli_query ( $enlace, "SELECT * FROM actualizacion_ecommerce a WHERE a.tienda_id = 1 AND a.fechahora_fin IS NULL;" );

if ($en_ejecucion = mysqli_fetch_array($Chequeo_ejecucion)){
    printf ( "Existe otra Actualizacion trabajando, inicializada el: %s\n", $en_ejecucion["fechahora_inicio"] );
	exit ();
} else {
    #-- Busco maximo id e inserto registro en tabla actualizaciones_ecommmerce   
    $max_id = mysqli_query ( $enlace, "SELECT MAX(id) AS id FROM actualizacion_ecommerce;" );
    $id_max = mysqli_fetch_array($max_id);
    #-- incremento en 1 $id_max
    $id_max["id"]++;

    #-- Cargo ejecucion en tipo 3 = Sustitutos
    if (mysqli_query ( $enlace, "INSERT INTO actualizacion_ecommerce (id, tienda_id, tipo, fechahora_inicio) VALUES ('".$id_max["id"]."', '1', '3', now());" )){
        printf ( "Se inició una nueva actualización de Sustitutos\r\n" );
        
    } else {
        printf ( "Existen problemas para insertar un nuevo registro" );
        exit ();
    }
}

#-- Obtengo datos con la consulta desde la Base de Datos
$Articulos_magento = mysqli_query ( $enlace, $Consulta_sustitutos );

#-- Obtengo Cantidad de registros de la consulta anterior para guardar en la tabla
$Cant_articulos_actualizados = mysqli_affected_rows($enlace);

printf ( "Se actualizarán ".$Cant_articulos_actualizados." Sustitutos.\r\n" );

#-- Armo encabezado de datos a enviar
$headers = array('Content-Type:application/json','Authorization:Bearer '.$clave_token);

#-- Inicia una nueva sesión y devuelve el manipulador curl para el uso de las funciones curl_setopt(), curl_exec(), y curl_close().
$chp = curl_init();

#-- Con los productos obtenidos chequeo existencia del Archivo en Directorio
while ($art_magento = mysqli_fetch_array($Articulos_magento)):

    $arti = new Articulo();
    $data = $arti->cargar_matriz_sustitutos($art_magento["sku"],$art_magento["related_skus"],$art_magento["related_position"]);
    
    //print_r($data);

    $data_string = json_encode($data);

    curl_setopt($chp,CURLOPT_URL, $apiUrl);
    curl_setopt($chp, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($chp, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($chp, CURLOPT_RETURNTRANSFER, true);  #-- si el true no muestra detalles del articulo
    curl_setopt($chp, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chp, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($chp);
     
    $response = json_decode($response, TRUE);
    //print_r($response);
    echo "\r\nSustitutos del Articulo ".$art_magento["sku"]." modificado. - " . date('d/m/Y H:i:s') . "\r\n";

    unset($arti);
        
endwhile;

curl_close($chp);
//curl_close($ch);

$Cierro_ejecucion = mysqli_query ( $enlace, "UPDATE actualizacion_ecommerce SET fechahora_fin=now() , reg_afectados=".$Cant_articulos_actualizados." WHERE id=".$id_max["id"].";" );
echo "Proceso Finalizado. Se actualizaron ".$Cant_articulos_actualizados." registros. - " . date('d/m/Y H:i:s');

/* cerrar la conexion */
mysqli_close ( $enlace );

?>
