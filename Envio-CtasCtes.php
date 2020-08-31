<?php

include( "server.php" );

$conn = mysqli_connect( $host, $user, $pass, $database )or die( "Error en Usuario o Contraseña " );

require_once 'agente-pve-stock-plus.php';

$i = 0;

// Consulta para obtener los productos marcados para comprar en los pedidos de Ventas de las Sucursales, 
// que no tengan un mail enviado y que no haya sido atendido por GA
//$Consulta_inicial = "SELECT * FROM detpve WHERE detpve.detpve_atendido = 0 AND detpve.detpve_tipo = 5 AND detpve.dpt_id = 9 AND detpve.detpve_destmail IS NULL;";
$Consulta_inicial = "";

$Datos = "\"Prd_id\",\"CodAlfa\",\"Division\",\"Clasificacion\",\"Articulo\",\"StockSUC\",\"Fecha Ult Venta\"\r\n";

date_default_timezone_set('America/Argentina/Tucuman');

$enlace = mysqli_connect ( "192.168.0.155", "mipoldb", "mipol123", "fc" );
// $enlace = mysqli_connect ( "192.168.0.157", "root", "", "fc" );

mysqli_query ( $enlace, "SET NAMES 'utf8'");

echo "\r\nEmpieza la Consulta..." . date ( 'r' ) . "\r\n";

/* Comprobar la conexion */
if (mysqli_connect_errno ()) {
	printf ( "Fallo en la conexión: %s\n", mysqli_connect_error () );
	exit ();
}
// Obtengo datos con la consulta anterior desde la Base de Datos
$Articulos_pve = mysqli_query ( $enlace, $Consulta_inicial );



== 'busca_ctacte' ) {

        $mes = $_POST[ 'mes' ];
        $anio = $_POST[ 'anio' ];
        $clt_id = $_POST[ 'representante' ];

        $sqltxt = "call sp_saldoclt($clt_id)";
        $rs2 = $conn->query( $sqltxt );
        $resultado = '';
        $totales = array();
        $totales[ 0 ] = 0;
        $totales[ 1 ] = 0;
        $totales[ 2 ] = 0;
        while ( $fila = mysqli_fetch_array( $rs2 ) ) {

                if( number_format( $fila['saldo'] ) < 0 )
                        $color='red';
                else
                        $color='black';

                $resultado = $resultado .
                '<tr><td align="left">' . $fila[ 'fecha' ] . '</td>
                        <td align="left">' . $fila['fecven'] . '</td>
                        <td align="left">' . $fila['comprobante']  . '</td>
                        <td align="right">' . number_format( $fila['total']). '</td>
                        <td align="right" style="color:'.$color.'">' . number_format( $fila['saldo'] ) . '</td></tr>';

                $totales[ 0 ] = $totales[ 0 ] + $fila[ 'saldo' ];
        }
        echo $resultado. '*' . '<tr><th class="align-middle text-light">Totales</th>
        <th class="text-light" style="text-align: right">&nbsp;</th>' .
        '<th class="text-light enteros" style="text-align: right">&nbsp;</th>' .
        '<th class="align-middle text-light porce">&nbsp;</th>' .
        '<th class="align-middle text-light porce" style="text-align: right">' . number_format( $totales[ 0 ] ) . '</th></tr>';
        $conn->close();
}

?>
