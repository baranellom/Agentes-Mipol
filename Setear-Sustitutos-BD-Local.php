#!/usr/bin/php

<?php

#-- Linux 156
//$DIRHOME="/usr/share/Alertas/";
#-- Pc Oficina
//$DIRHOME = "D:/FotosMagento/GeneralJPG/";
#-- Pc de Casa
//$DIRHOME = "D:/FotosMagento/GeneralJPG";

##-- Consulta para obtener los productos que se subieron a Magento, para chequear la existencia del arhivo de imagen
$Consulta_inicial = "SELECT sku, id_gestuc, fecha_up FROM articulos_magento ;";

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );
// $enlace = mysqli_connect ( "192.168.0.157", "root", "", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */
if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}
// Obtengo datos con la consulta anterior desde la Base de Datos
$Articulos_magento = mysqli_query ( $enlace, $Consulta_inicial );

#-- Con los productos obtenidos chequeo existencia del Archivo en Directorio
while ($art_magento = mysqli_fetch_array($Articulos_magento)):

	$Consulta_Sustitutos = "SELECT GROUP_CONCAT( CONCAT(r_prd_prd.prd_id2,'-1') ) as sustitutos from r_prd_prd WHERE r_prd_prd.prd_id1 = ".$art_magento['id_gestuc']." group by r_prd_prd.prd_id1 order by prd_id2 ;";
    $Sustitutos = mysqli_query($enlace, $Consulta_Sustitutos);

    while ($Art_Sustitutos = mysqli_fetch_array($Sustitutos)):

		//echo "Existen productos relacionados \n";

		$ind = "";

		$texto = explode(",", $Art_Sustitutos['sustitutos']);
		//print_r ($texto);

		for ($i=1; $i <= count($texto); $i++)
		{   
			if ($i != count($texto))
				$ind = $ind . ($i) . ',';
			else
				$ind = $ind . ($i);
		}

		//print $ind;

		$Modificar_registro = "UPDATE articulos_magento SET related_skus='".$Art_Sustitutos['sustitutos']."' , related_position ='".$ind."' WHERE sku='".$art_magento['sku']."' LIMIT 1;";
		
        $Mod_reg = mysqli_query($enlace, $Modificar_registro);
	
	endwhile;

endwhile;

echo "Proceso Finalizado. - " . date('d/m/Y H:i:s');

/* cerrar la conexion */
mysqli_close ( $enlace );

?>