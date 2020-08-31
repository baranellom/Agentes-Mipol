#!/usr/bin/php

<?php
        echo "F/H Inicio: " . date('d/m/Y H:i:s');

        //Creas una variable de tipo objeto mysqli con los datos de la bd y el charset que quieras
        $mysql_loc = mysqli_connect('localhost', 'root','','fc');
        $mysql_loc->set_charset("utf8");
        //var_dump($mysql_loc);

        //Creo la variable con la consulta a la BD Local
        $consultalocal="Select stock_mp.* from stock_mp where (fecha_web<fecha_mov) or (fecha_web is null);";

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

                if (mysqli_num_rows($reslocal)> 0 )  /* La consulta local devuelve mas de 1 resultado*/
                {
                        //Creo Conexion Externa a Base de Datos en la Nube
                        $mysql_ext = mysqli_connect('mipoler.dyndns.org', 'userstock','userstock','fc','43306');
                        $mysql_ext->set_charset("utf8");

                        if(!$mysql_ext) /*No logro conectarse a la DB en la nube*/
                        {
                                echo "No se ha podido conectar PHP - MySQL, verifique sus datos Externos.\r".PHP_EOL ;
                                echo mysqli_connect_errno().PHP_EOL;
                                echo mysqli_connect_error().PHP_EOL;
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
                                        $consultaext = "CALL actualizastock(".(int)$f['prd_id'].",".(int)$f['dpt_id'].",".(int)$f['stock'].",".(int)$f['suc_id'].");";

                                        //Ejecuto la consulta creada en la conexion externa
                                        $resext_1 = mysqli_query($mysql_ext,$consultaext) ;
                                        echo $consultaext;

                                        //Creo la consulta que actualiza los datos localmente
                                        $consultalocal = "UPDATE stock_mp set fecha_web = NOW() where prd_id=".(int)$f['prd_id']." and dpt_id=".(int)$f['dpt_id'].";";

                                        //Ejecuto la consulta local
                                        $reslocal_1 = mysqli_query($mysql_loc,$consultalocal) ;
                                        echo $consultalocal;
                                }
                                mysqli_close($mysql_ext);
                                mysqli_close($mysql_loc);
                                echo " F/H Final: " . date('d/m/Y H:i:s');
                                exit();
                        }

                }
                else //Si la consulta local no devuelve resultados
                {
                        mysqli_close($mysql_loc);
                        echo " Sin Reg. ";
                        echo " F/H Final: " . date('d/m/Y H:i:s');
                        exit();
                }
        }

?>