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
    "CC" => array("suc_id" => 1, "dep_id" => 1),
    "SGO" => array("suc_id" => 2, "dep_id" => 2),
    "JUJUY" => array("suc_id" => 3, "dep_id" => 3),
    "CONCEP" => array("suc_id" => 5, "dep_id" => 5),
    "BRS" => array("suc_id" => 6, "dep_id" => 6),
    "LB" => array("suc_id" => 7, "dep_id" => 7),
    "MEND" => array("suc_id" => 8, "dep_id" => 8),
    "GA" => array("suc_id" => 9, "dep_id" => 46),
    "JBJ" => array("suc_id" => 10, "dep_id" => 30),
    "CAT" => array("suc_id" => 11, "dep_id" => 36),
    "SALTA" => array("suc_id" => 12, "dep_id" => 40),
    "LP" => array("suc_id" => 15, "dep_id" => 45)
);

// print_r($Sucursales);
// print_r(count($Sucursales));
// print_r($Sucursales['LP']['suc_id']);

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

exit();

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
WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Compras' AND ((detpve.detpve_mailenviado = 0) OR (detpve.detpve_mailenviado IS NULL));";

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
	$body .= "En funcion al nuevo proceso para agilizar los Pedidos Especiales de las Sucursales, se adjunta un archivo con los datos de los productos que habra que gestionar ante proveedor.\r\n";
	$body .= "Muchas gracias por la gestión.\r\n";
	$body .= "Saludos\r\n";

	$mail->Body = $body;

	$mail->Subject = "LISTADO DE PRODUCTOS QUE DEBEN COMPRARSE PARA RESOLVER PVE";
	#$mail->addCC($MAILCC_RETONDO);
	#$mail->AddAddress($MAIL_FHOYOS);
	#$mail->AddAddress($MAIL_MDIP);
	#$mail->AddAddress($MAILSAMMY);
	$mail->AddBCC($MAILTEST);
	$mail->AddAttachment( $DIRHOME . 'Compras.xlsx', 'Compras.xlsx' );

	$mail->Send ();

	unlink ( $DIRHOME . 'Compras.xlsx' );
	unlink ( $DIRHOME . 'Compras.csv' );

	mysqli_free_result ( $Pve_compras );
}

// print_r(count($Sucursales)); 12
// print_r($Sucursales['LP']['suc_id']);

$a = 1;

//Empiezo a enviar Mail a Sucursales para que repongan Articulos a GA
while ($a <= count($Sucursales)):
//for( $a = 1 ; $a <= 12; $a++ )
{
	$mail->clearAttachments();
	$mail->clearBCCs();
	$mail->clearCCs();
	$mail->clearAddresses();
	$mail->Body = "";
	$mail->Subject = "";
    
 	switch ($a) 
  	{
		case 1:
			$Query_Pve_Suc_1 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 1;";
			
			$Pve_suc_1 = mysqli_query($enlace, $Query_Pve_Suc_1);
			echo mysqli_num_rows($Pve_suc_1) . "\n";

			if (mysqli_num_rows($Pve_suc_1)>0)
			{
				$body = "";
				
				#-- Encabezado del archivo csv
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc1_file = fopen($DIRHOME.'Suc1.csv',"w");
				fwrite($Suc1_file, $Datos);

				#-- Armo variable con datos de la Tabla para el cuerpo del mensaje.
				$tabla = '<table border="1">';
				$tabla .= '<thead>';
				$tabla .= '<tr>';
				$tabla .= '<th> Cantidad </th>';
				$tabla .= '<th> Prd_id </th>';
				$tabla .= '<th> CodAlfa </th>';
				$tabla .= '<th> Articulo </th>';
				$tabla .= '</tr>';
				$tabla .= '</thead>';
				$tabla .= '<tbody>';

				while ($reg_suc_1 = mysqli_fetch_array ($Pve_suc_1))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg1 = $reg_suc_1['cantidad'].",".$reg_suc_1['prd_id'].",\"".$reg_suc_1['prd_codalfa']."\",\"".$reg_suc_1['prd_detanterior']."\"\r\n";
					fwrite($Suc1_file, $reg1);

					$tabla .= '<td>'. $reg_suc_1['cantidad']."</td><td>".$reg_suc_1['prd_id']."</td><td>".$reg_suc_1['prd_codalfa']."</td><td>".$reg_suc_1['prd_detanterior']."</td>";
					$tabla .= '</tr>';

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_1['pve_id'] ." AND pve_suc=". $reg_suc_1['pve_suc'] ." AND prd_id=". $reg_suc_1['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				$tabla .= '</tbody></table>';

				fclose($Suc1_file);
				
				CSVToExcelConverter::convert( $DIRHOME . 'Suc1.csv', $DIRHOME . 'Suc1.xlsx');

				$mail->addCustomHeader('X-custom-header: custom-value');
    
    			//Content
    			$mail->isHTML(true); 

				$body = "<p>Estimado <b>" .$ENCARGADO_CC. "</b></p>";
				$body .= "<p>Saludos y buen día.</p>";
				$body .= "<p>Se adjunta un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.</p>";
				$body .= "<p>Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad requerida de los articulos solicitados para resolver los pedidos pendientes.</p>";
				$body .= "<p>Muchas gracias por su gestión.</p>";
				$body .= "<p>Saludos</p>";

				$mensaje = '<html>'.
				'<head><title>LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE - CC</title></head>'.
				'<body><h1>Listado de Productos Solicitados</h1>'.
				$body.
				'<hr>'.
				'<br>'.
				$tabla.
				'</body>'.
				'</html>';
				
				$mail->Body = $mensaje;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CC";
				/* $mail->AddAddress($MAILCC_RETONDO);
				$mail->AddAddress($MAIL_JCARRIZO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP); 
				$mail->AddBCC($MAILTEST);*/
				$mail->AddAddress($MAILTEST);
				$mail->AddCC($MAILSAMMY);

				$mail->ConfirmReadingTo = "baranellom@gmail.com";

				$mail->AddAttachment ( $DIRHOME . 'Suc1.xlsx', 'Suc1.xlsx' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc1.csv' );
				unlink ( $DIRHOME . 'Suc1.xlsx');

				mysqli_free_result ( $Pve_suc_1 );
			
			}
	 	break;

	 	case 2:
			$Query_Pve_Suc_2 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 2;";
			
			$Pve_suc_2 = mysqli_query($enlace, $Query_Pve_Suc_2);

			echo mysqli_num_rows($Pve_suc_2) ."\n";
			
			if (mysqli_num_rows($Pve_suc_2) > 0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc2_file = fopen($DIRHOME.'Suc2.csv',"w");
				fwrite($Suc2_file, $Datos);
			
				while ($reg_suc_2 = mysqli_fetch_array ($Pve_suc_2))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg2 = $reg_suc_2['cantidad'].",".$reg_suc_2['prd_id'].",\"".$reg_suc_2['prd_codalfa']."\",\"".$reg_suc_2['prd_detanterior']."\"\r\n";
					fwrite($Suc2_file, $reg2);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_2['pve_id'] ." AND pve_suc=". $reg_suc_2['pve_suc'] ." AND prd_id=". $reg_suc_2['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}
			
				fclose($Suc2_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc2.csv', $DIRHOME . 'Suc2.xlsx');
			
				$body = "Estimado " .$ENCARGADO_SGO. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - SGO";
				$mail->AddAddress($MAILSGO_MM);
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc2.xlsx', 'Suc2.xlsx' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc2.csv' );
				unlink ( $DIRHOME . 'Suc2.xlsx');

				mysqli_free_result ( $Pve_suc_2 );
			
			}
		break;

		case 3:
			$Query_Pve_Suc_3 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 3;";
			
			if (mysqli_num_rows($Pve_suc_3 = mysqli_query($enlace, $Query_Pve_Suc_3))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc3_file = fopen($DIRHOME.'Suc3.csv',"w");
				fwrite($Suc3_file, $Datos);
			
				while ($reg_suc_3 = mysqli_fetch_array ($Pve_suc_3))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg3 = $reg_suc_3['cantidad'].",".$reg_suc_3['prd_id'].",\"".$reg_suc_3['prd_codalfa']."\",\"".$reg_suc_3['prd_detanterior']."\"\r\n";
					fwrite($Suc3_file, $reg3);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_3['pve_id'] ." AND pve_suc=". $reg_suc_3['pve_suc'] ." AND prd_id=". $reg_suc_3['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}
			
				fclose($Suc3_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc3.csv', $DIRHOME . 'Suc3.xlsx');
			
				$body = "Estimado " .$ENCARGADO_JUJUY. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - JUJUY";
				$mail->AddAddress( $MAILJUJUY );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc3.xlsx', 'Suc3.xlsx' );
			
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc3.csv' );
				unlink ( $DIRHOME . 'Suc3.xlsx');
				
				mysqli_free_result ( $Pve_suc_3 );
			}	

		break;
	
		case 5:
			$Query_Pve_Suc_5 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 5;";
			
			if (mysqli_num_rows($Pve_suc_5 = mysqli_query($enlace, $Query_Pve_Suc_5))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc5_file = fopen($DIRHOME.'Suc5.csv',"w");
				fwrite($Suc5_file, $Datos);
			
				while ($reg_suc_5 = mysqli_fetch_array ($Pve_suc_5))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg5 = $reg_suc_5['cantidad'].",".$reg_suc_5['prd_id'].",\"".$reg_suc_5['prd_codalfa']."\",\"".$reg_suc_5['prd_detanterior']."\"\r\n";
					fwrite($Suc5_file, $reg5);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_5['pve_id'] ." AND pve_suc=". $reg_suc_5['pve_suc'] ." AND prd_id=". $reg_suc_5['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}
			
				fclose($Suc5_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc5.csv', $DIRHOME . 'Suc5.xlsx');
			
				$body = "Estimado " .$ENCARGADO_CONC. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;
			
				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CONCEPCION";
				$mail->AddAddress( $MAILCONCEPCION );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);
				$mail->AddAttachment ( $DIRHOME . 'Suc5.xlsx', 'Suc5.xlsx' );
				
				$mail->Send ();
			
				unlink ( $DIRHOME . 'Suc5.csv' );
				unlink ( $DIRHOME . 'Suc5.xlsx');

				mysqli_free_result ( $Pve_suc_5 );
			}

		break;

		case 6:
			$Query_Pve_Suc_6 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 6;";

			if (mysqli_num_rows($Pve_suc_6 = mysqli_query($enlace, $Query_Pve_Suc_6))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc6_file = fopen($DIRHOME.'Suc6.csv',"w");
				fwrite($Suc6_file, $Datos);

				while ($reg_suc_6 = mysqli_fetch_array ($Pve_suc_6))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg6 = $reg_suc_6['cantidad'].",".$reg_suc_6['prd_id'].",\"".$reg_suc_6['prd_codalfa']."\",\"".$reg_suc_6['prd_detanterior']."\"\r\n";
					fwrite($Suc6_file, $reg6);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_6['pve_id'] ." AND pve_suc=". $reg_suc_6['pve_suc'] ." AND prd_id=". $reg_suc_6['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc6_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc6.csv', $DIRHOME . 'Suc6.xlsx');

				$body = "Estimado " .$ENCARGADO_BRS. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - BR SALI";
				$mail->AddAddress($MAILBRS);
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc6.xlsx', 'Suc6.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc6.csv' );
				unlink ( $DIRHOME . 'Suc6.xlsx' );
				mysqli_free_result ( $Pve_suc_6 );
			}
		break;
		
		case 7:
			$Query_Pve_Suc_7 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 7;";

			if (mysqli_num_rows($Pve_suc_7 = mysqli_query($enlace, $Query_Pve_Suc_7))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc7_file = fopen($DIRHOME.'Suc7.csv',"w");
				fwrite($Suc7_file, $Datos);

				while ($reg_suc_7 = mysqli_fetch_array ($Pve_suc_7))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg7 = $reg_suc_7['cantidad'].",".$reg_suc_7['prd_id'].",\"".$reg_suc_7['prd_codalfa']."\",\"".$reg_suc_7['prd_detanterior']."\"\r\n";
					fwrite($Suc7_file, $reg7);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_7['pve_id'] ." AND pve_suc=". $reg_suc_7['pve_suc'] ." AND prd_id=". $reg_suc_7['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc7_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc7.csv', $DIRHOME . 'Suc7.xlsx');

				$body = "Estimado " .$ENCARGADO_LBS. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - LA BANDA -SGO";
				$mail->AddAddress( $MAILLB );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);
				
				$mail->AddAttachment ( $DIRHOME . 'Suc7.xlsx', 'Suc7.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc7.csv' );
				unlink ( $DIRHOME . 'Suc7.xlsx' );

				mysqli_free_result ( $Pve_suc_7 );
			}
		
 		break;

		case 8:
			$Query_Pve_Suc_8 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 8;";

			if (mysqli_num_rows($Pve_suc_8 = mysqli_query($enlace, $Query_Pve_Suc_8))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc8_file = fopen($DIRHOME.'Suc8.csv',"w");
				fwrite($Suc8_file, $Datos);

				while ($reg_suc_8 = mysqli_fetch_array ($Pve_suc_8))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg8 = $reg_suc_8['cantidad'].",".$reg_suc_8['prd_id'].",\"".$reg_suc_8['prd_codalfa']."\",\"".$reg_suc_8['prd_detanterior']."\"\r\n";
					fwrite($Suc8_file, $reg8);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_8['pve_id'] ." AND pve_suc=". $reg_suc_8['pve_suc'] ." AND prd_id=". $reg_suc_8['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc8_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc8.csv', $DIRHOME . 'Suc8.xlsx');

				$body = "Estimado " .$ENCARGADO_MDZA. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - MENDOZA";
				$mail->AddAddress( $MAILMENDONZA );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc8.xlsx', 'Suc8.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc8.csv' );
				unlink ( $DIRHOME . 'Suc8.xlsx' );
				mysqli_free_result ( $Pve_suc_8 );
			}
 		break;

		case 9:
			$Query_Pve_Suc_9 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 9;";

			if (mysqli_num_rows($Pve_suc_9 = mysqli_query($enlace, $Query_Pve_Suc_9))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc9_file = fopen($DIRHOME.'Suc9.csv',"w");
				fwrite($Suc9_file, $Datos);

				while ($reg_suc_9 = mysqli_fetch_array ($Pve_suc_9))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg9 = $reg_suc_9['cantidad'].",".$reg_suc_9['prd_id'].",\"".$reg_suc_9['prd_codalfa']."\",\"".$reg_suc_9['prd_detanterior']."\"\r\n";
					fwrite($Suc9_file, $reg9);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_9['pve_id'] ." AND pve_suc=". $reg_suc_9['pve_suc'] ." AND prd_id=". $reg_suc_9['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc9_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc9.csv', $DIRHOME . 'Suc9.xlsx');

				$body = "Estimado " .$ENCARGADO_GA. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - GA VENTAS";
				$mail->AddAddress($MAILMORENO );
				$mail->AddAddress($MAILBARRIENTOS);
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc9.xlsx', 'Suc9.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc9.csv' );
				unlink ( $DIRHOME . 'Suc9.xlsx' );
				mysqli_free_result ( $Pve_suc_9 );

			}
 		break;
 		case 10:
			$Query_Pve_Suc_10 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 10;";

			if (mysqli_num_rows($Pve_suc_10 = mysqli_query($enlace, $Query_Pve_Suc_10))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc10_file = fopen($DIRHOME.'Suc10.csv',"w");
				fwrite($Suc10_file, $Datos);

				while ($reg_suc_10 = mysqli_fetch_array ($Pve_suc_10))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg10 = $reg_suc_10['cantidad'].",".$reg_suc_10['prd_id'].",\"".$reg_suc_10['prd_codalfa']."\",\"".$reg_suc_10['prd_detanterior']."\"\r\n";
					fwrite($Suc10_file, $reg10);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_10['pve_id'] ." AND pve_suc=". $reg_suc_10['pve_suc'] ." AND prd_id=". $reg_suc_10['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc10_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc10.csv', $DIRHOME . 'Suc10.xlsx');

				$body = "Estimado " .$ENCARGADO_JBJ. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - JB JUSTO";
				$mail->AddAddress( $MAILJBJUSTO );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc10.xlsx', 'Suc10.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc10.csv' );
				unlink ( $DIRHOME . 'Suc10.xlsx' );

				mysqli_free_result ( $Pve_suc_10 );
				
			}
 		break;

 		case 11:
			$Query_Pve_Suc_11 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 11;";

			if (mysqli_num_rows($Pve_suc_11 = mysqli_query($enlace, $Query_Pve_Suc_11))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc11_file = fopen($DIRHOME.'Suc11.csv',"w");
				fwrite($Suc11_file, $Datos);

				while ($reg_suc_11 = mysqli_fetch_array ($Pve_suc_11))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg11 = $reg_suc_11['cantidad'].",".$reg_suc_11['prd_id'].",\"".$reg_suc_11['prd_codalfa']."\",\"".$reg_suc_11['prd_detanterior']."\"\r\n";
					fwrite($Suc11_file, $reg11);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_11['pve_id'] ." AND pve_suc=". $reg_suc_11['pve_suc'] ." AND prd_id=". $reg_suc_11['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc11_file);

				CSVToExcelConverter::convert( $DIRHOME . 'Suc11.csv', $DIRHOME . 'Suc11.xlsx');

				$body = "Estimado " .$ENCARGADO_CAT. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - CATAMARCA";
				$mail->AddAddress( $MAILCATAMARCA );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);

				$mail->AddAttachment ( $DIRHOME . 'Suc11.xlsx', 'Suc11.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc11.csv' );
				unlink ( $DIRHOME . 'Suc11.xlsx' );

				mysqli_free_result ( $Pve_suc_11 );
			
			}
 		break;

 		case 12:
			$Query_Pve_Suc_12 = "SELECT * FROM pve 
			INNER JOIN detpve ON pve.pve_id = detpve.pve_id AND pve.pve_suc = detpve.pve_suc
			INNER JOIN prd ON prd.prd_id = detpve.prd_id
			INNER JOIN pdvs ON pdvs.solpsuc_id = pve.pve_id AND pdvs.solpsuc_suc = pve.pve_suc
			WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail = 'Sucursal' AND detpve.detpve_mailenviado = 0 AND detpve.detpve_sucmail = 12;";

			if (mysqli_num_rows($Pve_suc_12 = mysqli_query($enlace, $Query_Pve_Suc_12))>0)
			{
				$Datos = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
				$Suc12_file = fopen($DIRHOME.'Suc12.csv',"w");
				fwrite($Suc12_file, $Datos);

				while ($reg_suc_12 = mysqli_fetch_array ($Pve_suc_12))
				{
					//$reg = "\"Cantidad\",\"Prd_id\",\"CodAlfa\",\"Articulo\"\r\n";
					$reg12 = $reg_suc_12['cantidad'].",".$reg_suc_12['prd_id'].",\"".$reg_suc_12['prd_codalfa']."\",\"".$reg_suc_12['prd_detanterior']."\"\r\n";
					fwrite($Suc12_file, $reg12);

					$Modificar_reg = "UPDATE detpve SET detpve_mailenviado=1 WHERE pve_id=". $reg_suc_12['pve_id'] ." AND pve_suc=". $reg_suc_12['pve_suc'] ." AND prd_id=". $reg_suc_12['prd_id'] .";";
					$Mod_reg = mysqli_query($enlace, $Modificar_reg);
				}

				fclose($Suc12_file);
				
				CSVToExcelConverter::convert( $DIRHOME . 'Suc12.csv', $DIRHOME . 'Suc12.xlsx');

				$body = "Estimado " .$ENCARGADO_SALTA. "\r\n";
				$body .= "Saludos y buen día.\r\n";
				//$body .= "En la sucursal ".$SUCURSAL_CC." con fecha de FECHA PEDIDO se ha realizado el pedido de venta NÚMERO DE PEDIDO por el CÓDIGO ALFA que usted tiene CANTIDAD en la sucursal y necesitamos enviarlo al cliente NOMBRE DEL CLIENTE a la brevedad.\r\n";
				$body .= "Se Adjunto un archivo con el/los productos que el Operador Logistico necesita para resolver los Pedidos de Ventas Especiales del resto de las Sucursales.\r\n";
				//$body .= "Una revisión de los últimos movimientos del producto CÓDIGO ALFA nos muestra que la Sucursal NOMBRE DE LA SUCURSAL es la que menos rotación tiene, la fecha del último movimiento fue FECHA ÚLTIMO MOVIMIENTO.\r\n";
				$body .= "Por favor, generar un remito a GRUPO AUTOPARTES OPERADOR LOGÍSTICO por la cantidad pedida anteriormente de los articulos solicitados para resolver los pedido pendientes.\r\n";
				$body .= "Muchas gracias por la gestión.\r\n";
				//$body .= "NOMBRE ENCARGADO SUCURSAL QUE PIDE EL PRODUCTO\r\n";
				$body .= "Saludos\r\n";

				$mail->Body = $body;

				$mail->Subject = "LISTADO DE PRODUCTOS SOLICITADOS PARA RESOLVER PVE'S - SALTA";
				$mail->AddAddress( $MAILSALTA );
				$mail->AddCC($MAILCC_RETONDO);
				$mail->AddCC($MAILSAMMY);
				$mail->AddAddress($MAIL_FHOYOS);
				$mail->AddAddress($MAIL_MDIP);
				$mail->AddBCC($MAILTEST);
				$mail->AddAttachment ( $DIRHOME . 'Suc12.xlsx', 'Suc12.xlsx' );

				$mail->Send ();

				unlink ( $DIRHOME . 'Suc12.csv' );
				unlink ( $DIRHOME . 'Suc12.xlsx' );

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
