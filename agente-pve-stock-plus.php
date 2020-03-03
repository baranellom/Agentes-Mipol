<?php

#-- Linux 156
#$DIRHOME="/usr/share/Alertas/";
#-- Pc Oficina
$DIRHOME = "D:/ProyectosVariosMipol/Agentes-Mipol/";
#-- Pc de Casa
#$DIRHOME = "D:/Proyectos-Programacion/VisualStudioCode/Agentes-Mipol/";

include ($DIRHOME . "phpmailer/class.phpmailer.php");
include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
require_once ($DIRHOME . "CSVToExcelConverter.php");
require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
// require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");

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

$MAILSISTEMA = "sistema@mipolrepuestos.com";
$MAILTEST = "mbaranello@mipolrepuestos.com";
$MAILSAMMY = "sammy.moreno@microsolutions.cl";

#-- Agregar direcciones al arreglo en funcion a los destinatarios del canal de Compras
$MAILCOMPRAS = array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com");

 #-- Agregar direcciones al arreglo en funcion a los destinatarios de cada Sucursal
$MAILSUCURSALES = array (
    1 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","juanacarrizo@mipolrepuestos.com","rpaez@mipolrepuestos.com"),
    2 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mmarcucci@mipolrepuestos.com"),
    3 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","rramos@mipolrepuestos.com"),
    4 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mipolconcep@mipolrepuestos.com"),
    5 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mipolbrs@mipolrepuestos.com"),
    6 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mipol-labanda@mipolrepuestos.com"),
    7 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mipolmendoza@mipolrepuestos.com"),
    8 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mmoreno@grupo-autopartes.com.ar","cbarrientos@grupo-autopartes.com.ar"),
    9 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","mipoljbjusto@mipolrepuestos.com"),
    10 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","pgrosso@grupo-autopartes.com.ar"),
    11 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","ldiaz@grupo-autopartes.com.ar"),
    12 => array("fhoyos@mipolrepuestos.com","mdip@grupo-autopartes.com.ar","sammy.moreno@microsolutions.cl","dretondo@mipolrepuestos.com","wgallardo@mipolrepuestos.com","deposito-lospocitos@grupo-autopartes.com.ar")
);

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
$MAIL_WSANCHEZ = "wsanchez@grupo-autopartes.com.ar";
$MAIL_DMEDINA = "dmedina@mipolrepuestos.com";
$MAIL_JCARRIZO = "juanacarrizo@mipolrepuestos.com";
$MAIL_FHOYOS = "fhoyos@mipolrepuestos.com";
$MAIL_MDIP = "mdip@grupo-autopartes.com.ar";

$ENCARGADO_CC = "RAFAEL PAEZ";
$ENCARGADO_SGO = "MATIAS MARCUCCI";
$ENCARGADO_JUJUY = "MIGUEL MAIDANA";
$ENCARGADO_CONC = "IVAN GROSSO";
$ENCARGADO_BRS = "EDUARDO MEDRANO";
$ENCARGADO_LBS = "TRISTAN VITALE";
$ENCARGADO_MDZA = "HERNAN HERRERA";
$ENCARGADO_GA = "MARCELO MORENO";
$ENCARGADO_JBJ = "LEONARDO YMOLA";
$ENCARGADO_CAT = "PAUL TREJO";
$ENCARGADO_SALTA = "LEONARDO DIAZ";

$SUCURSAL_CC = "SUCURSAL CASA CENTRAL";
$SUCURSAL_SGO = "SUCURSAL SANTIAGO DEL ESTERO";
$SUCURSAL_JUJUY = "SUCURSAL JUJUY";
$SUCURSAL_CONC = "SUCURSAL CONCEPCION";
$SUCURSAL_BRS = "SUCURSAL BANDA DEL RIO SALI";
$SUCURSAL_LBS = "SUCURSAL LA BANDA";
$SUCURSAL_MDZA = "SUCURSAL MENDOZA";
$SUCURSAL_GA = "SUCURSAL GRUPO AUTOPARTES - VENTAS";
$SUCURSAL_JBJ = "SUCURSAL J.B. JUSTO";
$SUCURSAL_CAT = "SUCURSAL CATAMARCA";
$SUCURSAL_SALTA = "SUCURSAL SALTA";

function convertir_CSV_XLS( $file_csv , $file_xls )
{
    #$extension = pathinfo( "D:/ProyectosVariosMipol/Agentes-Mipol/" . $file_csv, PATHINFO_EXTENSION);
    $extension = pathinfo( $DIRHOME . $file_csv, PATHINFO_EXTENSION);
    
    echo $extension;
    
    if( $extension == "csv" )
    {
        //wite to file for XLS
        include $DIRHOME . "/PHPExcel-1.8.2/Classes/PHPExcel/IOFactory.php";
              
        $objReader = PHPExcel_IOFactory::createReader("CSV");
        $objReader->setDelimiter(',');
        $objReader->setEnclosure('"');
        $objReader->setLineEnding(0);
        
        $objPHPExcel = $objReader->load($file_csv);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
        $objWriter->save($DIRHOME . $file_xls);
        
        }
};

$mail = new PHPMailer ( true );

$mail->SetLanguage('es', $DIRHOME . 'phpmailer/language/');

$mail->IsSMTP ();

// Activa la condificacción utf-8
$mail->CharSet = 'UTF-8';

$mail->SMTPAuth = true;

$mail->SMTPDebug = 2;

$mail->Host = "mailen3.cloudsector.net";

$mail->Port = 587;

$mail->Username = "monitoreodesistemas@mipolrepuestos.com";

$mail->Password = "Abc$4321";

$mail->SetFrom ( $MAILSISTEMA );

$mail->FromName = "Servidor Linux de Mipol Repuestos SA";

?>