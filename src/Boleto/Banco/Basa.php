<?php
namespace Eduardokum\LaravelBoleto\Boleto\Banco;

use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Util;

class Basa extends AbstractBoleto implements BoletoContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->addCampoObrigatorio("agenciaDv");
    }

    /**
     * Local de pagamento
     *
     * @var string
     */
    protected $localPagamento = "PAGÁVEL EM QUALQUER AGÊNCIA BANCÁRIA ATÉ O VENCIMENTO";

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_BASA;

    /**
     * Variáveis adicionais.
     *
     * @var array
     */
    public $variaveis_adicionais = [
        "carteira_nome" => "",
    ];

    /**
     * Define as carteiras disponíveis para este banco.
     * Carteira 1 = Cobrança Simples Registrada (conforme manual v1.7).
     *
     * @var array
     */
    protected $carteiras = ["1"];

    /**
     * Espécie do documento, código para remessa
     * Conforme tabela C015 do manual oficial.
     *
     * @var array
     */
    protected $especiesCodigo = [
        "CH"  => "01", // Cheque
        "DM"  => "02", // Duplicata Mercantil
        "DMI" => "03", // Duplicata Mercantil p/ Indicação
        "DS"  => "04", // Duplicata de Serviço
        "DSI" => "05", // Duplicata de Serviço p/ Indicação
        "DR"  => "06", // Duplicata Rural
        "LC"  => "07", // Letra de Câmbio
        "NCC" => "08", // Nota de Crédito Comercial
        "NCE" => "09", // Nota de Crédito a Exportação
        "NCI" => "10", // Nota de Crédito Industrial
        "NCR" => "11", // Nota de Crédito Rural
        "NP"  => "12", // Nota Promissória
        "NPR" => "13", // Nota Promissória Rural
        "TM"  => "14", // Triplicata Mercantil
        "TS"  => "15", // Triplicata de Serviço
        "NS"  => "16", // Nota de Seguro
        "RC"  => "17", // Recibo
        "FAT" => "18", // Fatura
        "ND"  => "19", // Nota de Débito
        "AP"  => "20", // Apólice de Seguro
        "ME"  => "21", // Mensalidade Escolar
        "PC"  => "22", // Parcela de Consórcio
        "NF"  => "23", // Nota Fiscal
        "DD"  => "24", // Documento de Dívida
        "CPR" => "25", // Cédula de Produto Rural
        "WAR" => "26", // Warrant
        "DAE" => "27", // Dívida Ativa de Estado
        "DAM" => "28", // Dívida Ativa de Município
        "DAU" => "29", // Dívida Ativa da União
        "EC"  => "30", // Encargos Condominiais
        "CC"  => "31", // Cartão de Crédito
        "BDP" => "32", // Boleto de Proposta
        "OUT" => "99", // Outros
    ];

    /**
     * Número do convênio
     *
     * @var string
     */
    protected $convenio;

    /**
     * Define o número do convênio.
     *
     * @param  string $convenio
     * @return Basa
     */
    public function setConvenio($convenio)
    {
        $this->convenio = $convenio;
        return $this;
    }

    /**
     * Retorna o número do convênio
     *
     * @return string
     */
    public function getConvenio()
    {
        return $this->convenio;
    }

    /**
     * Gera o Nosso Número.
     * No Banco da Amazônia (manual v1.7, item 15.2.4), o Nosso Número no código de barras
     * e na linha digitável possui 7 dígitos numéricos sem dígito verificador.
     *
     * @return string
     * @throws \Exception
     */
    protected function gerarNossoNumero()
    {
        return Util::numberFormatGeral($this->getNumero(), 7);
    }

    /**
     * Método que retorna o nosso número usado no boleto.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return $this->getNossoNumero();
    }

    /**
     * Método para gerar o código da posição de 20 a 44 (25 posições)
     *
     * Posição 20 a 23 (04): Agência com Dígito (3 dígitos da agência + 1 DV)
     * Posição 24 a 30 (07): Nosso Número sem dígito
     * Posição 31 a 34 (04): DDMM (Dia e Mês do Vencimento) ou zeros
     * Posição 35 a 36 (02): AA (Dezena do Ano do Vencimento) ou zeros
     * Posição 37 a 43 (07): Zeros (0000000)
     * Posição 44 a 44 (01): Identificador do Sistema ("0")
     *
     * @return string
     * @throws \Exception
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $agencia = Util::numberFormatGeral(substr($this->getAgencia(), -3), 3);
        $agenciaDv = Util::numberFormatGeral($this->getAgenciaDv(), 1);

        $nossoNumero = Util::numberFormatGeral($this->getNossoNumero(), 7);

        if ($this->getDataVencimento()) {
            $ddmm = $this->getDataVencimento()->format("dm");
            $aa = $this->getDataVencimento()->format("y");
        } else {
            $ddmm = "0000";
            $aa = "00";
        }

        $campoLivre = $agencia . $agenciaDv;
        $campoLivre .= $nossoNumero;
        $campoLivre .= $ddmm;
        $campoLivre .= $aa;
        $campoLivre .= "0000000";
        $campoLivre .= "0";

        return $this->campoLivre = $campoLivre;
    }

    /**
     * Método para decompor o campo livre de 20 a 44
     *
     * @param string $campoLivre
     * @return array
     */
    public static function parseCampoLivre($campoLivre)
    {
        return [
            "convenio"        => null,
            "agencia"         => substr($campoLivre, 0, 3),
            "agenciaDv"       => substr($campoLivre, 3, 1),
            "contaCorrente"   => null,
            "contaCorrenteDv" => null,
            "nossoNumero"     => substr($campoLivre, 4, 7),
            "nossoNumeroDv"   => null,
            "nossoNumeroFull" => substr($campoLivre, 4, 7),
            "carteira"        => "1",
        ];
    }
}
