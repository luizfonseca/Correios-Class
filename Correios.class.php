<?php

/**
 * @param $produto
 * nCdEmpresa=
 * &sDsSenha=
 * &sCepOrigem=
 * 71939360&
 * sCepDestino=
 * 72151613
 * &
 * nVlPeso=1&
 * nCdFormato
 * =1&
 * nVlComprimento
 * =20&
 * nVlAltura=5&
 * nVlLargura=15&
 * sCdMaoPropria=
 * s&nVlValorDeclarado=200&
 * sCdAvisoRecebimento=n&
 * nCdServico=41106&
 * nVlDiametro=0&
 * StrRetorno=http://ws.correios.com.br/calculador/popuptarifa.aspx
 *
 *
 **/
class Correios
{
    #inicializando os tipos de frete, e se existe um produto.
    
    const FRETE_PAC         = '41106'; #PAC sem contrato
    const FRETE_SEDEX       = '40010'; #SEDEX sem contrato
    const FRETE_SEDEX_10    = '40215'; #SEDEX 10, sem contrato
    const FRETE_SEDEX_HOJE  = '40290'; #SEDEX HOJE, sem contrato
    const FRETE_COBRAR     = '40045'; #SEDEX a Cobrar, sem contrato
    const FRETE_E_SEDEX     = '81019'; #e-SEDEX, com contrato
    const URL_CORREIOS      = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?';

    public $produto = null; 
    public $output = Array();
    private $dom = null;


    public function __construct($object)
    {
       $this->produto = (empty($object)) ? $this->error_type(1) : $object;
    }


    public function calcula_frete ($cepOrigem = '', $cepDestino = '')
    {

        if ($cepOrigem <> '' || $cepDestino <> ''):
            $replace = Array ('-',' ','/','_');
            $cepOrigem = str_replace($replaces,'', $cepOrigem);
            $cepDestino = str_replace($replaces,'', $cepDestino);
        else:
            $cepOrigem = '78230000'; #valor padrão
            $cepDestino = '78110020'; #valor padrão
        endif;
    
        if (!is_null($this->produto)):
            $dados = Array(
                'nCdEmpresa'            => '',
                'sDsSenha'              => '',
                'sCepOrigem'            => $cepOrigem,
                'sCepDestino'           => $cepDestino,
                'nVlPeso'               => '10',
                'nCdFormato'            => '1',
                'nVlComprimento'        => '20',
                'nVlAltura'             => '20',
                'nVlLargura'            => '20',
                'sCdMaoPropria'         => 'n',
                'nVlValorDeclarado'     => '220',
                'sCdAvisoRecebimento'   => 'n',
                'nCdServico'            => self::FRETE_PAC,
                'nVlDiametro'           => '0',           
                'StrRetorno'            => 'xml' #opções possíveis: 'popup', 'xml' e URL (será retornado via POST)               
            );
               
            $page_correios_query = http_build_query($dados);
            $page_correios_url   = file_get_contents(self::URL_CORREIOS . $page_correios_query);
        endif;
           
            return $page_correios_url;
    }


    public function format_xml ($options, $args = '')
    {
        if ((int)$options):
           
            $dom = new DOMDocument('1.0','iso-8859-1');
            $dom->formatOutput = True;
            $dom->loadXML($this->calcula_frete());

            if ($options == 1):
                $tags = Array ( 
                        'Valor',
                        'PrazoEntrega',
                        'ValorMaoPropria',
                        'ValorAvisoRecebimento',
                        'ValorDeclarado',
                        'EntregaDomiciliar',
                        'EntregaSabado',
                        'Erro',
                        'MsgErro'
                );

                foreach ($tags as $key => $value):
                    @$this->output[$value] = $dom->getElementsByTagName($value)->item(0)->nodeValue;
                endforeach;

            elseif ($options == 2):
                $this->output = Array(
                    'Valor' => $dom->getElementsByTagName('Valor')
                    ->item(0)->nodeValue,
                    'Prazo' => $dom->getElementsByTagName('PrazoEntrega')
                    ->item(0)->nodeValue,
                    'Erro' => $dom->GetElementsByTagName('Erro')
                    ->item(0)->nodeValue
                );
            endif;
         endif;

         return $this->output;
    }

    public function error_type ($error_number)
    {
        if ((int)$error):
            switch ($error):
                case 1: $error_msg = 'Produto não definido'; break; # esqueceram de chamar o produto...
                case 2: $error_msg = 'Não foi possível calcular o frete. Aguarde.'; #provavelmente um problema no get/post dos correios
                case 3: $error_msg = 'Erro fatal. Consulte o administrador.'; # Exception
            endswitch;
        endif;
        return $error_msg;
    }
}



?>
