#!/usr/bin/php

<?php
	date_default_timezone_set('America/Argentina/Tucuman');
	$saludar = "Hola Mundo";
	echo $saludar."\n";
	echo date('d-m-Y ')."\n";
	echo date('Y-m-d')."\n";
	echo date('r')."\n";

	// function recursividad($a)
	// {
	//     if ($a < 20) {
	//         echo "$a\n";
	//         recursividad($a + 1);
	//     }
	// }
	//recursividad(8);

	// function convertir_CSV_XLS( $file_csv , $file_xls )
	// {
		// $extension = pathinfo( "D:/ProyectosVariosMipol/Agentes-Mipol/" . $file_csv, PATHINFO_EXTENSION);
		
		// echo $extension;
		
		// if( $extension == "csv" )
		// {
			//wite to file for XLS
			// include $DIRHOME . "/PHPExcel-1.8.2/Classes/PHPExcel/IOFactory.php";
				  
			// $objReader = PHPExcel_IOFactory::createReader("CSV");
			// $objReader->setDelimiter(',');
			// $objReader->setEnclosure('"');
			// $objReader->setLineEnding(0);
			
			// $objPHPExcel = $objReader->load($file_csv);
			// $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, "Excel5");
			// $objWriter->save($DIRHOME . $file_xls);
			$DIRHOME = "D:/Proyectos-Programacion/VisualStudioCode/Agentes-Mipol/";

			include ($DIRHOME . "phpmailer/class.phpmailer.php");
			include_once ($DIRHOME . "phpmailer/PHPMailerAutoload.php");
			require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel.php");
			require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Autoloader.php");
			require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/Excel2007.php");
			require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Style/Alignment.php");
			require_once ($DIRHOME . "PHPExcel-1.8.2/Classes/PHPExcel/Writer/CSV.php");
			
			include ($DIRHOME . 'PHPExcel-1.8.2/Classes/PHPExcel/IOFactory.php');

			$objReader = PHPExcel_IOFactory::createReader('CSV');

			// If the files uses a delimiter other than a comma (e.g. a tab), then tell the reader
			$objReader->setDelimiter(",");
			// If the files uses an encoding other than UTF-8 or ASCII, then tell the reader
			$objReader->setInputEncoding('UTF-8');

			$objPHPExcel = $objReader->load('Compras.csv');
			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
			$objWriter->save('MyExcelFile.xls');
			
			//}
	// };




?>
