<?php

namespace Retorno;

use Illuminate\Support\Collection;
use Eduardokum\LaravelBoleto\Tests\TestCase;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Detalhe;
use Eduardokum\LaravelBoleto\Exception\ValidationException;

class RetornoCnab240Test extends TestCase
{
    public function testRetornoSantanderCnab240()
    {
        $retorno = \Eduardokum\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab240/santander.ret');
        $retorno->processar();

        $this->assertNotNull($retorno->getHeader());
        $this->assertNotNull($retorno->getHeaderLote());
        $this->assertNotNull($retorno->getDetalhes());
        $this->assertNotNull($retorno->getTrailerLote());
        $this->assertNotNull($retorno->getTrailer());

        $this->assertEquals('Banco Santander (Brasil) S.A.', $retorno->getBancoNome());
        $this->assertEquals('033', $retorno->getCodigoBanco());

        $this->assertInstanceOf(Collection::class, $retorno->getDetalhes());

        $this->assertInstanceOf(Detalhe::class, $retorno->getDetalhe(1));

        foreach ($retorno->getDetalhes() as $detalhe) {
            $this->assertInstanceOf(Detalhe::class, $detalhe);
            $this->assertArrayHasKey('numeroDocumento', $detalhe->toArray());
        }
    }

    public function testRetornoSemDetalheCnab240()
    {
        $this->expectException(ValidationException::class);
        $retorno = \Eduardokum\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab240/retorno_sem_detalhe.ret');
        $retorno->processar();
    }

    public function testRetornoBasaCnab240()
    {
        $retorno = \Eduardokum\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab240/basa.ret');
        $retorno->processar();

        $this->assertNotNull($retorno->getHeader());
        $this->assertNotNull($retorno->getHeaderLote());
        $this->assertNotNull($retorno->getDetalhes());
        $this->assertNotNull($retorno->getTrailerLote());
        $this->assertNotNull($retorno->getTrailer());

        $this->assertEquals('Banco da Amazônia S.A.', $retorno->getBancoNome());
        $this->assertEquals('003', $retorno->getCodigoBanco());

        $this->assertInstanceOf(Collection::class, $retorno->getDetalhes());
        $this->assertInstanceOf(Detalhe::class, $retorno->getDetalhe(1));

        foreach ($retorno->getDetalhes() as $detalhe) {
            $this->assertInstanceOf(Detalhe::class, $detalhe);
            $this->assertArrayHasKey('numeroDocumento', $detalhe->toArray());
            $this->assertEquals('06', $detalhe->getOcorrencia());
        }
    }

    public function testRetornoCitibankCnab240()
    {
        $retorno = \Eduardokum\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab240/citibank.ret');
        $retorno->processar();

        $this->assertNotNull($retorno->getHeader());
        $this->assertNotNull($retorno->getHeaderLote());
        $this->assertNotNull($retorno->getTrailerLote());
        $this->assertNotNull($retorno->getTrailer());

        $this->assertEquals('745', $retorno->getCodigoBanco());
        $this->assertCount(10, $retorno->getDetalhes());

        $primeiro = $retorno->getDetalhe(1);
        $this->assertEquals('000000110418', $primeiro->getNossoNumero());
        $this->assertEquals('06', $primeiro->getOcorrencia());
        $this->assertTrue($primeiro->hasOcorrencia('06'));
        $this->assertEquals(35.22, $primeiro->getValorRecebido());
        $this->assertEquals('18/11/2026', $primeiro->getDataOcorrencia());

        foreach ($retorno->getDetalhes() as $detalhe) {
            $this->assertInstanceOf(\Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Detalhe::class, $detalhe);
            $this->assertStringStartsWith('Liquidação', $detalhe->getOcorrenciaDescricao());
        }
    }
}
