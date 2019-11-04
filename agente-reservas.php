#!/usr/bin/php

<?php
        echo "F/H Inicio: " . date('d/m/Y H:i:s');

        //Creas una variable de tipo objeto mysqli con los datos de la bd y el charset que quieras
        $mysql_loc = mysqli_connect('localhost', 'root','','fc');
        $mysql_loc->set_charset("utf8");
        //var_dump($mysql_loc);

        //Creo la variable con la consulta a la BD Local
        $consultalocal="Select * from reservaga where reservaga.reservaga_fechaweb is null;";

echo $consultalocal;

        //Chequeo que la conexion Local se haya establecido bien
        if(!$mysql_loc)
        {
                echo "No se ha podido conectar PHP - MySQL, verifique sus datos Locales.\r".PHP_EOL ;
                echo mysqli_connect_errno().PHP_EOL;
                echo mysqli_connect_error().PHP_EOL;
                exit();
        }
        else /*conexion local exitosa*/
        {
                //Realizo la consulta a la Base de Datos Local
                $reslocal = mysqli_query($mysql_loc, $consultalocal);

                if (mysqli_num_rows($reslocal) > 0 )  /* La consulta local devuelve resultados */
                {
                        //Creo Conexion Externa a Base de Datos en la Nube
                        $mysql_ext = mysqli_connect('mipoler.dyndns.org', 'userstock','userstock','fc','43306');
                        $mysql_ext->set_charset("utf8");

                        if(!$mysql_ext) /*No se conecto a la DB en la nube*/
                        {
                                echo "No se ha podido conectar PHP - MySQL, verifique sus datos Externos.\r".PHP_EOL ;
                                echo mysqli_connect_errno().PHP_EOL;
                                echo mysqli_connect_error().PHP_EOL;
                                mysqli_free_result($reslocal);
                                mysqli_close($mysql_loc);
                                echo " F/H Final: " . date('d/m/Y H:i:s');
                                exit();
                        }
                        else /*conexion externa exitosa*/
                        {
                                //Itero hasta que los datos locales tomados en $f lleguen al final
                                while ($f = mysqli_fetch_array($reslocal))
                                {
                                        //Creo la consulta externa que inserta el dato a travez del SP en Casa Central
                                        $consultaext = "CALL addreserva(".(int)$f['reservaga_id'].", ".(int)$f['reservaga_suc'].", ".(int)$f['clt_id'].", \"".$f['reservaga_detclt']."\", '".$f['reservaga_fechamov']."', ".(int)$f['cantitems'].", ".(int)$f['reservaga_tipo'].", ".(int)$f['dpt_id'].");"; 
                                        echo $consultaext;

                                        //Ejecuto la consulta creada en la conexion externa
                                        $resext = mysqli_query($mysql_ext,$consultaext);                                        
                                        //mysqli_free_result($resext);


                                        //Creo 2da consulta en local para buscar datos en detreservaga
                                        $consultalocal2 = "Select reservaga_id, reservaga_suc, prd_id, cantidad, detreservaga_tipo from detreservaga where reservaga_id = ".(int)$f['reservaga_id']." and reservaga_suc = ".(int)$f['reservaga_suc'].";";
                                        $reslocal2 = mysqli_query($mysql_loc, $consultalocal2);
                                        echo $consultalocal2;

                                        if (mysqli_num_rows($reslocal2) > 0 )  /* La consulta local devuelve resultados de la tabla detreservaga*/
                                        {
                                    	    while ($g = mysqli_fetch_array($reslocal2))
                                    		{
                                    			$consultaext2 = "CALL adddetreserva(".(int)$g['reservaga_id'].", ".(int)$g['reservaga_suc'].", ".(int)$g['prd_id'].", ".(int)$g['cantidad'].", ".(int)$g['detreservaga_tipo'].", ".(int)$f['dpt_id'].");";
                                    			echo $consultaext2;
                                    			
                                    			$resext2 = mysqli_query($mysql_ext,$consultaext2) ;
                                    			//mysqli_free_result($resext2);

                                    			//Creo la consulta que actualiza los datos localmente y seteamos campo fecha_web para marcar que ya esta subida a GA
                                    			$consultalocal4 = "UPDATE detreservaga set detreservaga_fechaweb = NOW() where reservaga_id = ".(int)$g['reservaga_id']." and reservaga_suc = ".(int)$g['reservaga_suc']." and prd_id = ".(int)$g['prd_id'].";";

                                    			//Ejecuto la consulta creada en la conexion local
                                    			$reslocal4 = mysqli_query($mysql_loc, $consultalocal4);
                                    			//mysqli_free_result($reslocal4);
                                    		}
                                    	}

                                    	mysqli_free_result($reslocal2);

                                        //Creo la consulta que actualiza los datos localmente y seteamos campo fecha_web para marcar que ya esta subida a GA
                                        $consultalocal3 = "UPDATE reservaga set reservaga_fechaweb = NOW() where reservaga_id = ".(int)$f['reservaga_id']." and reservaga_suc = ".(int)$f['reservaga_suc'].";";
                                        echo $consultalocal3;

                                        //Ejecuto la consulta creada en la conexion local
                                        $reslocal3 = mysqli_query($mysql_loc, $consultalocal3);
                                        //mysqli_free_result($reslocal3);

                                }
                                mysqli_free_result($reslocal);
                                mysqli_close($mysql_ext);
                                mysqli_close($mysql_loc);
                                echo " F/H Final: " . date('d/m/Y H:i:s');
                                exit();
                        }

                }
                else //Si la consulta local no devuelve resultados directamente me desconecto
                {
                        mysqli_close($mysql_loc);
                        echo " Sin Reg. ";
                        echo " F/H Final: " . date('d/m/Y H:i:s');
                        exit();
                }
        }

?>
