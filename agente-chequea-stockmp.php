#!/usr/bin/php

<?php
$DIRHOME="/usr/local/bin/";

echo "F/H Inicio: " . date('d/m/Y H:i:s') . "\n";

//Creas una variable de tipo objeto mysqli con los datos de la bd y el charset que quieras
$mysql_loc = mysqli_connect('localhost', 'root','','fc');
$mysql_loc->set_charset("utf8");
//var_dump($mysql_loc);

//Creo la variable con la consulta a la BD Local
$consultalocal="SELECT prd_id, dpt_id, suc_id, fecha_mov, stock FROM stock_mp WHERE stock_mp.suc_id = ".$argv[1]." ORDER BY prd_id;";

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
        $mysql_ext = mysqli_connect('mipoler.dyndns.org', 'consultor','mipol_123','fc','43306');
        $mysql_ext->set_charset("utf8");

        if (mysqli_connect_errno()) 
        {
            printf("Falló la conexión Externa: %s\n", mysqli_connect_error());
            mysqli_close($mysql_loc);
            echo " F/H Final: " . date('d/m/Y H:i:s');
            exit();
        }
        else /*conexion externa exitosa*/
        {
            $Datos = "\"Prd_id\",\"Stock Suc\",\"Stock GA\",\"Fecha Mov Suc\",\"Fecha Mov GA\",\"Suc\"\n";
        	$stockmp_file= fopen($DIRHOME.'Stockmp.csv',"w");
	        fwrite($stockmp_file, $Datos);
            
            //Itero hasta que los datos locales tomados en $f lleguen al final
            while ($l = mysqli_fetch_array($reslocal))
            {
                //Creo la consulta externa que verifica el Stock del producto en Casa Central
                $consultaext = "SELECT * FROM stock_mp WHERE stock_mp.prd_id = ".(int)$l['prd_id']." AND stock_mp.dpt_id = ".(int)$l['dpt_id']." AND stock_mp.suc_id = ".(int)$l['suc_id'].";";

                //Ejecuto la consulta creada en la conexion externa
                $resext_1 = mysqli_query($mysql_ext,$consultaext) ;
                //echo $consultaext;

                if (mysqli_num_rows($resext_1) === 1) //Si la consulta devuelve mas de 1 registro o 0, es un error.
                {
                    $r = mysqli_fetch_array($resext_1);
  
                    if ($r['stock'] != $l['stock'])  // Si encuentro diferencias de Stock, lo agrego al archivo que luego se enviará
                    {
                        $consulta_local_extendida = "SELECT prd_id, 
                        SUM(IF(tipocpb_accion=1,detcpbstock_cant,IF(tipocpb_accion=2,-detcpbstock_cant,IF(cpbstock.dpt_id=".(int)$l['dpt_id']." ,-detcpbstock_cant,detcpbstock_cant)))) AS mov, 
                        SUM(IF(tipocpb_accion=1,detcpbstock_cant,IF(tipocpb_accion=2,0,IF(cpbstock.dpt_id=".(int)$l['dpt_id'].",0,detcpbstock_cant)))) AS ing, 
                        SUM(IF(tipocpb_accion=1,0,IF(tipocpb_accion=2,-detcpbstock_cant,IF(cpbstock.dpt_id=".(int)$l['dpt_id'].",-detcpbstock_cant,0)))) AS egr
                        FROM cpbstock
                        INNER JOIN detcpbstock_prd ON cpbstock.cpbstock_id=detcpbstock_prd.cpbstock_id AND cpbstock.cpbstock_suc=detcpbstock_prd.cpbstock_suc
                        INNER JOIN tipocpb ON cpbstock.tipocpb_id = tipocpb.tipocpb_id
                        WHERE prd_id=".(int)$l['prd_id']." AND (cpbstock.dpt_id=".(int)$l['dpt_id']." OR cpbstock.dpt_id2=".(int)$l['dpt_id'].") AND (cpbstock.cpbstock_fecanul IS NULL OR cpbstock.cpbstock_fecanul='0000-00-00 00:00:00') AND cpbstock.cpbstock_fecha<=curdate()
                        GROUP BY prd_id;";

                        $consulta_remota_extendida = "SELECT prd_id, 
                        SUM(IF(tipocpb_accion=1,detcpbstock_cant,IF(tipocpb_accion=2,-detcpbstock_cant,IF(cpbstock.dpt_id=".(int)$r['dpt_id']." ,-detcpbstock_cant,detcpbstock_cant)))) AS mov, 
                        SUM(IF(tipocpb_accion=1,detcpbstock_cant,IF(tipocpb_accion=2,0,IF(cpbstock.dpt_id=".(int)$r['dpt_id'].",0,detcpbstock_cant)))) AS ing, 
                        SUM(IF(tipocpb_accion=1,0,IF(tipocpb_accion=2,-detcpbstock_cant,IF(cpbstock.dpt_id=".(int)$r['dpt_id'].",-detcpbstock_cant,0)))) AS egr
                        FROM cpbstock
                        INNER JOIN detcpbstock_prd ON cpbstock.cpbstock_id=detcpbstock_prd.cpbstock_id AND cpbstock.cpbstock_suc=detcpbstock_prd.cpbstock_suc
                        INNER JOIN tipocpb ON cpbstock.tipocpb_id = tipocpb.tipocpb_id
                        WHERE prd_id=".(int)$r['prd_id']." AND (cpbstock.dpt_id=".(int)$r['dpt_id']." OR cpbstock.dpt_id2=".(int)$r['dpt_id'].") AND (cpbstock.cpbstock_fecanul IS NULL OR cpbstock.cpbstock_fecanul='0000-00-00 00:00:00') AND cpbstock.cpbstock_fecha<=curdate()
                        GROUP BY prd_id;";

                        $res_ext_local = mysqli_fetch_array(mysqli_query($mysql_loc, $consulta_local_extendida));

                        $res_ext_remota = mysqli_fetch_array(mysqli_query($mysql_ext, $consulta_remota_extendida));

                        printf("Se encontro una Diferencia de Stock: prd_id = %s, Stock Suc = %s, Stock GA = %s\n", $l['prd_id'] , $l['stock'],  $r['stock']);
                        $reg = $l['prd_id'].",". $l['stock'] .",". $r['stock'] .",". $l['fecha_mov'] .",". $r['fecha_mov'] .",". $l['suc_id']."\n";
                        fwrite($stockmp_file, $reg);
                    }
                }
                else
                {
                    printf("La consulta no devolvio 1 registro en la consulta externa, cuyo prd_id = %s\n", $l['prd_id']);
                    $reg = $l['prd_id'].",". $l['stock'] .",". $r['stock'] .",". $l['fecha_mov'] .",". $r['fecha_mov'] .",". $l['suc_id']."\n";
                    fwrite($stockmp_file, $reg);
                }
            }
            mysqli_close($mysql_ext);
            mysqli_close($mysql_loc);
            fclose($stockmp_file);
            $salida = shell_exec("mail -s 'Envio Archivo diferencias Stock_mp La Banda' -a /usr/local/bin/Stockmp.csv mbaranello@mipolrepuestos.com < cuerpo-mail.txt");
            //echo "$salida";
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