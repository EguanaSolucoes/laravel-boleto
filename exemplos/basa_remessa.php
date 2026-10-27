<?php
require __DIR__ . "/../vendor/autoload.php";

$pagador = new \Eduardokum\LaravelBoleto\Pessoa([
    "nome"      => "Cliente Teste",
    "endereco"  => "Rua Teste, 123",
    "bairro"    => "Bairro Teste",
    "cep"       => "66000-000",
    "uf"        => "PA",
    "cidade"    => "Belém",
    "documento" => "123.456.789-01",
]);

$beneficiario = new \Eduardokum\LaravelBoleto\Pessoa([
    "nome"      => "Empresa Beneficiária",
    "endereco"  => "Av. Principal, 500",
    "bairro"    => "Centro",
    "cep"       => "66000-100",
    "uf"        => "PA",
    "cidade"    => "Belém",
    "documento" => "12.345.678/0001-90",
]);

$boleto = new \Eduardokum\LaravelBoleto\Boleto\Banco\Basa([
    "logo"            => realpath(__DIR__ . "/../logos/003.png"),
    "dataVencimento"  => new \Carbon\Carbon("2026-12-31"),
    "valor"           => 100.50,
    "numero"          => 9604832,
    "numeroDocumento" => 12345,
    "pagador"         => $pagador,
    "beneficiario"    => $beneficiario,
    "carteira"        => "1",
    "agencia"         => "017",
    "agenciaDv"       => "5",
    "conta"           => "737320",
    "contaDv"         => "0",
    "convenio"        => "12345",
    "aceite"          => "N",
    "especieDoc"      => "DM",
]);

$remessa = new \Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco\Basa([
    "agencia"      => "017",
    "agenciaDv"    => "5",
    "carteira"     => "1",
    "conta"        => "737320",
    "contaDv"      => "0",
    "convenio"     => "12345",
    "idremessa"    => 1,
    "beneficiario" => $beneficiario,
]);

$remessa->addBoleto($boleto);

// Gera o arquivo de texto formatado (240 bytes por linha)
echo $remessa->gerar();
