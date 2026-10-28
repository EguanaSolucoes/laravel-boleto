<?php

namespace Eduardokum\LaravelBoleto\Tests\Retorno;

use Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Detalhe;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\DetalheSegmentoT;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\DetalheSegmentoU;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\DetalheSegmentoY;
use Eduardokum\LaravelBoleto\Tests\TestCase;
use Illuminate\Support\Collection;

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


    public function testRetornoBasaCnab240()
    {
        $retorno = \Eduardokum\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . '/files/cnab240/basa.ret');
        $retorno->processar();

        $this->assertNotNull($retorno->getHeader());
        $this->assertNotNull($retorno->getHeaderLote());
        $this->assertNotNull($retorno->getDetalhes());
        $this->assertNotNull($retorno->getTrailerLote());
        $this->assertNotNull($retorno->getTrailer());

        $this->assertEquals('BANCO DA AMAZONIA', $retorno->getBancoNome());
        $this->assertEquals('003', $retorno->getCodigoBanco());

        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $retorno->getDetalhes());
        $this->assertInstanceOf(\Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Detalhe::class, $retorno->getDetalhe(1));

        foreach ($retorno->getDetalhes() as $detalhe) {
            $this->assertInstanceOf(\Eduardokum\LaravelBoleto\Cnab\Retorno\Cnab240\Detalhe::class, $detalhe);
            $this->assertArrayHasKey('numeroDocumento', $detalhe->toArray());
            $this->assertEquals('06', $detalhe->getOcorrencia());
        }
    }
}
