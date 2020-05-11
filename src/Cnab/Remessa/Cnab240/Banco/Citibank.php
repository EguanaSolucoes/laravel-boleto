<?php
/**
 * Created by Wesley Serafim de Araújo.
 * User: wesleysaraujo
 * Email: wsadesigner@gmail.com
 * Date: 26/05/2019
 * Time: 21:33
 */

namespace Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco;

use Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\AbstractRemessa;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Contracts\Cnab\Remessa as RemessaContract;
use Eduardokum\LaravelBoleto\Util;

class Citibank extends AbstractRemessa implements RemessaContract
{
    const OCORRENCIA_REMESSA = '01';
    const OCORRENCIA_PEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '04';
    const OCORRENCIA_CANC_ABATIMENTO = '05';
    const OCORRENCIA_ALT_VENCIMENTO = '06';
    const OCORRENCIA_CONCESSAO_DESCONTO = '07';
    const OCORRENCIA_CANC_DESCONTO = '08';
    const OCORRENCIA_PROTESTAR = '09';
    const OCORRENCIA_SUSTAR_PROTESTO_BAIXAR_TITULO = '10';
    const OCORRENCIA_SUSTAR_PROTESTO_MANTER_CARTEIRA = '11';
    const OCORRENCIA_ALT_JUROS = '12';
    const OCORRENCIA_DISPENSA_JUROS = '13';
    const OCORRENCIA_ALT_VALOR_MULTA = '14';
    const OCORRENCIA_DISPENSA_COBRANCA_MULTA = '15';
    const OCORRENCIA_ALT_VALOR_DESCONTO = '16';
    const OCORRENCIA_NAO_CONCEDER_DESCONTO = '17';
    const OCORRENCIA_ALT_VALOR_ABATIMENTO= '18';
    const OCORRENCIA_ALT_DADOS_PAGADOR = '23';
    const OCORRENCIA_ENT_NEGATIVACAO = '29';
    const OCORRENCIA_ALT_DADOS_EXTRAS = '31';
    const OCORRENCIA_NAO_NEGATIVAR = '39';
    const OCORRENCIA_ALT_VALOR_NOMINAL = '47';
    const OCORRENCIA_ALT_VALOR_MINIMO = '48';
    const OCORRENCIA_ALT_VALOR_MAXIMO = '49';
    const OCORRENCIA_CANC_MIN_MAXIMO = '73';

    const PROTESTO_DIAS_CORRIDOS = '1';
    const PROTESTO_DIAS_UTEIS = '2';
    const PROTESTO_NAO_PROTESTAR = '3';
    const PROTESTO_NEGATIVAR_DIAS_CORRIDOS = '7';
    const PROTESTO_NAO_NEGATIVAR = '8';

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_CITIBANK;


    /**
     * Define as carteiras disponíveis para cada banco
     *
     * 1 - Cobrança Simples
     * 3 -  Cobranca Caucionada
     * 6 - Cobranca Flex Sem Pagamento parcial
     * 8 - Cobranca fex com pagamento parcial
     * @var array
     */
    protected $carteiras = [1,3,6,8];

    /**
     * Convenio com o banco
     *
     * @var string
     */
    protected $convenio;

    /**
     * @return mixed
     */
    public function getConvenio()
    {
        return $this->convenio;
    }

    /**
     * @param mixed $convenio
     *
     * @return convenio
     */
    public function setConvenio($convenio)
    {
        $this->convenio = ltrim($convenio, 0);

        return $this;
    }

    public function getCarteira()
    {
        return $this->carteira;
    }

    public function setCarteira($carteira)
    {
        $this->carteira = $carteira;

        return $this;
    }

    /**
     * Caracter de fim de linha
     *
     * @var string
     */
    protected $fimLinha = "\r\n";

    /**
     * Caracter de fim de arquivo
     *
     * @var null
     */
    protected $fimArquivo = "\n";

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->boletos[] = $boleto;
        $this->segmentoP($boleto);
        $this->segmentoQ($boleto);
        // $this->segmentoR($boleto);
        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    protected function segmentoP(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'P');
        $this->add(15, 15, '');
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
            $this->add(16, 17, sprintf('%2.02s', $boleto->getComando()));
        }
        $this->add(18, 22, Util::formatCnab('9', '0', 5));
        $this->add(23, 23, '0');
        $this->add(24, 35, '000000000000');
        $this->add(36, 36,'0');
        $this->add(37, 37, '0');
        $this->add(38, 49, Util::formatCnab('9', $boleto->getNossoNumero(), 12));
        $this->add(50, 57, '');
        $this->add(58, 58, '1');
        $this->add(59, 59, '1');
        $this->add(60, 60, '');
        $this->add(61, 61, '2');
        $this->add(62, 62, '2');
        $this->add(63, 72, Util::formatCnab('9', $boleto->getNumeroDocumento(), 10));
        $this->add(73, 77, '');
        $this->add(78, 85, $boleto->getDataVencimento()->format('dmY'));
        $this->add(86, 87,'00');
        $this->add(88, 100, Util::formatCnab('9', $boleto->getValor(), 13, 2));
        $this->add(101, 105, '00000');
        $this->add(106, 106, '0');
        $this->add(107, 108, '03');
        $this->add(109, 109, Util::formatCnab('X', $boleto->getAceite() == 'N' ? 'N' : 'A', 1));    //N = Não Aceita     A = Aceite
        $this->add(110, 117, $boleto->getDataDocumento()->format('dmY'));
        $this->add(118, 118, ($boleto->getJuros() !== null && $boleto->getJuros() > 0) ? '2' : '3');    //0 = ISENTO | 1 = R$ ao dia | 2 = % ao mês
        $this->add(119, 126, $boleto->getDataVencimento()->format('dmY'));
        $this->add(127, 128, '00');
        $this->add(129, 141, Util::formatCnab('9', $boleto->getJuros(), 13, 2)); //Taxa mensal
        $this->add(142, 142, $boleto->getDesconto() > 0  ? '1' : '0'); //0 = SEM DESCONTO | 1 = VALOR FIXO | 2 = PERCENTUAL
        $this->add(143, 150, $boleto->getDesconto() > 0 ? $boleto->getDataDesconto()->format('dmY') : '00000000');
        $this->add(151, 152, '00');
        $this->add(153, 165, Util::formatCnab('9', $boleto->getDesconto(), 13, 2));
        $this->add(166, 167, '00');
        $this->add(168, 180, Util::formatCnab('9', 0, 13, 2));
        $this->add(181, 182, '00');
        $this->add(183, 195, Util::formatCnab('9', 0, 13, 2));
        $this->add(196, 220, Util::formatCnab('X', $boleto->getNumeroControle(), 25));
        $this->add(221, 221, self::PROTESTO_NAO_PROTESTAR);
        if ($boleto->getDiasProtesto() > 0) {
            $this->add(221, 221, self::PROTESTO_DIAS_UTEIS);
        }
        $this->add(222, 223, Util::formatCnab('9', $boleto->getDiasProtesto(), 2));
        $this->add(224, 224, '2');
        $this->add(225, 227, '');
        $this->add(228, 229, '09');
        $this->add(230, 239, '0000000000');
        $this->add(240, 240, '');

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoQ(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'Q');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_DADOS_PAGADOR);
        }
        $this->add(18, 18, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 33, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15));
        $this->add(34, 73, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40));
        $this->add(74, 113, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40));
        $this->add(114, 128, Util::formatCnab('X', $boleto->getPagador()->getBairro(), 15));
        $this->add(129, 133, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getCep()), 5));
        $this->add(134, 136, Util::formatCnab('9', Util::onlyNumbers(substr($boleto->getPagador()->getCep(), 6, 9)), 3));
        $this->add(137, 151, Util::formatCnab('X', $boleto->getPagador()->getCidade(), 15));
        $this->add(152, 153, Util::formatCnab('X', $boleto->getPagador()->getUf(), 2));
        $this->add(154, 154, '0');
        $this->add(155, 169, '000000000000000');
        $this->add(170, 209, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40));
        $this->add(210, 219, '0000000000');
        $this->add(220, 220, '0');
        $this->add(221, 232,'');
        $this->add(233, 240,'');

//        if($boleto->getSacadorAvalista()) {
//            $this->add(154, 154, strlen(Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento())) == 14 ? 2 : 1);
//            $this->add(155, 169, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento()), 15));
//            $this->add(170, 209, Util::formatCnab('X', $boleto->getSacadorAvalista()->getNome(), 40));
//        }

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoR(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'R');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_DADOS_PAGADOR);
        }
        $this->add(18, 18, '0');
        $this->add(19, 26, '00000000');
        $this->add(27, 28, '00');
        $this->add(29, 41, Util::formatCnab('9', '', 13, 2));
        $this->add(42, 42, '0');
        $this->add(43, 50, Util::formatCnab('9', '0', 8, 2));
        $this->add(51, 52, '00');
        $this->add(53, 65, Util::formatCnab('9', '0', 13, 2));
        $this->add(66, 66, $boleto->getMulta() > 0 ? '2' : '0'); //0 = ISENTO | 1 = VALOR FIXO | 2 = PERCENTUAL
        $this->add(67, 74, $boleto->getDataVencimento()->format('dmY'));
        $this->add(75, 76, '00');
        $this->add(77, 89, Util::formatCnab('9', $boleto->getMulta(), 13, 2));  //2,20 = 0000000000220
        $this->add(90, 199, '');
        $this->add(200, 207, '00000000');
        $this->add(208, 210, '000');
        $this->add(211, 215, '00000');
        $this->add(216, 216, '0');
        $this->add(217, 228, '000000000000');
        $this->add(229, 230, '0');
        $this->add(231, 231, '0');
        $this->add(232, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function header()
    {
        $this->iniciaHeader();

        /**
         * HEADER DE ARQUIVO
         */
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0000');
        $this->add(8, 8, '0');
        $this->add(9, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 52, Util::formatCnab('X', $this->getConvenio(), 20));
        $this->add(53, 57, '00000');
        $this->add(58, 58, '0');
        $this->add(59, 70,  '000000000000');
        $this->add(71, 71, '0');
        $this->add(72, 72, '0');
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 132, Util::formatCnab('X', 'CITIBANK', 30));
        $this->add(133, 142, '');
        $this->add(143, 143, 1);
        $this->add(144, 151, date('dmY'));
        $this->add(152, 157, date('His'));
        $this->add(158, 163, Util::formatCnab('9', $this->getIdremessa(), 6));
        $this->add(164, 166, '083');
        $this->add(167, 171, '01600');
        $this->add(172, 191, '');
        $this->add(192, 211, '');
        $this->add(212, 240, '');
        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function headerLote()
    {
        $this->iniciaHeaderLote();

        /**
         * HEADER DE LOTE
         */
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '1');
        $this->add(9, 9, 'R');
        $this->add(10, 11, '01');
        $this->add(12, 13, '');
        $this->add(14, 16, '041');
        $this->add(17, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 33, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 15));
        $this->add(34, 53, Util::onlyNumbers($this->getConvenio()));
        $this->add(54, 58, '00000');
        $this->add(59, 59, '0');
        $this->add(60, 71, '000000000000');
        $this->add(72, 72, '0');
        $this->add(73, 73, '0');
        $this->add(74, 103, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(104, 143, 'Cobrança');
        $this->add(144, 183, '');
        $this->add(184, 191, Util::formatCnab('9', $this->getIdremessa(), 8));
        $this->add(192, 199, $this->getDataRemessa('dmY'));
        $this->add(200, 207, '00000000');
        $this->add(208, 241, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function trailerLote()
    {
        $this->iniciaTrailerLote();

        $valor = array_reduce($this->boletos, function($valor, $boleto) {
            return $valor + $boleto->getValor();
        }, 0);

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '5');
        $this->add(9, 17, '');
        $this->add(18, 23, Util::formatCnab('9', $this->getCountDetalhes() + 2, 6));
        $this->add(24, 29, Util::formatCnab('9', count($this->boletos), 6));
        $this->add(30, 46, Util::formatCnab('9', $valor, 17, 2));
        $this->add(47, 52, '000000');
        $this->add(53, 69, '00000000000000000');
        $this->add(70, 75, '000000');
        $this->add(76, 92, '00000000000000000');
        $this->add(93, 98, '000000');
        $this->add(99, 115, '00000000000000000');
        $this->add(116, 123, Util::formatCnab('9', '0', 8));
        $this->add(124, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '9999');
        $this->add(8, 8, '9');
        $this->add(9, 17, '');
        $this->add(18, 23, '000001');
        $this->add(24, 29, Util::formatCnab('9', $this->getCountDetalhes() + 4, 6));
        $this->add(30, 35, '000000');
        $this->add(36, 240, '');

        return $this;
    }
}
