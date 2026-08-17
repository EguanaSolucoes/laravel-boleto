<?php

namespace Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab400\Banco;

use Illuminate\Support\Arr;
use Eduardokum\LaravelBoleto\Util;
use Eduardokum\LaravelBoleto\Contracts\Cnab\RetornoCnab400;
use Eduardokum\LaravelBoleto\Exception\ValidationException;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab400\AbstractRetorno;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;

/**
 * Layout proprietario Citibank (Manual CNAB 400 - Boleto Hibrido v2.10),
 * nao e o CNAB400 febraban padrao. Posicoes conferidas linha a linha contra
 * esse manual e contra arquivos de retorno reais do banco.
 *
 * Registro tipo 1 (posicoes 1/1 = "1"): confirmacao/liquidacao/ocorrencias do
 * boleto -- layout proprio, com Nosso Numero em 65/76 (nao 86/94 como no
 * Itau) e Codigo de Ocorrencia em 109/110.
 *
 * Registro tipo 6 (posicoes 1/1 = "6"): enviado logo apos o registro tipo 1
 * correspondente, so nos casos de boleto hibrido (BolePix). Traz em
 * 184/394 a string EMV do Pix ("copia e cola") ja pronta -- nao precisa
 * gerar, so extrair. Segue o mesmo padrao que Cnab400\Banco\Itau usa pro
 * registro de Pix dele (registro tipo 3 la): processa e devolve false pra
 * nao virar um Detalhe proprio, so anexa no Detalhe anterior.
 */
class Citibank extends AbstractRetorno implements RetornoCnab400
{
    /**
     * Codigo do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_CITIBANK;

    /**
     * Array com as ocorrencias do banco (Registro 1, posicao 109/110)
     *
     * @var array
     */
    private $ocorrencias = [
        '02' => 'Entrada confirmada',
        '03' => 'Transação rejeitada',
        '06' => 'Liquidação/pagamento',
        '07' => 'Desconto concedido',
        '10' => 'Baixa/Devolução',
        '11' => 'Em ser (a vencer)',
        '12' => 'Abatimento concedido',
        '14' => 'Vencimento e/ou valor alterado',
        '15' => 'Pago em cartório',
        '18' => 'Devolução por decurso de prazo',
        '19' => 'Confirmação recebimento da instrução de protesto',
        '20' => 'Confirmação recebimento da instrução de sustação/cancelamento de protesto',
        '21' => 'Confirmação de pedido de exclusão da Serasa',
        '22' => 'Boleto enviado para negativação',
        '23' => 'Boleto enviado a cartório',
        '26' => 'Instrução rejeitada',
        '29' => 'Alegação do sacado',
        '31' => 'Boleto negativado na Serasa',
        '34' => 'Boleto retirado de cartório',
        '51' => 'Custa de distribuição',
        '52' => 'Custa de sustação',
        '53' => 'Custa de protesto',
        '95' => 'Instrução negativação cumprida',
    ];

    /**
     * @param array $header
     *
     * @return bool
     * @throws ValidationException
     */
    protected function processarHeader(array $header)
    {
        $this->getHeader()
            ->setConvenio($this->rem(27, 46, $header))
            ->setData($this->rem(95, 100, $header));

        return true;
    }

    /**
     * @param array $detalhe
     *
     * @return bool
     * @throws ValidationException
     */
    protected function processarDetalhe(array $detalhe)
    {
        if ($this->rem(1, 1, $detalhe) == 6) {
            return $this->processarPix($detalhe);
        }

        $d = $this->detalheAtual();
        $d->setNumeroControle($this->rem(38, 62, $detalhe))
            ->setNossoNumero($this->rem(65, 76, $detalhe))
            ->setCarteira($this->rem(108, 108, $detalhe))
            ->setOcorrencia($this->rem(109, 110, $detalhe))
            ->setOcorrenciaDescricao(Arr::get($this->ocorrencias, $d->getOcorrencia(), 'Desconhecida'))
            ->setDataOcorrencia($this->rem(111, 116, $detalhe))
            ->setNumeroDocumento($this->rem(117, 126, $detalhe))
            ->setDataVencimento($this->rem(147, 152, $detalhe))
            ->setValor(Util::nFloat($this->rem(153, 165, $detalhe) / 100, 2, false))
            ->setValorAbatimento(Util::nFloat($this->rem(228, 240, $detalhe) / 100, 2, false))
            ->setValorDesconto(Util::nFloat($this->rem(241, 253, $detalhe) / 100, 2, false))
            ->setValorRecebido(Util::nFloat($this->rem(254, 266, $detalhe) / 100, 2, false))
            ->setValorMora(Util::nFloat($this->rem(267, 279, $detalhe) / 100, 2, false))
            ->setDataCredito($this->rem(296, 301, $detalhe));

        if ($d->hasOcorrencia('06', '15')) {
            $d->setOcorrenciaTipo($d::OCORRENCIA_LIQUIDADA);
        } elseif ($d->hasOcorrencia('02')) {
            $d->setOcorrenciaTipo($d::OCORRENCIA_ENTRADA);
        } elseif ($d->hasOcorrencia('10', '18')) {
            $d->setOcorrenciaTipo($d::OCORRENCIA_BAIXADA);
        } elseif ($d->hasOcorrencia('31')) {
            $d->setOcorrenciaTipo($d::OCORRENCIA_PROTESTADA);
        } elseif ($d->hasOcorrencia('14')) {
            $d->setOcorrenciaTipo($d::OCORRENCIA_ALTERACAO);
        } elseif ($d->hasOcorrencia('03', '26')) {
            $d->setError(trim($this->rem(302, 321, $detalhe)) ?: $d->getOcorrenciaDescricao());
        } else {
            $d->setOcorrenciaTipo($d::OCORRENCIA_OUTROS);
        }

        return true;
    }

    /**
     * Registro tipo 6 (ACK BolePix), sempre logo apos o registro tipo 1 que
     * ele complementa -- anexa a string Pix no Detalhe anterior (mesmo
     * padrao de Cnab400\Banco\Itau::processarPix) e descarta o proprio
     * registro (retorna false, o AbstractRetorno remove o Detalhe vazio que
     * teria sido criado pra essa linha).
     *
     * @param array $detalhe
     * @return bool
     * @throws ValidationException
     */
    private function processarPix(array $detalhe)
    {
        $d = $this->getDetalhe($this->increment - 1);

        $pix = trim($this->rem(184, 394, $detalhe));
        if ($pix !== '') {
            $d->setPixQrCode($pix);
        }

        return false;
    }

    /**
     * @param array $trailer
     *
     * @return bool
     * @throws ValidationException
     */
    protected function processarTrailer(array $trailer)
    {
        $this->getTrailer()
            ->setQuantidadeTitulos((int) $this->rem(18, 25, $trailer))
            ->setValorTitulos((float) Util::nFloat($this->rem(26, 39, $trailer) / 100, 2, false));

        return true;
    }
}
