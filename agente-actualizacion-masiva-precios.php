#!/usr/bin/php

<?php

// var_dump($argv);
// array(4) {
// 	[0]=>
// 	string(10) "script.php"
// 	[1]=>
// 	string(4) "arg1"
// 	[2]=>
// 	string(4) "arg2"
// 	[3]=>
// 	string(4) "arg3"
//   }

//$DIRHOME="/usr/share/Alertas/";
$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";
$ARCHIVOSQL = "Sql_Update.sql";
$MAILSISTEMA = "sistema@mipolrepuestos.com";
$MAILTEST = "mbaranello@mipolrepuestos.com";
$MAILSAMMY = "sammy.moreno@microsolutions.cl";

if ($argv[1] == "")
{
	$Coeficiente = '1.08';
	echo "No tiene argumentos. Coeficiente de aumento : ".$Coeficiente."\n";
}
else
{
	$Coeficiente = $argv[1];
	echo "Tiene argumentos. El coeficiente de aumento sera de : " . $Coeficiente . "\n";
}

include ($DIRHOME . "phpmailer/class.phpmailer.php");
include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");

//$mail->PluginDir = $DIRHOME . "phpmailer/";

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Actualizacion..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */

if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}

$Lineas_Sql= fopen('Sql_Update.sql',"w");

//$Consulta_Productos= "select distinct prd.prd_id from prd inner join preciovta on prd.prd_id = preciovta.prd_id WHERE prd.prd_id <> 0 order by prd.prd_id ;";

//$Consulta_Productos= "select distinct prd.prd_id from prd inner join preciovta on prd.prd_id = preciovta.prd_id WHERE prd.prd_id <> 0 and prd.marcaproducto_id IN (84, 106, 28, 29, 16, 531, 510, 120, 100055, 100341) ORDER by prd.prd_id ;";

$Consulta_Productos= "select distinct prd.prd_id from prd inner join preciovta on prd.prd_id = preciovta.prd_id WHERE prd.prd_id <> 0 and prd.marcaproducto_id IN (130) ORDER by prd.prd_id ;";

$Productos = mysqli_query ( $enlace, $Consulta_Productos );


//Obtengo en Maximo id de la tabla de precios
$ConsultaSQL2 = "SELECT MAX(preciovta_id) as id FROM preciovta;";

//Ahora obtengo el Maximo Id de la Tabla preciovta para incrementarlo en 1 y grabar nuevo registro
$Id_Maximo = mysqli_query ( $enlace, $ConsultaSQL2 );
$max_id = mysqli_fetch_array($Id_Maximo);
$id_siguiente = $max_id['id'];

//$id_siguiente = 15835873;

//PARA CADA PRODUCTO CONSULTO SU PRECIO ACTUAL, Y OBTENGO LOS DATOS PARA GRABAR NUEVO REGISTRO EN PRECIOVTA
while ($prd = mysqli_fetch_array($Productos)) {
	
	$ConsultaSQL = "Select preciovta_id, 
	preciovta_precio, 
	prd_id, 
	preciovta_vigencia, 
	preciovta_mayorista, 
	preciovta_minorista, 
	preciovta_asociados, 
	costo, 
	condicionesdescuentos, 
	preciovta_lote, 
	preciovta_vigenciahasta, 
	preciovta_marca, 
	preciovta_usuario 
	from preciovta 
	where prd_id=".$prd['prd_id']." 
	order by preciovta_id desc LIMIT 1;";

	//Obtengo en Maximo id de la tabla de precios
	//$ConsultaSQL2 = "SELECT MAX(preciovta_id) as id FROM preciovta;";

	if ($resultado = mysqli_query ( $enlace, $ConsultaSQL )) {
		
		$id_siguiente = $id_siguiente + 1;

		$preciovta = mysqli_fetch_array($resultado);
		
		//Ahora obtengo el Maximo Id de la Tabla preciovta para incrementarlo en 1 y grabar nuevo registro
		// $Id_Maximo = mysqli_query ( $enlace, $ConsultaSQL2 );
		// $max_id = mysqli_fetch_array($Id_Maximo);
		// $id_siguiente = $max_id['id'] + 1;

		$Consulta_agregar = "Insert into preciovta (preciovta_id,preciovta_precio,prd_id,preciovta_vigencia,preciovta_mayorista,preciovta_minorista,preciovta_asociados,costo,condicionesdescuentos,preciovta_usuario) 
		values (".$id_siguiente.",". number_format( $preciovta['preciovta_precio'] * $Coeficiente,4,'.','') .",". $preciovta['prd_id'] .",\"". date('Y-m-d') ."\",". $preciovta['preciovta_mayorista'] .",". $preciovta['preciovta_minorista'] .",". $preciovta['preciovta_asociados'] .",". number_format($preciovta['costo'] * $Coeficiente,4,'.','') .",\"". $preciovta['condicionesdescuentos'] ."\",". $preciovta['preciovta_usuario'] .")";

		$Insertar_tabla = mysqli_query ($enlace, $Consulta_agregar);

		$Consulta_agregar_log = "INSERT INTO log (sentencia,fecha,tabla,usuario_id,suc) values ('".$Consulta_agregar."',\"". date('Y-m-d') ."\",\"preciovta\",158,9);";

		$Insertar_log = mysqli_query ($enlace, $Consulta_agregar_log);

		// echo $Consulta_agregar . ";\n";
		// echo $Consulta_agregar_log . "\n";
		
		//$linea7 = $art_hab['prd_id'].','.$art_hab['prd_codalfa'].',"'.$art_hab['Division'].'",'.$art_hab['Clasificacion'].',"'.$art_hab['Detalle'].'",'.$SxS[$a-1].",\"".$fuv['fuv']."\"\r\n";
		fwrite($Lineas_Sql,$Consulta_agregar.";". "\r\n");
		fwrite($Lineas_Sql,$Consulta_agregar_log . "\r\n");

		/* liberar el conjunto de resultados */
		mysqli_free_result ( $resultado );
	}
	
}

fclose($Lineas_Sql);

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

// $mail->Subject = "ARCHIVO CON INSTRUCCIONES PARA ACTUALIZAR PRECIOS MASIVOS.";

// //$mail->AddAddress ( $MAILCC_RETONDO );
// //$mail->AddAddress ( $MAILSAMMY );
// $mail->AddBCC ( $MAILTEST );

// $body = "Saludos y buen día.\r\n";
// $body .= "Adjunto encontraran un archivo con las instrucciones SQL para actualizar precios en forma Masiv.\r\n";
// $body .= "Estamos atentos a sus comentarios\r\n";
// $body .= "Saludos\r\n";
			
// $mail->Body = $body;

// // adjuntamos los archivos

// $mail->AddAttachment ( $DIRHOME . $ARCHIVOSQL, $ARCHIVOSQL );

// $mail->Send ();

echo "Informes enviados." . date('r'). "\n";

// Borro los Archivos Generados
//unlink ( $DIRHOME . $ARCHIVOSQL );

mysqli_free_result ( $Productos );

/* cerrar la conexion */
mysqli_close ( $enlace );

?>