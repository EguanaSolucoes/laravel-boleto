<?php

namespace Eduardokum\LaravelBoleto\Tests\Boleto;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Eduardokum\LaravelBoleto\Pessoa;
use Eduardokum\LaravelBoleto\Boleto\Banco\Banrisul;
use Eduardokum\LaravelBoleto\Boleto\Render\Pdf;

class PdfHideInfoEmpresaProxy extends Pdf
{
    public function renderLogoEmpresa($index)
    {
        $this->logoEmpresa($index);
    }

    public function getPageContent($page = 1)
    {
        return $this->pages[$page] ?? '';
    }
}

class PdfHideInfoEmpresaTest extends TestCase
{
    public function testHideInfoEmpresaOcultaDadosSemAlterarEspacamentoDoBloco()
    {
        $boleto = $this->makeBanrisulBoleto();

        $pdfComInfo = new PdfHideInfoEmpresaProxy();
        $pdfComInfo->addBoleto($boleto);
        $pdfComInfo->AddPage();
        $yInicialComInfo = $pdfComInfo->GetY();
        $pdfComInfo->renderLogoEmpresa(0);
        $espacoComInfo = $pdfComInfo->GetY() - $yInicialComInfo;
        $conteudoComInfo = $pdfComInfo->getPageContent();

        $pdfSemInfo = new PdfHideInfoEmpresaProxy();
        $pdfSemInfo->addBoleto($boleto)->hideInfoEmpresa();
        $pdfSemInfo->AddPage();
        $yInicialSemInfo = $pdfSemInfo->GetY();
        $pdfSemInfo->renderLogoEmpresa(0);
        $espacoSemInfo = $pdfSemInfo->GetY() - $yInicialSemInfo;
        $conteudoSemInfo = $pdfSemInfo->getPageContent();

        $this->assertEquals($espacoComInfo, $espacoSemInfo);
        $this->assertStringContainsString('ACME', $conteudoComInfo);
        $this->assertStringContainsString('Rua um, 123', $conteudoComInfo);
        $this->assertStringNotContainsString('ACME', $conteudoSemInfo);
        $this->assertStringNotContainsString('Rua um, 123', $conteudoSemInfo);
    }

    private function makeBanrisulBoleto()
    {
        $beneficiario = new Pessoa([
            'nome'      => 'ACME',
            'endereco'  => 'Rua um, 123',
            'cep'       => '99999-999',
            'uf'        => 'UF',
            'cidade'    => 'CIDADE',
            'documento' => '99.999.999/9999-99',
        ]);

        $pagador = new Pessoa([
            'nome'      => 'Cliente',
            'endereco'  => 'Rua um, 123',
            'bairro'    => 'Bairro',
            'cep'       => '99999-999',
            'uf'        => 'UF',
            'cidade'    => 'CIDADE',
            'documento' => '999.999.999-99',
        ]);

        return new Banrisul([
            'logo'                   => realpath(__DIR__ . '/../../logos/') . DIRECTORY_SEPARATOR . '041.png',
            'dataVencimento'         => Carbon::create(2026, 4, 3),
            'valor'                  => 100.5,
            'multa'                  => false,
            'juros'                  => false,
            'numero'                 => 1,
            'diasBaixaAutomatica'    => 20,
            'numeroDocumento'        => 1,
            'pagador'                => $pagador,
            'beneficiario'           => $beneficiario,
            'carteira'               => 1,
            'agencia'                => 1111,
            'conta'                  => 22222,
            'descricaoDemonstrativo' => ['demonstrativo 1', 'demonstrativo 2', 'demonstrativo 3'],
            'instrucoes'             => ['instrucao 1', 'instrucao 2', 'instrucao 3'],
            'aceite'                 => 'S',
            'especieDoc'             => 'DM',
        ]);
    }
}
