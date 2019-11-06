#!/usr/bin/php

<?php

//$DIRHOME="/usr/share/Alertas/";
$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";

include ($DIRHOME . "phpmailer/class.phpmailer.php");
include_once ($DIRHOME . "agente-pve-stock-plus.php");
include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");

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

	$Stock_1 = 0;
	$Stock_2 = 0;
	$Stock_3 = 0;

	$Sucs_g1 = "";
	$Sucs_g2 = "";
	$Sucs_g3 = "";

	// Si existen productos en el grupo 1 que satisfaga el Stock necesitado 
	while ($stock_g1 = mysqli_fetch_array($Stock_grupo1)) 
	{
		if ($stock_g1['Stock_Total'] >= $art_pve['cantidad'] )
		{
			echo "Pido x mail a Suc " . $stock_g1['Suc'].", ". $art_pve['cantidad'] ." unidad/es del producto ".$stock_g1['prd_codalfa'].", ID = ".$stock_g1['prd_id']. " \n";
			echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
			echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
			$Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $stock_g1['Suc']."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
			$Mod_reg = mysqli_query($enlace, $Modificar_registro);
			//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
			$resuelto = true;
			break;
		}
		else 
		{
			$Stock_1 = $Stock_1 + $stock_g1['Stock_Total'];
			if ($Sucs_g1 == "")
			{
				$Sucs_g1 = $stock_g1['Suc'];
			}
			else 
			{
				$Sucs_g1 = $Sucs_g1  . "," .  $stock_g1['Suc'];
			}
			

			if ($Stock_1 >= $art_pve['cantidad'])
			{
				echo "Pido x mail a las Suc's " . $stock_g1['Suc']." el producto ".$stock_g1['prd_codalfa'].  " \n";
				echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
				echo "Hay que pedir este producto a Las Siguientes Suc's: ". $Sucs_g1 . "\n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
				$Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $Sucs_g1."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
				$Mod_reg = mysqli_query($enlace, $Modificar_registro);
				//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
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
				$Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $stock_g2['Suc']."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
				$Mod_reg = mysqli_query($enlace, $Modificar_registro);
				//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
				$resuelto = true;
				break;
			}
			else 
			{
				$Stock_2 = $Stock_2 + $stock_g2['Stock_Total'];
				if ($Sucs_g2 == "")
				{
					$Sucs_g2 = $stock_g2['Suc'];
				}
				else 
				{
					$Sucs_g2 = $Sucs_g2  . "," .  $stock_g2['Suc'];
				}

				if ($Stock_2 >= $art_pve['cantidad'])
				{
					echo "Pido x mail a las Suc's " . $stock_g2['Suc']." el producto ".$stock_g2['prd_codalfa'].  " \n";
					echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
					echo "Hay que pedir este producto a Las Siguientes Suc's: ". $Sucs_g2 . "\n";
					echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
					$Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $Sucs_g2."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
					$Mod_reg = mysqli_query($enlace, $Modificar_registro);
					//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
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
				$Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $stock_g3['Suc']."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
				$Mod_reg = mysqli_query($enlace, $Modificar_registro);
				//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
				$resuelto = true;
				break;
			}
			else 
			{
				$Stock_3 = $Stock_3 + $stock_g3['Stock_Total'];
				if ($Sucs_g3 == "")
				{
					$Sucs_g3 = $stock_g3['Suc'];
				}
				else 
				{
					$Sucs_g3 = $Sucs_g3  . "," .  $stock_g3['Suc'];
				}

				if ($Stock_3 >= $art_pve['cantidad'])
				{
					echo "Pido x mail a las Suc's " . $stock_g3['Suc']." el producto ".$stock_g3['prd_codalfa'].  " \n";
					echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
					echo "Hay que pedir este producto a Las Siguientes Suc's: ". $Sucs_g3 . "\n";
					echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
					$Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $Sucs_g3."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
					$Mod_reg = mysqli_query($enlace, $Modificar_registro);
					//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a una Sucursal\n\n";
					$resuelto = true;
					break;
				}
				else 
				{
				$resuelto = false;
				}
			}	
		}
		echo "Pido a Proveedores, Envio mail a David x el producto id = ".$art_pve['prd_id']." \n";
		echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
		$Modificar_registro = "UPDATE detpve SET detpve_destmail='Compras', detpve_sucmail='', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;";
		$Mod_reg = mysqli_query($enlace, $Modificar_registro);
		echo "Marco Producto PVE en tabla detpve como enviado a Compras \n";
		//echo "Salgo del Ciclo Where, ya que el producto fue solicitado a Compras\n\n";
	}

}

/* liberar el conjunto de resultados de Stock Negativos*/
// mysqli_free_result ( $Stock_grupo1 );
// mysqli_free_result ( $Stock_grupo2 );
// mysqli_free_result ( $Stock_grupo3 );
// mysqli_free_result ( $Articulos_pve);

$Query_Pve_compras = "SELECT * FROM pve 
INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
INNER JOIN prd ON prd.prd_id = detpve.prd_id
INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Compras' AND detpve.detpve_mailenviado = 0;";

//Envio de Art para solicitar al proveedor
if (mysqli_num_rows($Pve_compras = mysqli_query($enlace, $Query_Pve_compras)) > 0)
{
	$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\",\"Pedido N\",\"Cliente\",\"Fecha\",\"Suc\"\r\n";
	$Compras_file= fopen('Compras.csv',"w");
	fwrite($Compras_file, $Datos);

	while ($reg_compras = mysqli_fetch_array ($Pve_compras))
	{
		//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\",\"Pedido N\",\"Cliente\",\"Fecha\",\"Suc\"\r\n";
		$reg = $reg_compras['cantidad'].",".$reg_compras['prd_id'].",\"".$reg_compras['prd_codalfa']."\",\"".$reg_compras['prd_detanterior']."\",\"".$reg_compras['cpbvta_nro']."\",\"".$reg_compras['pve_detclt']."\",\"".$reg_compras['pve_fechamov']."\",".$reg_compras['pve_suc']."\r\n";
		fwrite($Compras_file, $reg);
	}

	fclose($Compras_file);

	convertir_CSV_XLS( $Compras_file, 'Compras.xls');
	
	$body = "Estimado David: \r\n";
	$body .= "Saludos y buen día.\r\n";
	$body .= "En funcion al nuevo proceso para agilizar los Pedidos Especiales de las Sucursales, se adjunta un archivo con los datos de los productos que habra que gestionar ante proveedor.\r\n";
	$body .= "Muchas gracias por la gestión.\r\n";
	$body .= "Saludos\r\n";

	$mail->Body = $body;

    $mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN COMPRARSE PARA RESOLVER PVE";
	$mail->AddAddress ( $MAIL_DMEDINA );
	$mail->AddAddress ( $MAILSAMMY );
	$mail->AddBCC ( $MAILTEST );
	$mail->AddAttachment ( $DIRHOME . 'Compras.xls', 'Compras.xls' );

	$mail->Send ();

	unlink ( $DIRHOME . 'Compras.xls' );

	mysqli_free_result ( $Pve_compras );
}

//Empiezo a enviar Mail a Sucursales
for( $a = 1 ; $a <= 12; $a++ )
{
	$mail->clearAttachments();

 	switch ($a) 
  	{
		case 1:
			$Query_Pve_Suc_1 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 1;";
			
			$Pve_suc_1 = mysqli_query($enlace, $Query_Pve_Suc_1);
			echo mysqli_num_rows($Pve_suc_1) . "\n";

			if (mysqli_num_rows($Pve_suc_1)>0)
			{
				$body = "";

				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc1_file = fopen('Suc1.csv',"w");
				fwrite($Suc1_file, $Datos);
			
				while ($reg_suc_1 = mysqli_fetch_array ($Pve_suc_1))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg1 = $reg_suc_1['cantidad'].",".$reg_suc_1['prd_id'].",\"".$reg_suc_1['prd_codalfa']."\",\"".$reg_suc_1['prd_detanterior']."\"\r\n";
					fwrite($Suc1_file, $reg1);
				}
			
				fclose($Suc1_file);
				

				$body = "Estimado " .$ENCARGADO_CC. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CC";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc1.csv', 'Suc1.csv' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc1.csv' );
				mysqli_free_result ( $Pve_suc_1 );
			
			}
	 	break;

	 	case 2:
			$Query_Pve_Suc_2 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 2;";
			
			$Pve_suc_2 = mysqli_query($enlace, $Query_Pve_Suc_2);

			echo mysqli_num_rows($Pve_suc_2) ."\n";
			
			if (mysqli_num_rows($Pve_suc_2) > 0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc2_file = fopen('Suc2.csv',"w");
				fwrite($Suc2_file, $Datos);
			
				while ($reg_suc_2 = mysqli_fetch_array ($Pve_suc_2))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg2 = $reg_suc_2['cantidad'].",".$reg_suc_2['prd_id'].",\"".$reg_suc_2['prd_codalfa']."\",\"".$reg_suc_2['prd_detanterior']."\"\r\n";
					fwrite($Suc2_file, $reg2);
				}
			
				fclose($Suc2_file);
			
				$body = "Estimado " .$ENCARGADO_SGO. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - SGO";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc2.csv', 'Suc2.csv' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc2.csv' );
				mysqli_free_result ( $Pve_suc_2 );
			
			}
		break;

		case 3:
			$Query_Pve_Suc_3 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 3;";
			
			if (mysqli_num_rows($Pve_suc_3 = mysqli_query($enlace, $Query_Pve_Suc_3))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc3_file = fopen('Suc3.csv',"w");
				fwrite($Suc3_file, $Datos);
			
				while ($reg_suc_3 = mysqli_fetch_array ($Pve_suc_3))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg3 = $reg_suc_3['cantidad'].",".$reg_suc_3['prd_id'].",\"".$reg_suc_3['prd_codalfa']."\",\"".$reg_suc_3['prd_detanterior']."\"\r\n";
					fwrite($Suc3_file, $reg3);
				}
			
				fclose($Suc3_file);
			
				$body = "Estimado " .$ENCARGADO_JUJUY. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - JUJUY";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc3.csv', 'Suc3.csv' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc3.csv' );
			
			}	

		break;
	
		case 5:
			$Query_Pve_Suc_5 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 5;";
			
			if (mysqli_num_rows($Pve_suc_5 = mysqli_query($enlace, $Query_Pve_Suc_5))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc5_file = fopen('Suc5.csv',"w");
				fwrite($Suc5_file, $Datos);
			
				while ($reg_suc_5 = mysqli_fetch_array ($Pve_suc_5))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg5 = $reg_suc_5['cantidad'].",".$reg_suc_5['prd_id'].",\"".$reg_suc_5['prd_codalfa']."\",\"".$reg_suc_5['prd_detanterior']."\"\r\n";
					fwrite($Suc5_file, $reg5);
				}
			
				fclose($Suc5_file);
			
				$body = "Estimado " .$ENCARGADO_CONC. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CONCEPCION";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc5.csv', 'Suc5.csv' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc5.csv' );
				mysqli_free_result ( $Pve_suc_5 );
			}

		break;

		case 6:
			$Query_Pve_Suc_6 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 6;";

			if (mysqli_num_rows($Pve_suc_6 = mysqli_query($enlace, $Query_Pve_Suc_6))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc6_file = fopen('Suc6.csv',"w");
				fwrite($Suc6_file, $Datos);

				while ($reg_suc_6 = mysqli_fetch_array ($Pve_suc_6))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg6 = $reg_suc_6['cantidad'].",".$reg_suc_6['prd_id'].",\"".$reg_suc_6['prd_codalfa']."\",\"".$reg_suc_6['prd_detanterior']."\"\r\n";
					fwrite($Suc6_file, $reg6);
				}

				fclose($Suc6_file);

				$body = "Estimado " .$ENCARGADO_BRS. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - BR SALI";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc6.csv', 'Suc6.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc6.csv' );
				mysqli_free_result ( $Pve_suc_6 );
			}
		break;
		
		case 7:
			$Query_Pve_Suc_7 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 7;";

			if (mysqli_num_rows($Pve_suc_7 = mysqli_query($enlace, $Query_Pve_Suc_7))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc7_file = fopen('Suc7.csv',"w");
				fwrite($Suc7_file, $Datos);

				while ($reg_suc_7 = mysqli_fetch_array ($Pve_suc_7))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg7 = $reg_suc_7['cantidad'].",".$reg_suc_7['prd_id'].",\"".$reg_suc_7['prd_codalfa']."\",\"".$reg_suc_7['prd_detanterior']."\"\r\n";
					fwrite($Suc7_file, $reg6);
				}

				fclose($Suc7_file);

				$body = "Estimado " .$ENCARGADO_LBS. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - LA BANDA -SGO";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc7.csv', 'Suc7.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc7.csv' );
				mysqli_free_result ( $Pve_suc_7 );
			}
		
 		break;

		case 8:
			$Query_Pve_Suc_8 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 8;";

			if (mysqli_num_rows($Pve_suc_8 = mysqli_query($enlace, $Query_Pve_Suc_8))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc8_file = fopen('Suc8.csv',"w");
				fwrite($Suc8_file, $Datos);

				while ($reg_suc_8 = mysqli_fetch_array ($Pve_suc_8))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg8 = $reg_suc_8['cantidad'].",".$reg_suc_8['prd_id'].",\"".$reg_suc_8['prd_codalfa']."\",\"".$reg_suc_8['prd_detanterior']."\"\r\n";
					fwrite($Suc8_file, $reg8);
				}

				fclose($Suc8_file);

				$body = "Estimado " .$ENCARGADO_MDZA. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - MENDOZA";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc8.csv', 'Suc8.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc8.csv' );
				mysqli_free_result ( $Pve_suc_8 );
			}
 		break;

		case 9:
			$Query_Pve_Suc_9 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 9;";

			if (mysqli_num_rows($Pve_suc_9 = mysqli_query($enlace, $Query_Pve_Suc_9))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc9_file = fopen('Suc9.csv',"w");
				fwrite($Suc9_file, $Datos);

				while ($reg_suc_9 = mysqli_fetch_array ($Pve_suc_9))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg9 = $reg_suc_9['cantidad'].",".$reg_suc_9['prd_id'].",\"".$reg_suc_9['prd_codalfa']."\",\"".$reg_suc_9['prd_detanterior']."\"\r\n";
					fwrite($Suc9_file, $reg9);
				}

				fclose($Suc9_file);

				$body = "Estimado " .$ENCARGADO_GA. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - GA VENTAS";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc9.csv', 'Suc9.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc9.csv' );
				mysqli_free_result ( $Pve_suc_9 );

			}
 		break;
 		case 10:
			$Query_Pve_Suc_10 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 10;";

			if (mysqli_num_rows($Pve_suc_10 = mysqli_query($enlace, $Query_Pve_Suc_10))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc10_file = fopen('Suc10.csv',"w");
				fwrite($Suc10_file, $Datos);

				while ($reg_suc_10 = mysqli_fetch_array ($Pve_suc_10))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg10 = $reg_suc_10['cantidad'].",".$reg_suc_10['prd_id'].",\"".$reg_suc_10['prd_codalfa']."\",\"".$reg_suc_10['prd_detanterior']."\"\r\n";
					fwrite($Suc10_file, $reg10);
				}

				fclose($Suc10_file);

				$body = "Estimado " .$ENCARGADO_JBJ. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - GA VENTAS";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc10.csv', 'Suc10.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc10.csv' );
				mysqli_free_result ( $Pve_suc_10 );
				
			}
 		break;

 		case 11:
			$Query_Pve_Suc_11 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 11;";

			if (mysqli_num_rows($Pve_suc_11 = mysqli_query($enlace, $Query_Pve_Suc_11))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc11_file = fopen('Suc11.csv',"w");
				fwrite($Suc11_file, $Datos);

				while ($reg_suc_11 = mysqli_fetch_array ($Pve_suc_11))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg11 = $reg_suc_11['cantidad'].",".$reg_suc_11['prd_id'].",\"".$reg_suc_11['prd_codalfa']."\",\"".$reg_suc_11['prd_detanterior']."\"\r\n";
					fwrite($Suc11_file, $reg11);
				}

				fclose($Suc11_file);

				$body = "Estimado " .$ENCARGADO_CAT. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CATAMARCA";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc11.csv', 'Suc11.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc11.csv' );
				mysqli_free_result ( $Pve_suc_11 );
			
			}
 		break;

 		case 12:
			$Query_Pve_Suc_12 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND pve.pve_suc = 12;";

			if (mysqli_num_rows($Pve_suc_12 = mysqli_query($enlace, $Query_Pve_Suc_12))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc12_file = fopen('Suc12.csv',"w");
				fwrite($Suc12_file, $Datos);

				while ($reg_suc_12 = mysqli_fetch_array ($Pve_suc_12))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg12 = $reg_suc_12['cantidad'].",".$reg_suc_12['prd_id'].",\"".$reg_suc_12['prd_codalfa']."\",\"".$reg_suc_12['prd_detanterior']."\"\r\n";
					fwrite($Suc12_file, $reg12);
				}

				fclose($Suc12_file);

				$body = "Estimado " .$ENCARGADO_SALTA. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo de Texto con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CATAMARCA";
				$mail->AddAddress ( $MAIL_DMEDINA );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc12.csv', 'Suc12.csv' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc12.csv' );
				mysqli_free_result ( $Pve_suc_12 );
			
			}
 		break;
	}
}

echo "Informes enviados ";

// // Borro los Archivos Generados
// unlink ( $DIRHOME . $ARCHIVOXLS );

/* cerrar la conexion */
mysqli_close ( $enlace );

?>