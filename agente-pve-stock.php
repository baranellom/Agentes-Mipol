#!/usr/bin/php

<?php

//$DIRHOME="/usr/share/Alertas/";
$DIRHOME = "D:/ProyectosVariosMipol/Mipol-Agentes/";

include ($DIRHOME . "phpmailer/class.phpmailer.php");
include_once ($DIRHOME . "agente-pve-stock-plus.php");
include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");

$ARCHIVOCSV = "mipol_ml.csv";
$ARCHIVOXLS="Reorganizacion_Productos.xlsx";

$grupo_dep1 = "1,5,6,30,46";
$grupo_dep2 = "2,7";
$grupo_dep3 = "3,40";
$grupo_dep4 = "8,36";
$resuelto = false;
$Stock_1 = 0;
$Stock_2 = 0;
$Stock_3 = 0;

$i = 0;

// Obetngo productos marcados para comprar en los pedidos de Vetntas de las Sucursales
$Consulta_inicial = "SELECT * FROM detpve WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail IS NULL;";

//$mail->PluginDir = $DIRHOME . "phpmailer/";

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
	(SELECT max(c.cpbvta_fecha)
	FROM cpbvta c 
	INNER JOIN  detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
	WHERE c.cpbvta_tipocpb=1  AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) AND d.Prd_id = ".$art_pve['prd_id']." AND c.cpbvta_suc  = stock_mp.suc_id GROUP BY d.Prd_id) AS U_Venta,
	(SELECT MAX(cs.cpbstock_fecha)
	FROM cpbstock cs 
	INNER JOIN detcpbstock_prd dcs ON cs.cpbstock_id = dcs.cpbstock_id AND cs.cpbstock_suc = dcs.cpbstock_suc
	WHERE cs.TipoCpb_id=89 AND ((cs.cpbstock_fecanul IS NULL) OR (cs.cpbstock_fecanul='0000-00-00')) AND dcs.Prd_id = ".$art_pve['prd_id']." AND cs.cpbstock_suc = stock_mp.suc_id GROUP BY dcs.Prd_id) AS U_IpSuc
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
	AND prd.prd_id = ".$art_pve['prd_id']." HAVING U_IpSuc < (CURDATE() - INTERVAL 14 DAY) /*IPSUC ingresadas 14 dias antes*/ ORDER BY 14 ASC;";

	//echo $ConsultaStock_Suc_Grupo1 . "\n";

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
	(SELECT max(c.cpbvta_fecha)
	FROM cpbvta c 
	INNER JOIN  detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
	WHERE c.cpbvta_tipocpb=1  AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) AND d.Prd_id = ".$art_pve['prd_id']." AND c.cpbvta_suc  = stock_mp.suc_id GROUP BY d.Prd_id) AS U_Venta,
	(SELECT MAX(cs.cpbstock_fecha)
	FROM cpbstock cs 
	INNER JOIN detcpbstock_prd dcs ON cs.cpbstock_id = dcs.cpbstock_id AND cs.cpbstock_suc = dcs.cpbstock_suc
	WHERE cs.TipoCpb_id=89 AND ((cs.cpbstock_fecanul IS NULL) OR (cs.cpbstock_fecanul='0000-00-00')) AND dcs.Prd_id = ".$art_pve['prd_id']." AND cs.cpbstock_suc = stock_mp.suc_id GROUP BY dcs.Prd_id) AS U_IpSuc
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep2.") /* Consultar en Depositos Grupo 2*/
	) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep2.") /* Consultar en Depositos Grupo 2*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']." HAVING U_IpSuc < (CURDATE() - INTERVAL 14 DAY) /*IPSUC ingresadas 14 dias antes*/ ORDER BY 14 ASC;";

	// echo $ConsultaStock_Suc_Grupo2 . "\n";

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
	(SELECT max(c.cpbvta_fecha)
	FROM cpbvta c 
	INNER JOIN  detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
	WHERE c.cpbvta_tipocpb=1  AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) AND d.Prd_id = ".$art_pve['prd_id']." AND c.cpbvta_suc  = stock_mp.suc_id GROUP BY d.Prd_id) AS U_Venta,
	(SELECT MAX(cs.cpbstock_fecha)
	FROM cpbstock cs 
	INNER JOIN detcpbstock_prd dcs ON cs.cpbstock_id = dcs.cpbstock_id AND cs.cpbstock_suc = dcs.cpbstock_suc
	WHERE cs.TipoCpb_id=89 AND ((cs.cpbstock_fecanul IS NULL) OR (cs.cpbstock_fecanul='0000-00-00')) AND dcs.Prd_id = ".$art_pve['prd_id']." AND cs.cpbstock_suc = stock_mp.suc_id GROUP BY dcs.Prd_id) AS U_IpSuc
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep3.") /* Consultar en Depositos Grupo 3*/
	) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep3.") /* Consultar en Depositos Grupo 3*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']." HAVING U_IpSuc < (CURDATE() - INTERVAL 14 DAY) /*IPSUC ingresadas 14 dias antes*/ ORDER BY 14 ASC;";
	
	// echo $ConsultaStock_Suc_Grupo3 . "\n";

	$Stock_grupo1 = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo1 ); 
	$Stock_grupo2 = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo2 ); 
	$Stock_grupo3 = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo3 ); 

	// Si existen productos en el grupo 1 que satisfaga el Stock necesitado 
	$Stock_1 = 0;
	$Stock_2 = 0;
	$Stock_3 = 0;

	$Sucs_g1 = "";
	$Sucs_g2 = "";
	$Sucs_g3 = "";

	while ($stock_g1 = mysqli_fetch_array($Stock_grupo1)) 
	{
		if ($stock_g1['Stock_Total'] >= $art_pve['cantidad'] )
		{
			echo "Pido x mail a Suc " . $stock_g1['Suc'].", ". $art_pve['cantidad'] ." unidad/es del producto ".$stock_g1['prd_codalfa'].", ID = ".$stock_g1['prd_id']. " \n";
			echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
			echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
			echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
			$resuelto = true;
			break;
		}
		else 
		{
			$Stock_1 = $Stock_1 + $stock_g1['Stock_Total'];
			$Sucs_g1 .= $Sucs_g1 . "," . $stock_g1['Suc'];

			if ($Stock_1 >= $art_pve['cantidad'])
			{
				echo "Pido x mail a las Suc's " . $stock_g1['Suc']." el producto ".$stock_g1['prd_codalfa'].  " \n";
				echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
				echo "Hay que pedir este producto a Las Siguientes Suc's: ". $Sucs_g1 . "\n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
				echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
				$resuelto = true;
				break;
			}
			else {
				$resuelto = false;
			}
			
		}
	}
	if ($resuelto == false)
	{
		while ($stock_g2 = mysqli_fetch_array($Stock_grupo2)) 
		{
			if ($stock_g2['Stock_Total'] >= $art_pve['cantidad']) 
			{
				echo "Pido x mail a Suc " . $stock_g2['Suc'].", ". $art_pve['cantidad'] ." unidad/es del producto ".$stock_g2['prd_codalfa'].", ID = ".$stock_g2['prd_id']. " \n";
				echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
				echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
				$resuelto = true;
				break;
			}
			else 
			{
				$Stock_2 = $Stock_2 + $stock_g2['Stock_Total'];
				$Sucs_g2 .= $Sucs_g2 . "," . $stock_g2['Suc'];

				if ($Stock_2 >= $art_pve['cantidad'])
				{
					echo "Pido x mail a las Suc's " . $stock_g2['Suc']." el producto ".$stock_g2['prd_codalfa'].  " \n";
					echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
					echo "Hay que pedir este producto a Las Siguientes Suc's: ". $Sucs_g2 . "\n";
					echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
					echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
					$resuelto = true;
					break;
				}
				else 
				{
				$resuelto = false;
				}
			}	
		}
	}
	if ($resuelto == false)
	{
		while ($stock_g3 = mysqli_fetch_array($Stock_grupo2)) 
		{
			if ($stock_g3['Stock_Total'] >= $art_pve['cantidad']) 
			{
				echo "Pido x mail a Suc " . $stock_g3['Suc'].", ". $art_pve['cantidad'] ." unidad/es del producto ".$stock_g3['prd_codalfa'].", ID = ".$stock_g3['prd_id']. " \n";
				echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
				echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
				$resuelto = true;
				break;
			}
			else 
			{
				$Stock_3 = $Stock_3 + $stock_g3['Stock_Total'];
				$Sucs_g3 .= $Sucs_g3 . "," . $stock_g3['Suc'];

				if ($Stock_3 >= $art_pve['cantidad'])
				{
					echo "Pido x mail a las Suc's " . $stock_g3['Suc']." el producto ".$stock_g3['prd_codalfa'].  " \n";
					echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
					echo "Hay que pedir este producto a Las Siguientes Suc's: ". $Sucs_g3 . "\n";
					echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
					echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
					$resuelto = true;
					break;
				}
				else 
				{
				$resuelto = false;
				}
			}	
		}
		echo "Pido a Proveedores, Envio mail a David x el producto ".$stock_g3['prd_codalfa'].  " \n";
		echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
		echo "Marco Producto PVE en tabla detpve como enviado a Compras \n";
		echo "Salgo del Ciclo Where, ya que el producto fue solicitado a Compras\n\n";
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