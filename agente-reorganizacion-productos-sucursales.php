#!/usr/bin/php

<?php

// function recursividad($a)
// {
//     if ($a < 20) {
//         echo "$a\n";
//         recursividad($a + 1);
//     }
// }
//recursividad(8);


//$DIRHOME="/usr/share/Alertas/";
$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";
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

// Cant de productos disponibles con sus Depositos, en los que r_stock = 0 y no esten en OL, ni LP ni CE
$Consulta_inicial = "SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
IF(SUM(r_dpt_prd.r_stock) = 0, SUM(stock_mp.stock), 0) AS Stk_Libre, 
IF(SUM(r_dpt_prd.r_stock) >= 1, SUM((stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))), 0) AS 'Dif_Stk_Max', 
GROUP_CONCAT(r_dpt_prd.dpt_id) AS dep_suc, 
GROUP_CONCAT(stock_mp.stock) AS stock_suc_full,
GROUP_CONCAT(IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)))) AS stock_suc,
GROUP_CONCAT(CAST(r_dpt_prd.r_maximo AS SIGNED)) AS Max_Suc, 
GROUP_CONCAT(r_dpt_prd.r_stock ) AS r_stock_Suc,
prd.prd_clasificacion AS Clasificacion
FROM r_dpt_prd
INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
WHERE (
(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id NOT IN (9, 44, 45) /* Sin consultar en Depositos de OL, LP ni CE*/ 
AND FC_Division_Num(prd.fliaprd_id) NOT IN (763,744) /*Descarto Lubricantes y Filtros*/ ) 
OR 
(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id NOT IN (9, 44, 45) /* Sin consultar en Depositos de OL, LP ni CE*/ 
AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
AND FC_Division_Num(prd.fliaprd_id) NOT IN (763,744) /*Descarto Lubricantes y Filtros*/ ) 
)
GROUP BY r_dpt_prd.prd_id
ORDER BY 1;";

$i = 0;

include ($DIRHOME . "phpmailer/class.phpmailer.php");
include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
require_once ($DIRHOME . "CSVToExcelConverter.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");

$mail->PluginDir = $DIRHOME . "phpmailer/";

$Datos = "\"Prd_id\",\"CodAlfa\",\"Division\",\"Clasificacion\",\"Articulo\",\"StockSUC\",\"Fecha Ult Venta\"\r\n";

$Suc1= fopen('Suc1.csv',"w");
fwrite($Suc1,$Datos);
$Suc2= fopen('Suc2.csv',"w");
fwrite($Suc2,$Datos);
$Suc3= fopen('Suc3.csv',"w");
fwrite($Suc3,$Datos);
$Suc5= fopen('Suc5.csv',"w");
fwrite($Suc5,$Datos);
$Suc6= fopen('Suc6.csv',"w");
fwrite($Suc6,$Datos);
$Suc7= fopen('Suc7.csv',"w");
fwrite($Suc7,$Datos);
$Suc8= fopen('Suc8.csv',"w");
fwrite($Suc8,$Datos);
$Suc9= fopen('Suc9.csv',"w");
fwrite($Suc9,$Datos);
$Suc10= fopen('Suc10.csv',"w");
fwrite($Suc10,$Datos);
$Suc11= fopen('Suc11.csv',"w");
fwrite($Suc11,$Datos);
$Suc12= fopen('Suc12.csv',"w");
fwrite($Suc12,$Datos);

$Dep = array();
$SxS = array();

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
$Articulos_habilitados = mysqli_query ( $enlace, $Consulta_inicial );

#Calculamos los productos que estan disponibles para el envio si hacen falta, luego vemos si estos son utiles en el resto de las Suc's
while ($art_hab = mysqli_fetch_array($Articulos_habilitados)) 
{
	$ConsultaSQL = "SELECT r_dpt_prd.prd_id, r_dpt_prd.dpt_id, stock_mp.suc_id, stock_mp.stock, (CAST(r_dpt_prd.r_maximo AS SIGNED) - stock_mp.stock) AS Necesidad, stock_OL.stock AS Stock_OL, 
	IF((stock_OL.stock - (CAST(r_dpt_prd.r_maximo AS SIGNED) - stock_mp.stock)) > 1, 'No', 'Si') AS Enviar,  CAST(r_dpt_prd.r_minimo AS SIGNED) AS Minimo, 
	CAST(r_dpt_prd.r_maximo AS SIGNED) AS Maximo 
	FROM r_dpt_prd INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id 
	INNER JOIN stock_mp stock_OL ON stock_OL.prd_id = r_dpt_prd.prd_id AND stock_OL.dpt_id = 9 
	WHERE r_dpt_prd.r_stock = 1 AND (stock_mp.stock - CAST(r_dpt_prd.r_minimo AS SIGNED) <= 0) AND r_dpt_prd.dpt_id NOT IN (9,44,45,".$art_hab['dep_suc'].") AND r_dpt_prd.prd_id = ".$art_hab['prd_id']." ORDER BY 1;";

	//echo $ConsultaSQL . "\n";

	$Mover_stock = mysqli_query ( $enlace, $ConsultaSQL );

	$Mov = mysqli_fetch_array($Mover_stock);
	
	//printf( $Mov["Enviar"] . "\n");
	
	if ($Mov["Enviar"] === "Si") 
	{
		// Analizo los depositos en los que hay mercaderia y cargo array con valores de prd, alfa, y Cantidad para luego insertarlo en un mail
		
		//Convierto string en arrays de depositos
		$Dep = explode(",", $art_hab["dep_suc"]);
		print_r($Dep);

		//Convierto string en arrays Stock x Suc
		$SxS = explode(",", $art_hab["stock_suc"]);
		print_r($SxS);

		//echo count($Dep) ."\n";

		// ciclo que depende de la cantidad de Sucursales devueltas en deps
		for( $a = 1 ; $a <= count($Dep); $a++ )
        {
			switch ($Dep[$a-1]) 
			{
				case 1:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (1,9001) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");

						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea1 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc1,$linea1);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}
						mysqli_free_result ( $fechauv );
				break;

				case 2:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (2,9002) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);
						
						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");


						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";	
							$linea2 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc2,$linea2);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}
						mysqli_free_result ( $fechauv );
				break;

				case 3:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (3,9003) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");

						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea3 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc3,$linea3);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}
						mysqli_free_result ( $fechauv );
				break;

				case 5:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (5,9005) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");
				
						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea5 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc5,$linea5);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;

				case 6:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (6,9006) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );
						
						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");

						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea6 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc6,$linea6);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;

				case 7:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (7,9007) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");
				
						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea7 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc7,$linea7);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;

				case 8:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (8,9008) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");

						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea8 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc8,$linea8);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );

				case 46:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (9,9009) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");
				
						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea9 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc9,$linea9);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;
				
				case 30:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (10,9010) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);

						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");

						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea10 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc10,$linea10);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;

				case 36:
						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (11,9011) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);
						
						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						
						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");
		
						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea11 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc11,$linea11);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;

				case 40:

						$fechaultimaventa = "SELECT date_format(max(c.cpbvta_fecha),'%d/%m/%Y') as fuv
						FROM cpbvta c 
						inner join detcpbvta d on c.cpbvta_id = d.cpbvta_id and c.cpbvta_suc = d.cpbvta_suc 
						inner join prd on d.Prd_id = prd.prd_id
						where c.cpbvta_tipocpb <=3 AND ((c.cpbvta_fecanul IS NULL) OR (c.cpbvta_fecanul='0000-00-00')) 
						AND prd.prd_id <> 0 AND prd.prd_id = ".$art_hab['prd_id']." AND c.cpbvta_suc IN (12,9012) 
						group by d.Prd_id, if( c.cpbvta_suc < 9000, c.cpbvta_suc, c.cpbvta_suc - 9000);";

						$fechauv = mysqli_query ( $enlace, $fechaultimaventa );

						$fuv = mysqli_fetch_array($fechauv);
						
						if ($fuv['fuv'] != "")
						{
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}
						else
						{
							$fuv['fuv'] = "00/00/9999";
							printf("Fecha Ultima Venta: ". $fuv['fuv']."\r\n");
						}

						$cad_fuv= explode("/", $fuv['fuv']);
						
						// echo "Fecha Ultima Venta: ";
						// print_r($cad_fuv);
						// printf( "Año". $cad_fuv[2]);

						printf($art_hab['Clasificacion']."\r\n");
						printf($art_hab['prd_id']."\r\n");

						if (($art_hab['Clasificacion'] <> 'N') && ($cad_fuv[2] < 2019))
						{
							echo "Ingresa en el if \n";
							$linea12 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
							fwrite($Suc12,$linea12);
						}
						else
						{
							echo "No ingresa en el if \n";	
						}

						mysqli_free_result ( $fechauv );
				break;
			}
		}
	}
}

/* liberar el conjunto de resultados de Stock Negativos*/
mysqli_free_result ( $Articulos_habilitados );

fclose($Suc1);
fclose($Suc2);
fclose($Suc3);
fclose($Suc5);
fclose($Suc6);
fclose($Suc7);
fclose($Suc8);
fclose($Suc9);
fclose($Suc10);
fclose($Suc11);
fclose($Suc12);

$mail = new PHPMailer ( true );

$mail->SetLanguage('es', $DIRHOME . 'phpmailer/language/');

$mail->IsSMTP ();

// Activa la condificacción utf-8
$mail->CharSet = 'UTF-8';

$mail->SMTPAuth = true;

$mail->SMTPDebug = 2;

$mail->Host = "mailen3.cloudsector.net";

$mail->Port = 587;

$mail->Username = "sistema@mipolrepuestos.com";

$mail->Password = "Abc_1234";

$mail->SetFrom ( $MAILSISTEMA );

$mail->FromName = "Sammy Moreno";

$body = "Estimado Encargado\r\n";
$body .= "Saludos y buen día.\r\n";
$body .= "Adjunto encontraran un ARCHIVO con el detalle de los productos que deberan ser enviados al Deposito OL.\r\n";
$body .= "La información recibida le permitirá realizar una gestión sobre los productos que son enviados a su sucursal.\r\n";
$body .= "Por cualquier duda o comentarios, por favor, contactar con Marcelo Matías, quien está a cargo del área de logística y distribución de la organización.\r\n";
$body .= "Estamos atentos a sus comentarios\r\n";
$body .= "Saludos\r\n";
		
$mail->Body = $body;

for( $a = 1 ; $a <= 12; $a++ )
{
	$mail->clearAttachments();
	switch ($a) 
	{
		case 1:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc1.csv', $DIRHOME . 'Suc1.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN REENVIAR A OL DESDE SUC CASA CENTRAL";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc1.xlsx', 'Suc1.xlsx' );
		break;

		case 2:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc2.csv', $DIRHOME . 'Suc2.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN REENVIAR A OL DESDE SUC SANTIAGO DEL ESTERO";		
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc2.xlsx', 'Suc2.xlsx' );
		break;

		case 3:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc3.csv', $DIRHOME . 'Suc3.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN REENVIAR A OL DESDE SUC JUJUY";		
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc3.xlsx', 'Suc3.xlsx' );
		break;

		case 5:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc5.csv', $DIRHOME . 'Suc5.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC CONCEPCION";		
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc5.xlsx', 'Suc5.xlsx' );
		break;

		case 6:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc6.csv', $DIRHOME . 'Suc6.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC BR SALI";		
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc6.xlsx', 'Suc6.xlsx' );
		break;

		case 7:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc7.csv', $DIRHOME . 'Suc7.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC LA BANDA DE SGO";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc7.xlsx', 'Suc7.xlsx' );
		break;

		case 8:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc8.csv', $DIRHOME . 'Suc8.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC MENDOZA";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc8.xlsx', 'Suc8.xlsx' );
		break;

		case 9:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc9.csv', $DIRHOME . 'Suc9.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC GA";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc9.xlsx', 'Suc9.xlsx' );
		break;
		case 10:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc10.csv', $DIRHOME . 'Suc10.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC JB JUSTO";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc10.xlsx', 'Suc10.xlsx' );
		break;

		case 11:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc11.csv', $DIRHOME . 'Suc11.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC CATAMARCA";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc11.xlsx', 'Suc11.xlsx' );
		break;

		case 12:
				CSVToExcelConverter::convert( $DIRHOME . 'Suc12.csv', $DIRHOME . 'Suc12.xlsx');
				$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN RENVIAR A OL DESDE SUC SALTA";
				//$mail->AddAddress ( $MAIL_WSANCHEZ );
				$mail->AddAddress ( $MAILSAMMY );
				$mail->AddBCC ( $MAILTEST );
				$mail->AddAttachment ( $DIRHOME . 'Suc12.xlsx', 'Suc12.xlsx' );
		break;
	}
	$mail->Send ();
}


echo "Informes enviados ";

// // Borro los Archivos Generados
// unlink ( $DIRHOME . $ARCHIVOXLS );

/* cerrar la conexion */
mysqli_close ( $enlace );

?>