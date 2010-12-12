<?php

/**
 * Correios - Class
 *
 * Returns an Array with all data provided by Correios itself
 * Requires DOMDocument lib
 *
 * @author Luiz Claudio M. Fonseca
 * @copyright Copyright (c)
 * @license http://www.opensource.org/licenses/bsd-license.php
 *
 * @param string $produto
 * @param Array | string CdEmpresa, sdDsSenha, sCepOrigem, SCepDestino, nVlPeso, nCdFormato, 
 * @param Array | string nVlComprimento, nVlAltura, nVlLargura, sCdMaoPropria, nVlValorDeclarado,
 * @param Array | string sCdAvisoRecebimento, nCdServico, nVlDiametro, StrRetorno 
 *
 **/
class Correios
{
    #inicializando os tipos de frete e, se existe um produto.
    
    const FRETE_PAC         = '41106'; #PAC sem contrato
    const FRETE_SEDEX       = '40010'; #SEDEX sem contrato
    const FRETE_SEDEX_10    = '40215'; #SEDEX 10, sem contrato
    const FRETE_SEDEX_HOJE  = '40290'; #SEDEX HOJE, sem contrato
    const FRETE_COBRAR      = '40045'; #SEDEX a Cobrar, sem contrato
    const FRETE_E_SEDEX     = '81019'; #e-SEDEX, com contrato
    const FRETE_SEDEX_C     = '40096'; #SEDEX com contrato
    const URL_CORREIOS      = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?';


    public  $output      = Array();
    public  $produto     = null;
    private $largura     = '20'; # if is FRETE_PAC, obrigatory.
    private $altura      = '20'; # if is FRETE_PAC, obrigatory.
    private $comprimento = '20'; # if is FRETE_PAC, obrigatory.
    private $diametro    = '0';
    private $formato     = '1';
    private $valor       = '0'; 
    protected $dom       = null;


    public function __construct($object)
    {
        $this->produto = (!isset($object) && $object == '') ? null : $object;
        if (is_null($this->produto)) return $this->error_type(1);
    }

    public function set_product_detail (  $nVlLargura = '', $nVlAltura = '', $nVlComprimento = '', $nVlDiametro = '', $nCdFormato = '', $nVlValor = '' )
    {
        $this->largura      = ((int)$nVlLargura <> '')      ? $nVlLargura     : $this->largura;
        $this->altura       = ((int)$nVlAltura  <> '')      ? $nVlAltura      : $this->altura;
        $this->comprimento  = ((int)$nVlComprimento <> '')  ? $nVlComprimento : $this->comprimento;
        $this->diametro     = ((int)$nVlDiametro <> '')     ? $nVlDiametro    : $this->diametro;
        $this->formato      = ((int)$nCdFormato <> '')      ? $nCdFormato     : $this->formato;
        $this->valor        = ((int)$nVlValor <> '')        ? $nVlValor       : $this->valor; 
    }




    public function calcula_frete ( $tipoFrete = 1, $cepOrigem = '', $cepDestino = '', $pesoProduto = '')
    {

        if ($cepOrigem <> '' && $cepDestino <> '' && (int)$tipoFrete <> '' && (int)$pesoProduto <> '' ):
            $replaces = Array ('-',' ','/','_');
            $cepOrigem = str_replace($replaces,'', $cepOrigem);
            $cepDestino = str_replace($replaces,'', $cepDestino);
        else:
            $cepOrigem = '78230000'; #valor padrão
            $cepDestino = '78110020'; #valor padrão
            $pesoProduto = 1; 
			switch ((int)$tipoFrete):
				case 1:     $tipoFrete = self::FRETE_PAC;
				case 2:     $tipoFrete = self::FRETE_SEDEX;
                case 3:     $tipoFrete = self::FRETE_SEDEX_10;
                case 4:     $tipoFrete = self::FRETE_SEDEX_HOJE;
                case 5:     $tipoFrete = self::FRETE_COBRAR;
                case 6:     $tipoFrete = self::FRETE_E_SEDEX;
                case 7:     $tipoFrete = self::FRETE_SEDEX_C;
                default:    $tipoFrete = self::FRETE_PAC;
            endswitch;
            if ($tipoFrete == 1):
                if ( $this->largura > 60 or $this->largura < 5 ): # Se for FRETE_PAC, largura não pode exceder esses valores.
                    return False; endif;
                if ( $this->largura < 11 && $this->comprimento < 25 ):
                    return False; endif;
                if ( $this->comprimento < 16 or $this->comprimento > 60):
                    return False; endif;
                if ( ($this->largura + $this->altura + $this->comprimento) > 160):
                    return False; endif;
                if ( $this->altura > $this->comprimento ):
                    return False; endif;
            endif;
        endif;
    
        if (!is_null($this->produto)):
            $dados = Array(
                'nCdEmpresa'            => '',                  #Opcional 
                'sDsSenha'              => '',                  #Opcional 
                'sCepOrigem'            => $cepOrigem,
                'sCepDestino'           => $cepDestino,
                'nVlPeso'               => $pesoProduto,
                'nCdFormato'            => $this->formato,      # 1 - Formato caixa/pacote ; 2 - Formato rolo/prisma
                'nVlComprimento'        => $this->comprimento,  # Comprimento em centímetros
                'nVlAltura'             => $this->altura,       # Altura em centímetros (incluíndo embalagem)
                'nVlLargura'            => $this->largura,      # Largura em centímetros (incluindo embalagem)
                'sCdMaoPropria'         => 'n',
                'nVlValorDeclarado'     => $this->valor,        # Valor declarado no envio?
                'sCdAvisoRecebimento'   => 'n',                 
                'nCdServico'            => $tipoFrete,          # PAC, SEDEX , etc.
                'nVlDiametro'           => $this->diametro,     # Diametro ( padrão 0 )      
                'StrRetorno'            => 'xml'                #opções possíveis: 'popup', 'xml' e URL (este será retornado via POST)               
            );
               
            $page_correios_query = http_build_query($dados);
            $page_correios_url   = file_get_contents(self::URL_CORREIOS . $page_correios_query);
        else:
            return False;
        endif;           

        return $page_correios_url;
    }


    public function readable_xml ($options, $args = '')
    {
        if ((int)$options):
               if ($this->calcula_frete()):
                $dom = new DOMDocument('1.0','iso-8859-1'); #infelizmente, o xml gerado pelos Correios é neste charset.
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
                        'Erro' 	=> $dom->GetElementsByTagName('Erro')
                        ->item(0)->nodeValue
                    );
                endif;
            else:
                return False;
            endif;
         endif;

         return $this->output;
    }

    public function error_type ($error)
    {
        if ((int)$error):
            switch ($error):
                case 1: $error_msg = 'Produto não definido'; break; # esqueceram de chamar o produto...
                case 2: $error_msg = 'Não foi possível calcular o frete. Aguarde.'; break; #provavelmente um problema no get/post dos correios
                case 3: $error_msg = 'Erro fatal. Consulte o administrador.'; break; # Exception
            endswitch;
        endif;
        return $error_msg;
    }
}
?>
