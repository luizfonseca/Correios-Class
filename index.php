<?php
error_reporting(E_ALL|E_STRICT);
/*
 *
 * @Correios class test
 *
 *
 */
require_once('Correios.class.php');
$sample__ = 'tv';
$correios = new Correios($sample__);    
$correios->calcula_frete();
print_r($correios->format_xml(1));

?>
