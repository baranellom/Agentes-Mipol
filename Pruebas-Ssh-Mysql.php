#!/usr/bin/php

<?php

//$DIRHOME="/usr/share/Alertas/";
$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";

// include ($DIRHOME . "phpmailer/class.phpmailer.php");
// include_once ($DIRHOME . "agente-pve-stock-plus.php");
// include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
// require_once ($DIRHOME . "CSVToExcelConverter.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");

$grupo_dep1 = "45,46";	//Dep Los Pocitos, Suc Autopartes
$grupo_dep2 = "1,6,30";	//Dep CC, BRS, JBJ
$grupo_dep3 = "5";		//Concep
$grupo_dep4 = "2,7";	// Dep Sgo, LB
$grupo_dep5 = "3,40";   //Dep Jujuy, Salta
$grupo_dep6 = "8,36";   //Dep Catam, Mendoza

$dbServerHost = "192.168.0.155";
$username = "mipoldb";
$password = "mipol123";
$dbname = "fc";

$resuelto = false;
$Stock_1 = 0;
$Stock_2 = 0;
$Stock_3 = 0;
$Stock_4 = 0;
$Stock_5 = 0;
$Stock_6 = 0;

$i = 0;

// Obetngo productos marcados para comprar en los pedidos de Vetntas de las Sucursales
$Consulta_inicial = "SELECT * FROM actualizacion_ecommerce;";

//$mail->PluginDir = $DIRHOME . "phpmailer/";

$Datos = "\"Prd_id\",\"CodAlfa\",\"Division\",\"Clasificacion\",\"Articulo\",\"StockSUC\",\"Fecha Ult Venta\"\r\n";

//shell_exec("C:\plink.exe -ssh 192.168.0.155 -l root -pw 'ingenio' -P 45654 -N -L 3307:127.0.0.1:3306");
//c:/plink -fNg -L 3307:$dbServerHost:3306 user@remote_host");
$enlace = new mysqli($dbServerHost, $username, $password, $dbname, 3307);

date_default_timezone_set('America/Argentina/Tucuman');

//$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */

if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}

//$depositos = mysqli_query ( $enlace, $Depositos_SQL );
$Articulos_pve = mysqli_query ( $enlace, $Consulta_inicial );


$art_pve = mysqli_fetch_array($Articulos_pve);

print_r($art_pve);

echo "Informes enviados ";

// // Borro los Archivos Generados
// unlink ( $DIRHOME . $ARCHIVOXLS );

/* cerrar la conexion */
mysqli_close ( $enlace );

?>