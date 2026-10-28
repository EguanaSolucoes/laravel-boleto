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
    "logo"            => realpath(__DIR__ . "/../logos/003.png") ?: null,
    "dataVencimento"  => new \Carbon\Carbon("2008-07-30"),
    "valor"           => 4539.00,
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

echo "Banco: " . $boleto->getCodigoBanco() . PHP_EOL;
echo "Nosso Número: " . $boleto->getNossoNumero() . PHP_EOL;
echo "Campo Livre: " . $boleto->getCampoLivre() . PHP_EOL;
echo "Código de Barras: " . $boleto->getCodigoBarras() . PHP_EOL;
echo "Linha Digitável: " . $boleto->getLinhaDigitavel() . PHP_EOL;

// Para renderizar no navegador:
// echo $boleto->renderHTML();
