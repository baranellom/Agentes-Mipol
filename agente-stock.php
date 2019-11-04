#!/usr/bin/php

<?php
        echo "F/H Inicio: " . date('d/m/Y H:i:s');

	//Creo la variable con la consulta a la BD Local
        $consultalocal="CALL sp_mrpchequeastock;";

	//$consultalocal="select * from bco limit 10;";

	//Creas una variable de tipo objeto mysqli con los datos de la bd y el charset que quieras
        $mysql_loc = mysqli_connect('192.168.0.155', 'root','','fc');
	//var_dump($mysql_loc);

        //Chequeo que la conexion Local se haya establecido bien
        if(!$mysql_loc)
        {
                echo "No se ha podido establecer la conexion Local PHP - MySQL, verifique sus datos Locales.\r".PHP_EOL ;
                echo mysqli_connect_errno().PHP_EOL;
                echo mysqli_connect_error().PHP_EOL;
                exit();
        }
        else /*conexion local exitosa*/
        {
		$mysql_loc->set_charset("utf8");

		//Realizo la consulta a la Base de Datos Local
		if (mysqli_query($mysql_loc, $consultalocal) == TRUE) 
		{
		    mysqli_close($mysql_loc);
                    echo " Finalizado con exito.-";
                    echo " F/H Final: " . date('d/m/Y H:i:s');
                    exit();
		}
                else //Si la consulta local no devuelve resultados
                {
			mysqli_close($mysql_loc);
			echo " Conexion Existosa, pero no se ejecuto bien el SP.- ";
			echo " F/H Final: " . date('d/m/Y H:i:s');
			exit();
                }
        }

?>
