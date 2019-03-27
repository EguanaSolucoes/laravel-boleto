<?php
namespace Eduardokum\LaravelBoleto\Boleto\Banco;

use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\CalculoDV;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto;
use Eduardokum\LaravelBoleto\Util;

class Citibank  extends AbstractBoleto implements BoletoContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        // $this->addCampoObrigatorio('convenio');
    }

    protected $aceite = 'N';

    protected $usoBanco = 'CLIENTE RCO';

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = Boleto::COD_BANCO_CITIBANK;
    /**
     * Define as carteiras disponíveis para este banco
     * '100' => Com registro
     *
     * @var array
     */
    protected $carteiras = ['100', '180'];
    /**
     * Trata-se de código utilizado para identificar mensagens especificas ao cedente, sendo
     * que o mesmo consta no cadastro do Banco, quando não houver código cadastrado preencher
     * com zeros "000".
     *
     * @var int
     */
    protected $cip = '';

    /**
     * Define o número do convênio (4, 6 ou 7 caracteres)
     *
     * @var string
     */
    protected $convenio;
    /**
     * Define o número do convênio. Sempre use string pois a quantidade de caracteres é validada.
     *
     * @param  string $convenio
     * @return Citibank
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
     * Código do cliente.
     *
     * @var int
     */
    protected $codigoCliente;

    /**
     * Variaveis adicionais.
     *
     * @var array
     */
    public $variaveis_adicionais = [
        'cip' => '000',
        'mostra_cip' => false,
    ];

    /**
     * Espécie do documento, coódigo para remessa
     *
     * @var string
     */
    protected $especiesCodigo = [
        'DMI' => '01',
    ];

    /**
     * Espécie do documento, geralmente DM (Duplicata Mercantil)
     *
     * @var string
     */
    protected $especieDoc = 'DMI';

    /**
     * Gera o Nosso Número.
     *
     * @return string
     */
    protected function gerarNossoNumero()
    {
        return Util::numberFormatGeral($this->getNumero(), 11)
            . CalculoDV::citibankNossoNumero($this->getNumero());
    }

    /**
     * Seta dias para baixa automática
     *
     * @param int $baixaAutomatica
     *
     * @return $this
     * @throws \Exception
     */
    public function setDiasBaixaAutomatica($baixaAutomatica)
    {
        if ($this->getDiasProtesto() > 0) {
            throw new \Exception('Você deve usar dias de protesto ou dias de baixa, nunca os 2');
        }
        $baixaAutomatica = (int) $baixaAutomatica;
        $this->diasBaixaAutomatica = $baixaAutomatica > 0 ? $baixaAutomatica : 0;
        return $this;
    }

    /**
     * Retorna o código do cliente.
     *
     * @return int
     */
    public function getCodigoCliente()
    {
        return $this->codigoCliente;
    }

    /**
     * Define o código do cliente.
     *
     * @param int $codigoCliente
     *
     * @return AbstractBoleto
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }

    /**
     * Método que retorna o nosso numero usado no boleto. alguns bancos possuem algumas diferenças.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return substr_replace($this->getNossoNumero(), '-', -1, 0);
    }
    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $campoLivre = "3";
        $campoLivre .= Util::numberFormatGeral($this->getCarteira(), 3);
        $campoLivre .= Util::numberFormatGeral(substr($this->getCodigoCliente(), 1, 9), 9);
        $campoLivre .= Util::numberFormatGeral($this->getNossoNumero(), 12);

        return $this->campoLivre = $campoLivre;
    }

    /**
     * Método onde qualquer boleto deve extender para gerar o código da posição de 20 a 44
     *
     * @param $campoLivre
     *
     * @return array
     */
    public static function parseCampoLivre($campoLivre) {
        return [
            'convenio' => null,
            'agencia' => null,
            'agenciaDv' => null,
            'contaCorrente' => null,
            'contaCorrenteDv' => null,
            'codigoCliente' => substr($campoLivre, 4, 12),
            'nossoNumero' => substr($campoLivre, 13, 12),
            'nossoNumeroDv' => substr($campoLivre, 14, 1),
            'nossoNumeroFull' => substr($campoLivre, 13, 13),
            'carteira' => substr($campoLivre, 1, 3),
        ];
    }

    /**
     * Agência/Código do Beneficiário: Informar o prefixo da agência e o código de associado/cliente.
     * Estes dados constam na planilha "Capa" deste arquivo. O código de cliente não deve ser
     * confundido com o número da conta corrente, pois são códigos diferentes.
     * @return string
     */
    public function getAgenciaCodigoBeneficiario(){
        return $this->getAgencia() . ' / ' . $this->getCodigoCliente();
    }

    /**
     * Retorna a linha digitável do boleto
     *
     * @return string
     * @throws \Exception
     */
    public function getLinhaDigitavel()
    {
        if (!empty($this->campoLinhaDigitavel)) {
            return $this->campoLinhaDigitavel;
        }

        $codigo = $this->getCodigoBarras();
        $contaCosmos = $this->getCodigoCliente();

        $s1 = substr($codigo, 0, 4) . "3" . $this->carteira . substr($contaCosmos, 0, 1);
        $s1 = $s1 . Util::modulo10($s1);
        $s1 = substr_replace($s1, '.', 5, 0);

        $s2 = substr($contaCosmos, 2, 8) . substr($this->getNossoNumero(), 0, 2);
        $s2 = $s2 . Util::modulo10($s2);
        $s2 = substr_replace($s2, '.', 5, 0);

        $s3 = substr(Util::numberFormatGeral($this->getNossoNumero(), 12), 2, 10);
        $s3 = $s3 . Util::modulo10($s3);
        $s3 = substr_replace($s3, '.', 5, 0);

        $s4 = substr($codigo, 4, 1);

        $s5 = substr($codigo, 5, 14);

        return $this->campoLinhaDigitavel = sprintf('%s %s %s %s %s', $s1, $s2, $s3, $s4, $s5);
    }

    /**
     * Define o campo Espécie Doc, Citibank sempre DMI
     *
     * @param  string $especieDoc
     * @return AbstractBoleto
     */
    public function setEspecieDoc($especieDoc)
    {
        $this->especieDoc = 'DMI';
        return $this;
    }

    /**
     * Define o campo UsoBanco, CITIBANK sempre RCO
     *
     * @param  string $usoBanco
     * @return AbstractBoleto
     */
    public function setUsoBanco($usoBanco)
    {
        $this->usoBanco = 'CLIENTE RCO';
        return $this;
    }

    /**
     * Define o campo Aceite, CITIBANK sempre PD
     *
     * @param  string $aceite
     * @return AbstractBoleto
     */
    public function setAceite($aceite)
    {
        $this->aceite = 'N';
        return $this;
    }
}
