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

	$ConsultaStock_Suc_Grupo2="SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, 0) AS Stk_Libre, 
	IF(r_dpt_prd.r_stock >= 1, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)), 0) AS 'Dif_Stk_Max', 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))) AS Stock_Total,
	stock_mp.suc_id AS Suc,
	r_dpt_prd.dpt_id AS Dep_Suc,
	stock_mp.stock AS Stock_Suc_Full,
	CAST(r_dpt_prd.r_maximo AS SIGNED) AS Max_Suc, 
	r_dpt_prd.r_stock AS Reponer_Suc,
	prd.prd_clasificacion AS Clasificacion
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep2.") /* Consultar en Depositos Grupo 1*/
	 ) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep2.") /* Consultar en Depositos Grupo 1*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']."  ORDER BY r_dpt_prd.dpt_id;";

	echo $ConsultaStock_Suc_Grupo2 . "\n";

	$ConsultaStock_Suc_Grupo3="SELECT r_dpt_prd.prd_id, prd.prd_codalfa, FC_Division_Det(prd.fliaprd_id) as Division, LEFT(REPLACE(REPLACE(prd.prd_detanterior,'(-SU)',''),'-  -',''),256) AS Detalle, 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, 0) AS Stk_Libre, 
	IF(r_dpt_prd.r_stock >= 1, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED)), 0) AS 'Dif_Stk_Max', 
	IF(r_dpt_prd.r_stock = 0, stock_mp.stock, (stock_mp.stock - CAST(r_dpt_prd.r_maximo AS SIGNED))) AS Stock_Total,
	stock_mp.suc_id AS Suc,
	r_dpt_prd.dpt_id AS Dep_Suc,
	stock_mp.stock AS Stock_Suc_Full,
	CAST(r_dpt_prd.r_maximo AS SIGNED) AS Max_Suc, 
	r_dpt_prd.r_stock AS Reponer_Suc,
	prd.prd_clasificacion AS Clasificacion
	FROM r_dpt_prd
	INNER JOIN stock_mp ON r_dpt_prd.prd_id = stock_mp.prd_id AND r_dpt_prd.dpt_id = stock_mp.dpt_id
	INNER JOIN prd ON prd.prd_id = stock_mp.prd_id AND prd.prd_id = r_dpt_prd.prd_id
	WHERE (
	(r_dpt_prd.r_stock = 0 /*Sin definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep3.") /* Consultar en Depositos Grupo 1*/
	 ) 
	OR 
	(r_dpt_prd.r_stock = 1 /*Con definicion de Maximos y Minimos*/ 
	AND stock_mp.stock > 0 /*Con Stock positivo*/ AND r_dpt_prd.dpt_id IN (".$grupo_dep3.") /* Consultar en Depositos Grupo 1*/
	AND (stock_mp.stock - r_dpt_prd.r_maximo > 0) /*Que el Stock sea Superior al Maximo definido*/ 
	) )
	AND prd.prd_id = ".$art_pve['prd_id']."  ORDER BY r_dpt_prd.dpt_id;";

	echo $ConsultaStock_Suc_Grupo3 . "\n";



?>
