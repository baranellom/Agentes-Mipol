#!/usr/bin/php

<?php

#-- Linux 156
#$DIRHOME="/usr/share/Alertas/";
#-- Pc Oficina
$DIRHOME = "D:/FotosMagento/GeneralJPG/";
#-- Pc de Casa
#$DIRHOME = "D:/FotosMagento/GeneralJPG";

##-- Consulta para obtener los productos que se subieron a Magento, para chequear la existencia del arhivo de imagen
//$Consulta_inicial = "SELECT sku, archivoimagen, existeimagen, fecha_up FROM articulos_magento WHERE name LIKE '%BREMEN%';";
$Consulta_inicial = "SELECT sku, archivoimagen, existeimagen, fecha_up FROM articulos_magento;";

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

$i = 0;

#-- Con los productos obtenidos chequeo existencia del Archivo en Directorio
while ($art_magento = mysqli_fetch_array($Articulos_magento)):

	$nombre_fichero = $DIRHOME . $art_magento['archivoimagen'];

	if ($art_magento['existeimagen'] == 0)
	{
		#-- Chequeo que el archivo con el nombre de la imagen exista
		if (file_exists($nombre_fichero)) 
		{
			//echo "El fichero $nombre_fichero existe \n";

			$Modificar_registro = "UPDATE articulos_magento SET existeimagen='1', fecha_up = now() WHERE sku='".$art_magento['sku']."' LIMIT 1;";

			$Mod_reg = mysqli_query($enlace, $Modificar_registro);

			$i++;
		} 
		else 
		{
			//echo "El fichero $nombre_fichero no existe \n";
			
			$Modificar_registro = "UPDATE articulos_magento SET existeimagen='0' WHERE sku='".$art_magento['sku']."' LIMIT 1;";

			$Mod_reg = mysqli_query($enlace, $Modificar_registro);
		}
	}
	

endwhile;

echo "Proceso Finalizado. - " . date('d/m/Y H:i:s'). "\n";

echo "Se procesaron " . $i . " imagenes. \n";

/* cerrar la conexion */
mysqli_close ( $enlace );

?>