<?php

    $url = "http://34.82.252.252/index.php/rest";
    $token_url= $url."/V1/integration/admin/token";
    
    #-- Creo URL de REST API para productos
    $apiUrl = $url."/V1/products";
    
    $username= "mbaranello";
    $password= "Carola123";

    $Consulta_precios ="SELECT sku, 'simple' as product_type, product_websites,
    if ((CAST((((SELECT cast(((p1.preciovta_mayorista + 100)/100 * p1.costo ) AS Decimal(13,2))
    FROM preciovta p1 WHERE p1.prd_id = articulos_magento.id_gestuc ORDER BY p1.preciovta_id DESC LIMIT 1) * 0.81225) * 1.7182) AS DECIMAL(13,2))) IS NULL, 0.00,
    CAST((((SELECT cast(((p1.preciovta_mayorista + 100)/100 * p1.costo ) AS Decimal(13,2))
    FROM preciovta p1 WHERE p1.prd_id = articulos_magento.id_gestuc ORDER BY p1.preciovta_id DESC LIMIT 1) * 0.81225) * 1.7182) AS DECIMAL(13,2))) AS 'price',
    IF ((CAST(((SELECT cast(((p1.preciovta_mayorista + 100)/100 * p1.costo ) AS Decimal(13,2))
    FROM preciovta p1 WHERE p1.prd_id = articulos_magento.id_gestuc ORDER BY p1.preciovta_id DESC LIMIT 1) * 0.81225) AS DECIMAL(13,2))) IS NULL, 0.00,
    CAST(((SELECT cast(((p1.preciovta_mayorista + 100)/100 * p1.costo ) AS Decimal(13,2))
    FROM preciovta p1 WHERE p1.prd_id = articulos_magento.id_gestuc ORDER BY p1.preciovta_id DESC LIMIT 1) * 0.81225) AS DECIMAL(13,2)))  AS 'special_price',
    if((fc_stockmp_PO(articulos_magento.id_gestuc) + fc_stockmp_suc(articulos_magento.id_gestuc,46) + fc_stockmp_GA(articulos_magento.id_gestuc))<0, 0, 
    (fc_stockmp_PO(articulos_magento.id_gestuc) + fc_stockmp_suc(articulos_magento.id_gestuc,46) + fc_stockmp_GA(articulos_magento.id_gestuc))) AS 'qty'
    FROM articulos_magento
    INNER JOIN prd ON prd.prd_id = articulos_magento.id_gestuc
    WHERE product_online = 1 AND id_gestuc IN 
    (SELECT pv1.prd_id FROM preciovta AS pv1 
    INNER JOIN (SELECT prd_id, MAX(preciovta_id) AS iden FROM preciovta GROUP BY prd_id) AS pv2 ON pv1.prd_id = pv2.prd_id AND pv1.preciovta_id = pv2.iden 
    WHERE pv1.preciovta_vigencia > ";
    
    //'2020-04-17');";

    $Consulta_stock = "SELECT sku, 'simple' as product_type, product_websites,
    if((fc_stockmp_PO(articulos_magento.id_gestuc) + fc_stockmp_suc(articulos_magento.id_gestuc,46) + fc_stockmp_GA(articulos_magento.id_gestuc))<0, 0, 
    (fc_stockmp_PO(articulos_magento.id_gestuc) + fc_stockmp_suc(articulos_magento.id_gestuc,46) + fc_stockmp_GA(articulos_magento.id_gestuc))) AS 'qty'
    FROM articulos_magento
    INNER JOIN prd ON prd.prd_id = articulos_magento.id_gestuc
    WHERE product_online = 1 AND id_gestuc IN 
    (SELECT DISTINCT prd_id FROM stock_mp s WHERE s.dpt_id IN (9,45,46) AND s.fecha_mov > ";
    
    #--'2020-04-20 09:00');";

    $Consulta_sustitutos = "SELECT sku, id_gestuc, related_skus, related_position FROM articulos_magento WHERE related_skus != '' AND related_position = '1,2' limit 10;";
    
    #-- AND related_position = '1,2,3,4,5';";

    class Token  {
        #-- Propiedades
        public $user;
        public $pass;
        public $dir_token;
        public $codigo;

        #-- Metodos
        function obtener_token($user,$pass,$dir_token){

            //Authentication REST API magento 2,    
            $ch = curl_init();
            $data = array("username" => $user, "password" => $pass);
            $data_string = json_encode($data);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $dir_token);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($data_string) ));

            $admintoken = curl_exec($ch);
                
            $this->codigo = json_decode($admintoken);

            return $this->codigo;

        }

    }

    class Articulo {

        #-- Propiedades
        public $datos;
        public $sku;
        public $price;
        public $special_price;
        public $status;
        public $visibility;
        public $type_id;
        public $created_at;
        public $updated_at;
        public $qty;
        public $is_in_stock;

        #-- Metodos
        function cargar_matriz_precios($sku,$price,$special_price){
            
            $data = [
                "product" => [
                    "sku" => $sku,
                    "price" => $price,
                    "type_id" => "simple",
                    "custom_attributes" => [
                        [
                            "attribute_code" => "special_price",
                            "value" => $special_price
                        ]
                    ]
                   
                ]
            ];

            $this->datos = $data;

            return $this->datos;

        }

        function cargar_matriz_stock($sku,$qty){
            
            $data = [
                "product" => [
                    "sku" => $sku,
                    "type_id" => "simple",
                    "extension_attributes" => [
                        "stock_item" => [
                                "qty" => $qty,
                                "is_in_stock" => 1
                        ]
                    ]
                ]
            ];

            $this->datos = $data;

            return $this->datos;
        }

        function cargar_matriz_sustitutos($sku,$rel_skus,$rel_pos){
            
            $relaciones = explode(",", $rel_skus);
            //echo count($relaciones)."\r\n";

            $posiciones = explode(",", $rel_pos);
            //echo count($posiciones)."\r\n";
          
            #-- Blanqueo matriz $rel
            $rel = array();

            for ($x = 0; $x < count($relaciones); $x++) {
                $rel[$x] = array("sku"=>$sku, "link_type"=>"related", "linked_product_sku"=>$relaciones[$x], "linked_product_type"=>"simple", "position"=>$posiciones[$x]);
                //echo $relaciones[$x]."\r\n"; // porción1
                //echo $posiciones[$x]."\r\n"; // porción1
            }

            $data = [
                "product" => [
                    "sku" => $sku,
                    //"attribute_set_id" => 4,
                    //"status" => 1,
                    //"visibility" => 4,
                    "type_id" => "simple",
                    "product_links" => $rel
                        // [
                        //     "sku" => $art_magento["sku"],
                        //     "link_type" => "related",
                        //     "linked_product_sku" => $relaciones[0],
                        //     "linked_product_type" => "simple",
                        //     "position" => $posiciones[0]
                        // ]            
                ]
            ];
            
            $this->datos = $data;

            return $this->datos;
        }

    }

?>

