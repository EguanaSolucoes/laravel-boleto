<?php
require __DIR__ . "/../vendor/autoload.php";

$retorno = \Eduardokum\LaravelBoleto\Cnab\Retorno\Factory::make(__DIR__ . "/../tests/Retorno/files/cnab240/basa.ret");

$retorno->processar();

echo "Banco: " . $retorno->getBancoNome() . " (" . $retorno->getCodigoBanco() . ")" . PHP_EOL;
echo "Total de Títulos Processados: " . $retorno->getDetalhes()->count() . PHP_EOL;

foreach ($retorno->getDetalhes() as $detalhe) {
    echo "--------------------------------------------------" . PHP_EOL;
    echo "Nosso Número : " . $detalhe->getNossoNumero() . PHP_EOL;
    echo "Documento    : " . $detalhe->getNumeroDocumento() . PHP_EOL;
    echo "Valor Título : R$ " . $detalhe->getValor() . PHP_EOL;
    echo "Valor Pago   : R$ " . $detalhe->getValorRecebido() . PHP_EOL;
    echo "Ocorrência   : " . $detalhe->getOcorrencia() . " - " . $detalhe->getOcorrenciaDescricao() . PHP_EOL;
    echo "Data Crédito : " . $detalhe->getDataCredito() . PHP_EOL;
}
