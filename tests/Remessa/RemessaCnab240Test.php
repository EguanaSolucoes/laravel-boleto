<?php

namespace Eduardokum\LaravelBoleto\Tests\Remessa;

use Eduardokum\LaravelBoleto\Boleto\Banco as Boleto;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco as Remessa;
use Eduardokum\LaravelBoleto\Pessoa;
use Eduardokum\LaravelBoleto\Tests\TestCase;
use Eduardokum\LaravelBoleto\Util;

class RemessaCnab240Test extends TestCase
{

    protected static $pagador;
    protected static $beneficiario;

    public static function setUpBeforeClass(): void
    {
        self::$beneficiario = new Pessoa(
            [
                'nome' => 'ACME',
                'endereco' => 'Rua um, 123',
                'cep' => '99999-999',
                'uf' => 'UF',
                'cidade' => 'CIDADE',
                'documento' => '99.999.999/9999-99',
            ]
        );

        self::$pagador = new Pessoa(
            [
                'nome' => 'Cliente',
                'endereco' => 'Rua um, 123',
                'bairro' => 'Bairro',
                'cep' => '99999-999',
                'uf' => 'UF',
                'cidade' => 'CIDADE',
                'documento' => '999.999.999-99',
            ]
        );
    }

    public static function tearDownAfterClass(): void
    {
        $aFiles = [
            __DIR__,
            'files',
            'cnab240',
        ];
        $files = glob(implode(DIRECTORY_SEPARATOR, $aFiles) . '/*'); // get all file names
        foreach($files as $file){
            if(is_file($file))
                @unlink($file);
        }
    }
//
//    public function testRemessaSantanderCnab240(){
//        $boleto = new Boleto\Santander(
//            [
//                'logo' => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '033.png',
//                'dataVencimento' => new \Carbon\Carbon(),
//                'valor' => 100,
//                'multa' => false,
//                'juros' => false,
//                'numero' => 1,
//                'numeroDocumento' => 1,
//                'pagador' => self::$pagador,
//                'beneficiario' => self::$beneficiario,
//                'diasBaixaAutomatica' => 15,
//                'carteira' => 101,
//                'agencia' => 1111,
//                'conta' => 99999999,
//                'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
//                'instrucoes' =>  ['instrucao 1', 'instrucao 2', 'instrucao 3'],
//                'aceite' => 'S',
//                'especieDoc' => 'DM',
//            ]
//        );
//
//        $remessa = new Remessa\Santander(
//            [
//                'agencia' => 1111,
//                'carteira' => 101,
//                'conta' => 99999999,
//                'codigoCliente' => 12345678,
//                'beneficiario' => self::$beneficiario,
//            ]
//        );
//        $remessa->addBoleto($boleto);
//
//        $file = implode(DIRECTORY_SEPARATOR, [
//            __DIR__,
//            'files',
//            'cnab400',
//            'santander.txt'
//        ]);
//
//        $file2 = $remessa->save($file);
//
//        $this->assertFileExists($file);
//        $this->assertEquals($file, $file2);
//    }

    public function testRemessaItauCnab240(){
        $boleto = new Boleto\Itau([
            'logo' => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '033.png',
            'dataVencimento' => new \Carbon\Carbon(),
            'valor' => 100,
            'multa' => false,
            'juros' => false,
            'numero' => 1,
            'numeroDocumento' => 1,
            'pagador' => self::$pagador,
            'beneficiario' => self::$beneficiario,
            'diasBaixaAutomatica' => 0,
            'carteira' => '109',
            'agencia' => '9999',
            'conta' => '99999',
            'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
            'instrucoes' =>  ['instrucao 1', 'instrucao 2', 'instrucao 3'],
            'aceite' => 'S',
            'especieDoc' => 'DM',
        ]);

        $remessa = new Remessa\Itau([
            'agencia' => '9999',
            'carteira' => '109',
            'conta' => '99999',
            'beneficiario' => self::$beneficiario,
        ]);
        $remessa->addBoleto($boleto);

        $file = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'files',
            'cnab240',
            'itau.txt'
        ]);

        $file2 = $remessa->save($file);

        $this->assertFileExists($file);
        $this->assertEquals($file, $file2);
    }

    public function testRemessaCitibankCnab240()
    {
        $boleto = new Boleto\Citibank([
            'logo' => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '033.png',
            'dataVencimento' => new \Carbon\Carbon(),
            'valor' => 100,
            'multa' => false,
            'juros' => false,
            'numero' => 1,
            'numeroDocumento' => 1,
            'pagador' => self::$pagador,
            'beneficiario' => self::$beneficiario,
            'carteira' => '100',
            'convenio' => 123456,
            'agencia' => '1111',
            'conta' => '99999999',
            'aceite' => 'A',
            'especieDoc' => 'DMI',
        ]);

        $remessa = new Remessa\Citibank([
            'agencia' => '1111',
            'carteira' => 1,
            'conta' => '99999999',
            'convenio' => 123456,
            'idremessa' => 1,
            'beneficiario' => self::$beneficiario,
        ]);
        $remessa->addBoleto($boleto);

        $file = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'files',
            'cnab240',
            'citibank.txt'
        ]);

        $file2 = $remessa->save($file);

        $this->assertFileExists($file);
        $this->assertEquals($file, $file2);

        $linhas = explode("\r\n", $remessa->gerar());
        // Header de Lote é a 2ª linha (índice 1)
        $headerLote = $linhas[1];
        $this->assertEquals(240, mb_strlen($headerLote));
        // Mensagem 1: posições 104 a 143 (índices 103 a 142)
        $this->assertEquals(Util::formatCnab('X', 'COBRANCA', 40), mb_substr($headerLote, 103, 40));
        $this->assertStringNotContainsString('Ç', $headerLote);
        $this->assertStringNotContainsString('ç', $headerLote);
    }

    public function testRemessaBtgCnab240()
    {
        $boleto = new Boleto\Btg([
            'logo' => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '208.png',
            'dataVencimento' => new \Carbon\Carbon(),
            'valor' => 1500,
            'multa' => false,
            'juros' => false,
            'numero' => 109617746,
            'numeroDocumento' => 109617746,
            'pagador' => self::$pagador,
            'beneficiario' => self::$beneficiario,
            'carteira' => 1,
            'agencia' => '0001',
            'conta' => '1234567',
            'aceite' => 'N',
            'especieDoc' => 'DM',
        ]);

        $remessa = new Remessa\Btg([
            'agencia' => '0001',
            'carteira' => 1,
            'conta' => '1234567',
            'codigoCliente' => '123456789012',
            'idremessa' => 1,
            'beneficiario' => self::$beneficiario,
        ]);
        $remessa->addBoleto($boleto);

        $file = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'files',
            'cnab240',
            'btg.txt'
        ]);

        $file2 = $remessa->save($file);

        $this->assertFileExists($file);
        $this->assertEquals($file, $file2);

        $linhas = explode("\r\n", $remessa->gerar());
        $this->assertGreaterThanOrEqual(5, count($linhas));
        $segmentoP = $linhas[2];
        $this->assertEquals(240, mb_strlen($segmentoP));
        $this->assertEquals('01001096177463', trim(mb_substr($segmentoP, 37, 20)));
    }

    public function testRemessaBasaCnab240()
    {
        $boleto = new Boleto\Basa([
            'logo' => realpath(__DIR__ . '/../logos/') . DIRECTORY_SEPARATOR . '003.png',
            'dataVencimento' => new \Carbon\Carbon('2026-12-31'),
            'valor' => 100,
            'multa' => 2.0,
            'juros' => 1.0,
            'numero' => 9604832,
            'numeroDocumento' => 12345,
            'pagador' => self::$pagador,
            'beneficiario' => self::$beneficiario,
            'carteira' => '1',
            'convenio' => '12345',
            'agencia' => '017',
            'agenciaDv' => '5',
            'conta' => '737320',
            'contaDv' => '0',
            'aceite' => 'N',
            'especieDoc' => 'DM',
        ]);

        $remessa = new Remessa\Basa([
            'agencia' => '017',
            'agenciaDv' => '5',
            'carteira' => '1',
            'conta' => '737320',
            'contaDv' => '0',
            'convenio' => '12345',
            'idremessa' => 1,
            'beneficiario' => self::$beneficiario,
        ]);
        $remessa->addBoleto($boleto);

        $file = implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'files',
            'cnab240',
            'basa.txt'
        ]);

        $file2 = $remessa->save($file);

        $this->assertFileExists($file);
        $this->assertEquals($file, $file2);

        $linhas = explode("\r\n", $remessa->gerar());
        $this->assertGreaterThanOrEqual(7, count($linhas));

        // Header de Arquivo
        $this->assertEquals(240, mb_strlen($linhas[0]));
        $this->assertEquals('003', mb_substr($linhas[0], 0, 3));
        $this->assertEquals('090', mb_substr($linhas[0], 163, 3));

        // Header de Lote
        $this->assertEquals(240, mb_strlen($linhas[1]));
        $this->assertEquals('003', mb_substr($linhas[1], 0, 3));
        $this->assertEquals('047', mb_substr($linhas[1], 13, 3));

        // Segmento P
        $this->assertEquals(240, mb_strlen($linhas[2]));
        $this->assertEquals('P', mb_substr($linhas[2], 13, 1));

        // Segmento Q
        $this->assertEquals(240, mb_strlen($linhas[3]));
        $this->assertEquals('Q', mb_substr($linhas[3], 13, 1));

        // Segmento R
        $this->assertEquals(240, mb_strlen($linhas[4]));
        $this->assertEquals('R', mb_substr($linhas[4], 13, 1));

        // Trailer Lote
        $this->assertEquals(240, mb_strlen($linhas[5]));
        $this->assertEquals('5', mb_substr($linhas[5], 7, 1));

        // Trailer Arquivo
        $this->assertEquals(240, mb_strlen($linhas[6]));
        $this->assertEquals('9', mb_substr($linhas[6], 7, 1));
    }
}
