#!/usr/bin/php

<?php

//$DIRHOME="/usr/share/Alertas/";
$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";

include ($DIRHOME . "phpmailer/class.phpmailer.php");
include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");

$ARCHIVOCSV = "mipol_ml.csv";
$ARCHIVOXLS="Reorganizacion_Productos.xlsx";
$MAILSISTEMA = "sistema@mipolrepuestos.com";
$MAILTEST = "dretondo@mipolrepuestos.com";
$MAILSAMMY = "sammy.moreno@microsolutions.cl";
$MAILCC_PAEZ = "rpaez@mipolrepuestos.com";
$MAILCC_RETONDO = "dretondo@mipolrepuestos.com";
$MAILCC_TEK = "jtek@mipolrepuestos.com";
$MAILSGO_MM = "mmarcucci@mipolrepuestos.com";
$MAIL_RPOLICHE = "rpoliche@mipolrepuestos.com";
$MAILJUJUY = "mipoljujuy@mipolrepuestos.com";
$MAILCONCEPCION = "mipolconcep@mipolrepuestos.com";
$MAILBRS = "mipolbrs@mipolrepuestos.com";
$MAILLB = "mipol-labanda@mipolrepuestos.com";
$MAILMENDONZA = "mipolmendoza@mipolrepuestos.com";
$MAILJBJUSTO = "mipoljbjusto@mipolrepuestos.com";
$MAILCATAMARCA = "jgrosso@grupo-autopartes.com.ar";
$MAILSALTA = "ldiaz@grupo-autopartes.com.ar";
$MAIL_GPOLICHE = "gpoliche@grupo-autopartes.com.ar";
$MAILMORENO = "mmoreno@grupo-autopartes.com.ar";
$MAIL_ESTELA = "evizcarra@mipolrepuestos.com";
$MAILLP = "deposito-lospocitos@grupo-autopartes.com.ar";
$MAIL_MMATIAS = "mmatias@grupo-autopartes.com.ar";
$MAIL_HELGUERO = "chelguero@mipolrepuestos.com";
$MAIL_EXPEDICION = "expedicion@grupo-autopartes.com.ar";
$MAIL_LBARRAZA = "lbarraza@grupo-autopartes.com.ar";
$MAIL_PPEREZ = "pperez@grupo-autopartes.com.ar";
$MAIL_WSANCHEZ = "wsanchez@grupo-autopartes.com.ar";
$grupo_dep1 = "1,5,6,30,46";
$grupo_dep2 = "2,7";
$grupo_dep3 = "3,40";
$grupo_dep4 = "8,36";
$resuelto = false;

$i = 0;

// Obetngo productos marcados para comprar en los pedidos de Vetntas de las Sucursales
$Consulta_inicial = "SELECT * FROM detpve WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail IS NULL;";

$mail->PluginDir = $DIRHOME . "phpmailer/";

$Datos = "\"Prd_id\",\"CodAlfa\",\"Division\",\"Clasificacion\",\"Articulo\",\"StockSUC\",\"Fecha Ult Venta\"\r\n";

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */

if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}

//$depositos = mysqli_query ( $enlace, $Depositos_SQL );
$Articulos_pve = mysqli_query ( $enlace, $Consulta_inicial );

#Calculamos con los productos que se agregaron a la tabla de PVE, los Stock disponibles en los Grupos de Sucursales
while ($art_pve = mysqli_fetch_array($Articulos_pve)) 
{
	$ConsultaStock_Suc_Grupo1 = "SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, 0) AS Stk_Libre, 
	IF(r_dpt_prd.r_stock >= 1, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)), 0) AS 'Dif_Stk_Max', 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))) AS Stock_Total,
	stock_mp.suc_id AS Suc,
	r_dpt_prd.dpt_id AS Dep_Suc,
	stock_mp.stock AS Stock_Suc_Full,
	CAST(r_dpt_prd.r_maximo AS SIGNED) AS Max_Suc, 
	r_dpt_prd.r_stock AS Reponer_Suc,
	prd.prd_clasificacion AS Clasificacion,
	(SELECT max(c.cpbvta_fecha) as fecha 
	FROM cpbvta c 
	INNER JOIN  detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
	INNER JOIN  prd on d.Prd_id = prd.prd_id
	WHERE c.cpbvta_tipocpb=1  AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) AND prd.prd_id = ".$art_pve['prd_id']." AND c.cpbvta_suc IN (".$grupo_dep1.") GROUP BY d.Prd_id) AS U_Venta
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep1.") /* Consultar en Depositos Grupo 1*/
	 ) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep1.") /* Consultar en Depositos Grupo 1*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']." ORDER BY 14 ASC;";

	echo $ConsultaStock_Suc_Grupo1 . "\n";

	$ConsultaStock_Suc_Grupo2 = "SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, 0) AS Stk_Libre, 
	IF(r_dpt_prd.r_stock >= 1, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)), 0) AS 'Dif_Stk_Max', 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))) AS Stock_Total,
	stock_mp.suc_id AS Suc,
	r_dpt_prd.dpt_id AS Dep_Suc,
	stock_mp.stock AS Stock_Suc_Full,
	CAST(r_dpt_prd.r_maximo AS SIGNED) AS Max_Suc, 
	r_dpt_prd.r_stock AS Reponer_Suc,
	prd.prd_clasificacion AS Clasificacion,
	(SELECT max(c.cpbvta_fecha) as fecha 
	FROM cpbvta c 
	INNER JOIN  detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
	INNER JOIN  prd on d.Prd_id = prd.prd_id
	WHERE c.cpbvta_tipocpb=1  AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) AND prd.prd_id = ".$art_pve['prd_id']." AND c.cpbvta_suc IN (".$grupo_dep2.") GROUP BY d.Prd_id) AS U_Venta
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep2.") /* Consultar en Depositos Grupo 1*/
	 ) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep2.") /* Consultar en Depositos Grupo 1*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']." ORDER BY 14 ASC;";

	echo $ConsultaStock_Suc_Grupo2 . "\n";

	$ConsultaStock_Suc_Grupo3 = "SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, 0) AS Stk_Libre, 
	IF(r_dpt_prd.r_stock >= 1, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)), 0) AS 'Dif_Stk_Max', 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))) AS Stock_Total,
	stock_mp.suc_id AS Suc,
	r_dpt_prd.dpt_id AS Dep_Suc,
	stock_mp.stock AS Stock_Suc_Full,
	CAST(r_dpt_prd.r_maximo AS SIGNED) AS Max_Suc, 
	r_dpt_prd.r_stock AS Reponer_Suc,
	prd.prd_clasificacion AS Clasificacion,
	(SELECT max(c.cpbvta_fecha) as fecha 
	FROM cpbvta c 
	INNER JOIN  detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
	INNER JOIN  prd on d.Prd_id = prd.prd_id
	WHERE c.cpbvta_tipocpb=1  AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) AND prd.prd_id = ".$art_pve['prd_id']." AND c.cpbvta_suc IN (".$grupo_dep3.") GROUP BY d.Prd_id) AS U_Venta
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep3.") /* Consultar en Depositos Grupo 1*/
	 ) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep3.") /* Consultar en Depositos Grupo 1*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']." ORDER BY 14 ASC;";

	echo $ConsultaStock_Suc_Grupo3 . "\n";


	$Stock_grupo1 = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo1 ); 
	$Stock_grupo2 = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo2 ); 
	$Stock_grupo3 = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo3 ); 

	// Si existen productos en el grupo 1 que satisfaga el Stock necesitado 
	while ($stock_g1 = mysqli_fetch_array($Stock_grupo1)) 
	{
		if ($stock_g1['Stock_Total'] >= $art_pve['cantidad'] )
		{
			echo "Pido x mail a Suc " . $stock_g1['Suc']." el producto ".$stock_g1['prd_codalfa'].  " \n";
			echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
			echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n";
			$resuelto = true;
		}
		else 
		{
			$resuelto = false;
		}
	}
	if ($resuelto == false)
	{
		while ($stock_g2 = mysqli_fetch_array($Stock_grupo2)) 
		{
			if ($stock_g2['Stock_Total'] >= $art_pve['cantidad']) 
			{
				echo "Pido x mail a Suc " . $stock_g2['suc']." el producto ".$stock_g2['prd_codalfa'].  " \n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
				echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n";
				$resuelto = true;
			}
			else 
			{
				$resuelto = false;
			}	
		}
	}
	if ($resuelto == false)
	{
		while ($stock_g3 = mysqli_fetch_array($Stock_grupo3)) 
		{
			if ($stock_g3['Stock_Total'] >= $art_pve['cantidad']) 
			{
				echo "Pido x mail a Suc " . $stock_g3['suc']." el producto ".$stock_g3['prd_codalfa'].  " \n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
				echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n";
				$resuelto = true;
			}
			else 
			{
				$resuelto = false;
			}	
		}
	}

}


	
	
/* liberar el conjunto de resultados de Stock Negativos*/
mysqli_free_result ( $Stock_grupo1 );
mysqli_free_result ( $Stock_grupo2 );
mysqli_free_result ( $Stock_grupo3 );
mysqli_free_result ( $Articulos_pve);

// $mail = new PHPMailer ( true );

// $mail->SetLanguage('es', $DIRHOME . 'phpmailer/language/');

// $mail->IsSMTP ();

// // Activa la condificacción utf-8
// $mail->CharSet = 'UTF-8';

// $mail->SMTPAuth = true;

// $mail->SMTPDebug = 2;

// $mail->Host = "mailen3.cloudsector.net";

// $mail->Port = 587;

// $mail->Username = "sistema@mipolrepuestos.com";

// $mail->Password = "Abc$4321";

// $mail->SetFrom ( $MAILSISTEMA );

// $mail->FromName = "Servidor Linux de Mipol Repuestos SA";

// $body = "Estimado Encargado\r\n";
// $body .= "Saludos y buen día.\r\n";
// $body .= "Adjunto encontraran un ARCHIVO con el detalle de los productos que deberan ser enviados al Deposito OL.\r\n";
// $body .= "La información recibida le permitirá realizar una gestión sobre los productos que son enviados a su sucursal.\r\n";
// $body .= "Por cualquier duda o comentarios, por favor, contactar con Marcelo Matías, quien está a cargo del área de logística y distribución de la organización.\r\n";
// $body .= "Estamos atentos a sus comentarios\r\n";
// $body .= "Saludos\r\n";
		
// $mail->Body = $body;

// for( $a = 1 ; $a <= 12; $a++ )
// {
// 	$mail->clearAttachments();
// 	switch ($a) 
// 	{
// 		case 1:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN REENVIAR A OL DESDE SUC CASA CENTRAL";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc1.csv', 'Suc1.csv' );
// 		break;

// 		case 2:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN REENVIAR A OL DESDE SUC SANTIAGO DEL ESTERO";		
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc2.csv', 'Suc2.csv' );
// 		break;

// 		case 3:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN REENVIAR A OL DESDE SUC JUJUY";		
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc3.csv', 'Suc3.csv' );
// 		break;

// 		case 5:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC CONCEPCION";		
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc5.csv', 'Suc5.csv' );
// 		break;

// 		case 6:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC BR SALI";		
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc6.csv', 'Suc6.csv' );
// 		break;

// 		case 7:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC LA BANDA DE SGO";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc7.csv', 'Suc7.csv' );
// 		break;

// 		case 8:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC MENDOZA";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc8.csv', 'Suc8.csv' );
// 		break;

// 		case 9:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC GA";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc9.csv', 'Suc9.csv' );
// 		break;
// 		case 10:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC JB JUSTO";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc10.csv', 'Suc10.csv' );
// 		break;

// 		case 11:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC CATAMARCA";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc11.csv', 'Suc11.csv' );
// 		break;

// 		case 12:
// 				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC SALTA";
// 				//$mail->AddAddress ( $MAIL_WSANCHEZ );
// 				$mail->AddAddress ( $MAILSAMMY );
// 				$mail->AddBCC ( $MAILTEST );
// 				$mail->AddAttachment ( $DIRHOME . 'Suc12.csv', 'Suc12.csv' );
// 		break;
// 	}
// 	$mail->Send ();
// }


echo "Informes enviados ";

// // Borro los Archivos Generados
// unlink ( $DIRHOME . $ARCHIVOXLS );

/* cerrar la conexion */
mysqli_close ( $enlace );

?>