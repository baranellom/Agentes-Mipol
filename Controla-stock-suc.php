#!/usr/bin/php

<?php
    echo "F/H Inicio: " . date('d/m/Y H:i:s');

    date_default_timezone_set('America/Argentina/Tucuman');

    $Suc = 10;
    $Dep = 30;

	## Consulto los productos que tienen Stock pero no estan dentro de la tabla Stock_mp
    $consultalocal="SELECT prd.prd_id AS prd_id, FC_Stock(prd.prd_id,".$Dep.") AS stock FROM prd LEFT JOIN stock_mp s ON prd.prd_id = s.prd_id AND s.dpt_id = ".$Dep." WHERE s.prd_id IS NULL AND prd.prd_id <> 0 HAVING stock > 0 ;";

	## Creas una variable de tipo objeto mysqli con los datos de la bd y el charset que quieras
    //$mysql_loc = mysqli_connect('192.168.0.155', 'root','','fc');
    $mysql_loc = mysqli_connect('127.0.0.1', 'root','','fc',43306);
    
    mysqli_query ( $mysql_loc, "SET NAMES 'utf8'");

    //var_dump($mysql_loc);
    //mysqli_close($mysql_loc);
    //exit();

    ## Chequeo que la conexion Local se haya establecido bien
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

		    ## Realizo la consulta a la Base de Datos Local, si obtengo datos los introduzco a la tabla Stock_mp
		    $prod = mysqli_query($mysql_loc, $consultalocal);
            
            $i = 0;

            if (mysqli_num_rows($prod) > 0)
                {
                    while ($p = mysqli_fetch_array($prod))
                        {
                            $consulta_insersion = "CALL actualizastock(".$p["prd_id"].",".$Dep.",".$p["stock"].",".$Suc.");";

                            $ingreso_datos = mysqli_query($mysql_loc, $consulta_insersion);

                            echo "Se introdujeron los Valores: prd_id = ".$p["prd_id"].", Deposito = ".$Dep.", Stock = ".$p["stock"].".";

                            $i++;

                        }
                }
        }

    mysqli_close($mysql_loc);
    echo " Finalizado con exito. Se ingresaron ".$i." registros.";
    echo " F/H Final: " . date('d/m/Y H:i:s');
    exit();

?>
