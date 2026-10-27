<?php
namespace Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco;

use Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\AbstractRemessa;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Contracts\Cnab\Remessa as RemessaContract;
use Eduardokum\LaravelBoleto\Util;

class Basa extends AbstractRemessa implements RemessaContract
{
    const OCORRENCIA_REMESSA = "01";
    const OCORRENCIA_PEDIDO_BAIXA = "02";
    const OCORRENCIA_CONCESSAO_ABATIMENTO = "04";
    const OCORRENCIA_CANC_ABATIMENTO = "05";
    const OCORRENCIA_ALT_VENCIMENTO = "06";
    const OCORRENCIA_CONCESSAO_DESCONTO = "07";
    const OCORRENCIA_CANC_DESCONTO = "08";
    const OCORRENCIA_PROTESTAR = "09";
    const OCORRENCIA_SUSTAR_PROTESTO_BAIXAR_TITULO = "10";
    const OCORRENCIA_SUSTAR_PROTESTO_MANTER_CARTEIRA = "11";
    const OCORRENCIA_ALT_JUROS = "12";
    const OCORRENCIA_DISPENSA_JUROS = "13";
    const OCORRENCIA_ALT_VALOR_MULTA = "14";
    const OCORRENCIA_DISPENSA_COBRANCA_MULTA = "15";
    const OCORRENCIA_ALT_VALOR_DESCONTO = "16";
    const OCORRENCIA_NAO_CONCEDER_DESCONTO = "17";
    const OCORRENCIA_ALT_VALOR_ABATIMENTO = "18";
    const OCORRENCIA_ALT_DADOS_PAGADOR = "23";
    const OCORRENCIA_ALT_DADOS_EXTRAS = "31";
    const OCORRENCIA_ALT_VALOR_NOMINAL = "47";

    const PROTESTO_DIAS_CORRIDOS = "1";
    const PROTESTO_DIAS_UTEIS = "2";
    const PROTESTO_NAO_PROTESTAR = "3";

    const BAIXA_DEVOLVER = "1";
    const BAIXA_NAO_DEVOLVER = "2";

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_BASA;

    /**
     * Carteiras disponíveis
     *
     * @var array
     */
    protected $carteiras = ["1"];

    /**
     * Característica da cobrança
     *
     * @var string
     */
    protected $carteira = "1";

    /**
     * Convênio
     *
     * @var string
     */
    protected $convenio;

    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->addCampoObrigatorio("convenio", "agenciaDv", "contaDv");
    }

    /**
     * Define o número do convênio
     *
     * @param string $convenio
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
     * @param BoletoContract $boleto
     * @return $this
     * @throws \Exception
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->boletos[] = $boleto;
        $this->segmentoP($boleto);
        $this->segmentoQ($boleto);
        if ($boleto->getMulta() > 0) {
            $this->segmentoR($boleto);
        }
        return $this;
    }

    /**
     * Segmento P
     *
     * @param BoletoContract $boleto
     * @return $this
     * @throws \Exception
     */
    protected function segmentoP(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "0001");
        $this->add(8, 8, "3");
        $this->add(9, 13, Util::formatCnab("9", $this->iRegistrosLote, 5));
        $this->add(14, 14, "P");
        $this->add(15, 15, "");
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_DADOS_PAGADOR);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO_DATA) {
            $this->add(16, 17, self::OCORRENCIA_ALT_VENCIMENTO);
        }
        if ($boleto->getStatus() == $boleto::STATUS_CUSTOM) {
            $this->add(16, 17, sprintf("%2.02s", $boleto->getComando()));
        }

        // C/C Cedente
        $this->add(18, 22, Util::formatCnab("9", $this->getAgencia(), 5));
        $this->add(23, 23, Util::formatCnab("X", $this->getAgenciaDv(), 1));
        $this->add(24, 35, Util::formatCnab("9", $this->getConta(), 12));
        $this->add(36, 36, Util::formatCnab("X", $this->getContaDv(), 1));
        $this->add(37, 37, "");

        // Nosso Número: 20 posições (AAACCCCCNNNNNNNNNNNN conforme G069)
        $nossoNumero = $boleto->getNossoNumero();
        if (strlen($nossoNumero) >= 20) {
            $identificacaoTitulo = Util::formatCnab("X", $nossoNumero, 20);
        } else {
            $agencia3 = Util::formatCnab("9", substr($this->getAgencia(), -3), 3);
            $convenio5 = Util::formatCnab("9", $this->getConvenio(), 5);
            $sequencial12 = Util::formatCnab("9", $nossoNumero, 12);
            $identificacaoTitulo = $agencia3 . $convenio5 . $sequencial12;
        }
        $this->add(38, 57, $identificacaoTitulo);

        // Características da cobrança
        $this->add(58, 58, Util::formatCnab("9", $this->getCarteira(), 1)); // 1 = Cobrança Simples
        $this->add(59, 59, "1"); // 1 = Com cadastramento (Cobrança Registrada)
        $this->add(60, 60, "1"); // 1 = Tradicional
        $this->add(61, 61, "2"); // 2 = Cliente Emite
        $this->add(62, 62, "2"); // 2 = Cliente Distribui

        // Documento
        $this->add(63, 77, Util::formatCnab("X", $boleto->getNumeroDocumento(), 15));
        $this->add(78, 85, $boleto->getDataVencimento()->format("dmY"));
        $this->add(86, 100, Util::formatCnab("9", $boleto->getValor(), 15, 2));
        $this->add(101, 105, "00000");
        $this->add(106, 106, "");
        $this->add(107, 108, Util::formatCnab("9", $boleto->getEspecieDocCodigo(), 2));
        $this->add(109, 109, Util::formatCnab("X", $boleto->getAceite() == "A" ? "A" : "N", 1));
        $this->add(110, 117, $boleto->getDataDocumento()->format("dmY"));

        // Juros de Mora
        $codJuros = "3";
        $dataJuros = "00000000";
        $valorJuros = 0;
        if ($boleto->getMoraDia() > 0) {
            $codJuros = "1"; // Valor por dia
            $valorJuros = $boleto->getMoraDia();
            $dataJuros = $boleto->getDataVencimento()->copy()->addDay()->format("dmY");
        } elseif ($boleto->getJuros() > 0) {
            $codJuros = "2"; // Taxa mensal
            $valorJuros = $boleto->getJuros();
            $dataJuros = $boleto->getDataVencimento()->copy()->addDay()->format("dmY");
        }
        $this->add(118, 118, $codJuros);
        $this->add(119, 126, $dataJuros);
        $this->add(127, 141, Util::formatCnab("9", $valorJuros, 15, 2));

        // Desconto
        $codDesconto = "0";
        $dataDesconto = "00000000";
        $valorDesconto = 0;
        if ($boleto->getDesconto() > 0) {
            $codDesconto = "1"; // Valor fixo até a data
            $valorDesconto = $boleto->getDesconto();
            $dataDesconto = $boleto->getDataDesconto() ? $boleto->getDataDesconto()->format("dmY") : $boleto->getDataVencimento()->format("dmY");
        }
        $this->add(142, 142, $codDesconto);
        $this->add(143, 150, $dataDesconto);
        $this->add(151, 165, Util::formatCnab("9", $valorDesconto, 15, 2));

        $this->add(166, 180, Util::formatCnab("9", 0, 15, 2)); // IOF
        $this->add(181, 195, Util::formatCnab("9", 0, 15, 2)); // Abatimento
        $this->add(196, 220, Util::formatCnab("X", $boleto->getNumeroDocumento(), 25)); // Identificação na empresa

        // Protesto
        $codProtesto = self::PROTESTO_NAO_PROTESTAR;
        $diasProtesto = 0;
        if ($boleto->getDiasProtesto() > 0) {
            $codProtesto = self::PROTESTO_DIAS_CORRIDOS;
            $diasProtesto = $boleto->getDiasProtesto();
        }
        $this->add(221, 221, $codProtesto);
        $this->add(222, 223, Util::formatCnab("9", $diasProtesto, 2));

        // Baixa / Devolução
        $codBaixa = self::BAIXA_NAO_DEVOLVER;
        $diasBaixa = "060"; // Default do manual pág. 17 é 60 dias
        if ($boleto->getDiasBaixaAutomatica() > 0) {
            $codBaixa = self::BAIXA_DEVOLVER;
            $diasBaixa = Util::formatCnab("9", $boleto->getDiasBaixaAutomatica(), 3);
        }
        $this->add(224, 224, $codBaixa);
        $this->add(225, 227, $diasBaixa);

        $this->add(228, 229, "09"); // Moeda Real
        $this->add(230, 239, "0000000000"); // Nº Contrato
        $this->add(240, 240, ""); // Uso livre

        return $this;
    }

    /**
     * Segmento Q
     *
     * @param BoletoContract $boleto
     * @return $this
     * @throws \Exception
     */
    public function segmentoQ(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "0001");
        $this->add(8, 8, "3");
        $this->add(9, 13, Util::formatCnab("9", $this->iRegistrosLote, 5));
        $this->add(14, 14, "Q");
        $this->add(15, 15, "");
        $this->add(16, 17, self::OCORRENCIA_REMESSA);

        // Pagador
        $tipoDocPagador = strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? "2" : "1";
        $this->add(18, 18, $tipoDocPagador);
        $this->add(19, 33, Util::formatCnab("9", Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15));
        $this->add(34, 73, Util::formatCnab("X", $boleto->getPagador()->getNome(), 40));
        $this->add(74, 113, Util::formatCnab("X", $boleto->getPagador()->getEndereco(), 40));
        $this->add(114, 128, Util::formatCnab("X", $boleto->getPagador()->getBairro(), 15));

        $cepCompleto = Util::onlyNumbers($boleto->getPagador()->getCep());
        $cep = substr($cepCompleto, 0, 5);
        $cepSufixo = substr($cepCompleto, 5, 3);
        $this->add(129, 133, Util::formatCnab("9", $cep, 5));
        $this->add(134, 136, Util::formatCnab("9", $cepSufixo, 3));
        $this->add(137, 151, Util::formatCnab("X", $boleto->getPagador()->getCidade(), 15));
        $this->add(152, 153, Util::formatCnab("X", $boleto->getPagador()->getUf(), 2));

        // Sacador / Avalista
        $this->add(154, 154, "0");
        $this->add(155, 169, Util::formatCnab("9", "0", 15));
        $this->add(170, 209, "");
        $this->add(210, 212, "000");
        $this->add(213, 232, "");
        $this->add(233, 240, "");

        return $this;
    }

    /**
     * Segmento R
     *
     * @param BoletoContract $boleto
     * @return $this
     * @throws \Exception
     */
    public function segmentoR(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "0001");
        $this->add(8, 8, "3");
        $this->add(9, 13, Util::formatCnab("9", $this->iRegistrosLote, 5));
        $this->add(14, 14, "R");
        $this->add(15, 15, "");
        $this->add(16, 17, self::OCORRENCIA_REMESSA);

        // Desconto 2 e 3
        $this->add(18, 18, "0");
        $this->add(19, 26, "00000000");
        $this->add(27, 41, Util::formatCnab("9", 0, 15, 2));
        $this->add(42, 42, "0");
        $this->add(43, 50, "00000000");
        $this->add(51, 65, Util::formatCnab("9", 0, 15, 2));

        // Multa
        $codMulta = "2"; // 2 = Percentual
        $dataMulta = $boleto->getDataVencimento()->copy()->addDay()->format("dmY");
        $valorMulta = $boleto->getMulta();
        $this->add(66, 66, $codMulta);
        $this->add(67, 74, $dataMulta);
        $this->add(75, 89, Util::formatCnab("9", $valorMulta, 15, 2));

        $this->add(90, 99, "");
        $this->add(100, 139, "");
        $this->add(140, 179, "");
        $this->add(180, 199, "");
        $this->add(200, 207, "00000000");
        $this->add(208, 210, "000");
        $this->add(211, 215, "00000");
        $this->add(216, 216, "");
        $this->add(217, 228, "000000000000");
        $this->add(229, 229, "");
        $this->add(230, 230, "");
        $this->add(231, 231, "0");
        $this->add(232, 240, "");

        return $this;
    }

    /**
     * Header de Arquivo
     *
     * @return $this
     * @throws \Exception
     */
    protected function header()
    {
        $this->iniciaHeader();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "0000");
        $this->add(8, 8, "0");
        $this->add(9, 17, "");

        // Empresa
        $tipoDocBeneficiario = strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? "2" : "1";
        $this->add(18, 18, $tipoDocBeneficiario);
        $this->add(19, 32, Util::formatCnab("9", Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 52, Util::formatCnab("X", $this->getConvenio(), 20));

        // Conta mantenedora
        $this->add(53, 57, Util::formatCnab("9", $this->getAgencia(), 5));
        $this->add(58, 58, Util::formatCnab("X", $this->getAgenciaDv(), 1));
        $this->add(59, 70, Util::formatCnab("9", $this->getConta(), 12));
        $this->add(71, 71, Util::formatCnab("X", $this->getContaDv(), 1));
        $this->add(72, 72, "");

        $this->add(73, 102, Util::formatCnab("X", $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 132, Util::formatCnab("X", "BANCO DA AMAZONIA", 30));
        $this->add(133, 142, "");
        $this->add(143, 143, "1"); // 1 = Remessa
        $this->add(144, 151, date("dmY"));
        $this->add(152, 157, date("His"));
        $this->add(158, 163, Util::formatCnab("9", $this->getIdremessa(), 6));
        $this->add(164, 166, "090"); // Versão do Layout do Arquivo
        $this->add(167, 171, "00000");
        $this->add(172, 191, "");
        $this->add(192, 211, "");
        $this->add(212, 240, "");

        return $this;
    }

    /**
     * Header de Lote
     *
     * @return $this
     * @throws \Exception
     */
    protected function headerLote()
    {
        $this->iniciaHeaderLote();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "0001");
        $this->add(8, 8, "1");
        $this->add(9, 9, "R");
        $this->add(10, 11, "01"); // 01 = Cobrança
        $this->add(12, 13, "");
        $this->add(14, 16, "047"); // Versão Layout Lote
        $this->add(17, 17, "");

        $tipoDocBeneficiario = strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? "2" : "1";
        $this->add(18, 18, $tipoDocBeneficiario);
        $this->add(19, 33, Util::formatCnab("9", Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 15));
        $this->add(34, 53, Util::formatCnab("X", $this->getConvenio(), 20));

        $this->add(54, 58, Util::formatCnab("9", $this->getAgencia(), 5));
        $this->add(59, 59, Util::formatCnab("X", $this->getAgenciaDv(), 1));
        $this->add(60, 71, Util::formatCnab("9", $this->getConta(), 12));
        $this->add(72, 72, Util::formatCnab("X", $this->getContaDv(), 1));
        $this->add(73, 73, "");

        $this->add(74, 103, Util::formatCnab("X", $this->getBeneficiario()->getNome(), 30));
        $this->add(104, 143, Util::formatCnab("X", "COBRANCA", 40));
        $this->add(144, 183, "");
        $this->add(184, 191, Util::formatCnab("9", $this->getIdremessa(), 8));
        $this->add(192, 199, $this->getDataRemessa("dmY"));
        $this->add(200, 207, "00000000");
        $this->add(208, 240, "");

        return $this;
    }

    /**
     * Trailer de Lote
     *
     * @return $this
     * @throws \Exception
     */
    protected function trailerLote()
    {
        $this->iniciaTrailerLote();

        $valor = array_reduce($this->boletos, function ($valor, $boleto) {
            return $valor + $boleto->getValor();
        }, 0);

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "0001");
        $this->add(8, 8, "5");
        $this->add(9, 17, "");
        $this->add(18, 23, Util::formatCnab("9", $this->getCountDetalhes() + 2, 6));
        $this->add(24, 29, Util::formatCnab("9", count($this->boletos), 6));
        $this->add(30, 46, Util::formatCnab("9", $valor, 17, 2));
        $this->add(47, 52, "000000");
        $this->add(53, 69, "00000000000000000");
        $this->add(70, 75, "000000");
        $this->add(76, 92, "00000000000000000");
        $this->add(93, 98, "000000");
        $this->add(99, 115, "00000000000000000");
        $this->add(116, 123, Util::formatCnab("9", "0", 8));
        $this->add(124, 240, "");

        return $this;
    }

    /**
     * Trailer de Arquivo
     *
     * @return $this
     * @throws \Exception
     */
    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, "9999");
        $this->add(8, 8, "9");
        $this->add(9, 17, "");
        $this->add(18, 23, "000001"); // 1 lote
        $this->add(24, 29, Util::formatCnab("9", $this->getCountDetalhes() + 4, 6)); // Lote + Headers + Trailers
        $this->add(30, 35, "000000");
        $this->add(36, 240, "");

        return $this;
    }
}
