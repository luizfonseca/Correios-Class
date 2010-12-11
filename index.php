<?php
/*
 * @Correios class test
 */
require_once('Correios.class.php');




/*
 *@param string $sample_product
 */

$sample_product = 'a TV';  
$correios = new Correios($sample_product);    

/*
 *@param calcula_frete( $cepOrigem, $cepDestino );
 */

$correios->calcula_frete();  


/*
 * @param format_xml ( int(1 or 2) )
 * 1 - return all values
 * 2 - return only Valor, PrazoEntrega and Erro
 */

print_r($correios->format_xml(2));

?>
