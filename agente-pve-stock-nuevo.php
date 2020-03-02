#!/usr/bin/php

<?php

#-- Linux 156
#$DIRHOME="/usr/share/Alertas/";
#-- Pc Oficina
#$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";
#-- Pc de Casa
#$DIRHOME = "D:/Proyectos-Programacion/VisualStudioCode/Agentes-Mipol/";

require_once 'agente-pve-stock-plus.php';

// $grupo_dep1 = "45,46";	//Dep Los Pocitos, Suc Autopartes
// $grupo_dep2 = "1,6,30";	//Dep CC, BRS, JBJ
// $grupo_dep3 = "5";		//Concep
// $grupo_dep4 = "2,7";	//Dep Sgo, LB
// $grupo_dep5 = "3,40";   //Dep Jujuy, Salta
// $grupo_dep6 = "8,36";   //Dep Catam, Mendoza

$Grupo_deps = array (1 => "45,46", 2 => "1,6,30", 3 => "5", 4 => "2,7", 5 => "3,40", 6 => "8,36");
$Sucursales = array (
    1 => array("suc_det" => "CASA CENTRAL", "suc_id" => 1, "dep_id" => 1, "encargado" => "RAFAEL PAEZ"),
    2 => array("suc_det" => "SANTIAGO", "suc_id" => 2, "dep_id" => 2, "encargado" => "MATIAS MARCUCCI"),
    3 => array("suc_det" => "JUJUY", "suc_id" => 3, "dep_id" => 3, "encargado" => "RODRIGO RAMOS"),
    4 => array("suc_det" => "CONCEPCION", "suc_id" => 5, "dep_id" => 5, "encargado" => "IVAN GROSSO"),
    5 => array("suc_det" => "BR SALI", "suc_id" => 6, "dep_id" => 6, "encargado" => "EDUARDO MEDRANO"),
    6 => array("suc_det" => "LA BANDA", "suc_id" => 7, "dep_id" => 7, "encargado" => "TRISTAN VITALE"),
    7 => array("suc_det" => "MENDOZA", "suc_id" => 8, "dep_id" => 8, "encargado" => "HERNAN HERRERA"),
    8 => array("suc_det" => "GA", "suc_id" => 9, "dep_id" => 46, "encargado" => "MARCELO MORENO"),
    9 => array("suc_det" => "JB JUSTO", "suc_id" => 10, "dep_id" => 30, "encargado" => "LEONARDO YMOLA"),
    10 => array("suc_det" => "CATAMARCA", "suc_id" => 11, "dep_id" => 36, "encargado" => "PAUL TREJO"),
    11 => array("suc_det" => "SALTA", "suc_id" => 12, "dep_id" => 40, "encargado" => "LEONARDO DIAZ"),
    12 => array("suc_det" => "LP", "suc_id" => 15, "dep_id" => 45, "encargado" => "WALTER CORONEL")
);

// print_r($Sucursales);
// print_r(count($Sucursales));
// print_r($Sucursales[12]['suc_id']);

// exit();

$resuelto = false;

//$Stocks = array (1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0);

$i = 0;

#-- Obtengo longitud de Arreglo
$cant_grupos = count($Grupo_deps);

// Consulta para obtener los productos marcados para comprar en los pedidos de Ventas de las Sucursales, 
// que no tengan un mail enviado y que no haya sido atendido por GA
$Consulta_inicial = "SELECT * FROM detpve WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail IS NULL;";

$Datos = "\"Prd_id\",\"CodAlfa\",\"Division\",\"Clasificacion\",\"Articulo\",\"StockSUC\",\"Fecha Ult Venta\"\r\n";

date_default_timezone_set('America/Argentina/Tucuman');

#$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );
$enlace = mysqli_connect ( "192.168.0.157", "root", "", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */
if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}
// Obtengo datos con la consulta anterior desde la Base de Datos
$Articulos_pve = mysqli_query ( $enlace, $Consulta_inicial );

#-- Con los productos obtenidos calculo Stock's disponibles en los Grupos de Sucursales definidos
while ($art_pve = mysqli_fetch_array($Articulos_pve)):

	#-- Seteo variable para terminar el bucle
    $resuelto = false;
    
    $l = 1;
    
    #-- Armo consultas para averiguar Stock en los distintos grupos y las guardo en una Variable Array $ConsultaStock_Suc_Grupo y $Stock_grupo
    while ($l <= $cant_grupos):
        
        $ConsultaStock_Suc_Grupo[$l] = "SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
        IF(r_dpt_prd.r_stock = 0, stock_mp.stock, 0) AS Stk_Libre, 
        IF(r_dpt_prd.r_stock >= 1, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)), 0) AS 'Dif_Stk_Max', 
        /*IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))) AS Stock_Total,*/
        stock_mp.stock AS Stock_Total,
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
        WHERE stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$Grupo_deps[$l].") AND prd.prd_id = ".$art_pve['prd_id']." ORDER BY 14 ASC;";

        #-- Ejecuto las consultas para los grupos, los cuales seran comparados luego, a los Stocks; los guardo en un arreglo
        $Stock_grupo[$l] = mysqli_query ( $enlace, $ConsultaStock_Suc_Grupo[$l] ); 

        $l++;
    endwhile;

    //print_r ( $ConsultaStock_Suc_Grupo);
    //print_r ( $Stock_grupo);

    $l = 1;

    #-- Consulto grupo x grupo para ver si puedo solicitar el articulo a algunas de nuestras Sucursales
    while (($resuelto == false) and ($l <= $cant_grupos)):
        
        while ($stock_g[$l] = mysqli_fetch_array($Stock_grupo[$l])) 
		{
			if ($stock_g[$l]['Stock_Total'] >= $art_pve['cantidad']) 
			{
				echo "Pido x mail a Suc " . $stock_g[$l]['Suc'].", ". $art_pve['cantidad'] ." unidad/es del producto ".$stock_g[$l]['prd_codalfa'].", ID = ".$stock_g[$l]['prd_id']. " \n";
				echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
				echo "Marco Producto PVE en tabla detpve como enviado a Suc \n";
                $Modificar_registro = "UPDATE detpve SET detpve_destmail='Sucursal', detpve_sucmail='" . $stock_g[$l]['Suc']."', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;". "\n";
				$Mod_reg = mysqli_query($enlace, $Modificar_registro);
                
                #-- Cambio Variable para salir del Ciclo While
				$resuelto = true;
				break;
			}
			else 
			{
				$resuelto = false;
			}	
        }
        
        $l++;

    endwhile;

    #-- Si al final el producto no es encontrado en los depositos de Sucursal, se lo enviara a Compras para que lo busquen
    if ($resuelto == false):
        echo "Pido a Proveedores, Envio mail a Facundo x el producto id = ".$art_pve['prd_id']." \n";
        echo "Este producto es necesitado por la Suc ". $art_pve['pve_suc']. "\n";
        echo "Marco Producto PVE en tabla detpve como enviado a Compras \n";
        $Modificar_registro = "UPDATE detpve SET detpve_destmail='Compras', detpve_sucmail='', detpve_cant_solicitada='". $art_pve['cantidad'] ."', detpve_mailenviado='0' WHERE pve_id=". $art_pve['pve_id'] ." AND pve_suc=". $art_pve['pve_suc'] ." AND prd_id=". $art_pve['prd_id'] ." LIMIT 1;". "\n";
        $Mod_reg = mysqli_query($enlace, $Modificar_registro);

	endif;
	
endwhile;

//exit();

#-- Limpio variables para el envio de mails
$mail->clearAttachments();
$mail->clearBCCs();
$mail->clearCCs();
$mail->clearAddresses();
$mail->Body = "";
$mail->Subject = "";

#-- Vuelvo a Consultar los productos que fueron marcados para enviarse a Compras
$Query_Pve_compras = "SELECT * FROM pve 
INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
INNER JOIN prd ON prd.prd_id = detpve.prd_id
LEFT JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Compras' 
AND ((detpve.detpve_mailenviado = 0) OR (detpve.detpve_mailenviado IS NULL));";

//Envio de Art para solicitar al proveedor via Compras
if (mysqli_num_rows($Pve_compras = mysqli_query($enlace, $Query_Pve_compras)) > 0)
{
	$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\",\"Pedido N\",\"Cliente\",\"Fecha\",\"Suc\"\r\n";
	$Compras_file= fopen($DIRHOME.'Compras.csv',"w");
	fwrite($Compras_file, $Datos);

	while ($reg_compras = mysqli_fetch_array ($Pve_compras))
	{
		//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\",\"Pedido N\",\"Cliente\",\"Fecha\",\"Suc\"\r\n";
		$reg = $reg_compras['cantidad'].",".$reg_compras['prd_id'].",\"".$reg_compras['prd_codalfa']."\",\"".$reg_compras['prd_detanterior']."\",\"".$reg_compras['cpbvta_nro']."\",\"".$reg_compras['pve_detclt']."\",\"".$reg_compras['pve_fechamov']."\",".$reg_compras['pve_suc']."\r\n";
		fwrite($Compras_file, $reg);
		
		$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_compras['pve_id'] ." AND pve_suc=". $reg_compras['pve_suc'] ." AND prd_id=". $reg_compras['prd_id'] .";";
		$Mod_reg = mysqli_query($enlace, $Modificar_reg);
	}

	fclose($Compras_file);

	CSVToExcelConverter::convert( $DIRHOME . 'Compras.csv', $DIRHOME . 'Compras.xlsx');

	$body = "Estimado Marcelo: \r\n";
	$body .= "Saludos y buen día.\r\n";
	$body .= "En función al nuevo proceso para agilizar los Pedidos Especiales de las Sucursales, se adjunta un archivo con los datos de los productos que habrá que gestionar ante proveedor.\r\n";
	$body .= "Muchas gracias por la gestión.\r\n";
	$body .= "Saludos\r\n";

	$mail->Body = $body;

	$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN COMPRARSE PARA RESOLVER PVE";

	for($x = 0; $x < count($MAILCOMPRAS); $x++) 
	{
		$mail->addAddress($MAILCOMPRAS[$x]);
	}
	
	$mail->AddBCC($MAILTEST);

	$mail->AddAttachment( $DIRHOME . 'Compras.xlsx', 'Compras.xlsx' );

	$mail->Send ();

	unlink ( $DIRHOME . 'Compras.xlsx' );
	unlink ( $DIRHOME . 'Compras.csv' );

	mysqli_free_result ( $Pve_compras );
}

$a = 1;

//Empiezo a enviar Mail a Sucursales para que repongan Articulos a GA
while ($a <= count($Sucursales)):

	$Query_Pve_Suc[$a] = "SELECT * FROM pve 
	INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
	INNER JOIN prd ON prd.prd_id = detpve.prd_id
	INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
	WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' 
	AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = ".$Sucursales[$a]['suc_id'].";";
	
	print_r($Query_Pve_Suc[$a]);

	#-- Consulto en base de datos
	$Pve_suc[$a] = mysqli_query($enlace, $Query_Pve_Suc[$a]);
	echo mysqli_num_rows($Pve_suc[$a]) . "\n";

	if (mysqli_num_rows($Pve_suc[$a])>0)
	{
		#-- Limpio Variables
		$mail->clearAttachments();
		$mail->clearBCCs();
		$mail->clearCCs();
		$mail->clearAddresses();
		$mail->Body = "";
		$mail->Subject = "";
		
		$body = "";
		
		#-- Encabezado del archivo csv
		$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
		#-- Abro archivo csv
		$Suc_file[$a] = fopen($DIRHOME.'Suc'.$Sucursales[$a]['suc_id'].'.csv',"w");
		#-- Guardo linea de Encabezado en archivo csv
		fwrite($Suc_file[$a], $Datos);

		#-- Armo variable con datos de la Tabla para el cuerpo del mensaje.
		$tabla = '<table border="1" cellpadding="2" cellspacing="0">';
		$tabla .= '<thead>';
		$tabla .= '<tr>';
		$tabla .= '<th> Cantidad </th>';
		$tabla .= '<th> Prd_id </th>';
		$tabla .= '<th> CodAlfa </th>';
		$tabla .= '<th> Articulo </th>';
		$tabla .= '</tr>';
		$tabla .= '</thead>';
		$tabla .= '<tbody>';

		while ($reg_suc[$a] = mysqli_fetch_array ($Pve_suc[$a]))
		{
			//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
			$reg[$a] = $reg_suc[$a]['cantidad'].",".$reg_suc[$a]['prd_id'].",\"".$reg_suc[$a]['prd_codalfa']."\",\"".$reg_suc[$a]['prd_detanterior']."\"\r\n";
			fwrite($Suc_file[$a], $reg[$a]);

			$tabla .= '<td>'. $reg_suc[$a]['cantidad']."</td><td>".$reg_suc[$a]['prd_id']."</td><td>".$reg_suc[$a]['prd_codalfa']."</td><td>".$reg_suc[$a]['prd_detanterior']."</td>";
			$tabla .= '</tr>';

			$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc[$a]['pve_id'] ." AND pve_suc=". $reg_suc[$a]['pve_suc'] ." AND prd_id=". $reg_suc[$a]['prd_id'] .";";
			$Mod_reg = mysqli_query($enlace, $Modificar_reg);
		}

		$tabla .= '</tbody></table>';

		fclose($Suc_file[$a]);
		
		CSVToExcelConverter::convert( $DIRHOME . 'Suc'.$Sucursales[$a]['suc_id'].'.csv', $DIRHOME . 'Suc'.$Sucursales[$a]['suc_id'].'.xlsx');

		$mail->addCustomHeader('X-custom-header: custom-value');

		//Content
		$mail->isHTML(true); 

		$body = "<p>Estimado <b>".$Sucursales[$a]['encargado']."</b></p>";
		$body .= "<p>Saludos y buen día.</p>";
		$body .= "<p>Se adjunta un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.</p>";
		$body .= "<p>Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad requerida de los articulos solicitados para resolver los pedidos pendientes.</p>";
		$body .= "<p>Muchas gracias por su gestión.</p>";
		$body .= "<p>Saludos</p>";

		$mensaje = '<html>'.
		'<head><title>LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE - '.$Sucursales[$a]['suc_det'].'</title></head>'.
		'<body><h1>Listado de Productos Solicitados</h1>'.
		$body.
		'<hr>'.
		'<br>'.
		$tabla.
		'</body>'.
		'</html>';
		
		$mail->Body = $mensaje;
	
		$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - ".$Sucursales[$a]['suc_det'];

		for($x = 0; $x < count($MAILSUCURSALES[$a]); $x++) 
	{
		$mail->addAddress($MAILSUCURSALES[$a][$x]);
	}

		$mail->AddBCC($MAILTEST);

		$mail->ConfirmReadingTo = "baranellom@gmail.com";

		$mail->AddAttachment ( $DIRHOME . 'Suc'.$Sucursales[$a]['suc_id'].'.xlsx', 'Suc'.$Sucursales[$a]['suc_id'].'.xlsx' );
	
		$mail->Send ();
	
		unlink ( $DIRHOME . 'Suc'.$Sucursales[$a]['suc_id'].'.csv' );
		unlink ( $DIRHOME . 'Suc'.$Sucursales[$a]['suc_id'].'.xlsx');

		mysqli_free_result ( $Pve_suc[$a] );
	} 	

	$a++;
endwhile;

echo "Informes enviados ";

// // Borro los Archivos Generados
// unlink ( $DIRHOME . $ARCHIVOXLS );

/* cerrar la conexion */
mysqli_close ( $enlace );

?>