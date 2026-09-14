<?php
namespace Eduardokum\LaravelBoleto\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    public function assertInternalType($expected, $actual, $message = '')
    {
        if ($expected === 'array') {
            $this->assertIsArray($actual, $message);
        } elseif ($expected === 'string') {
            $this->assertIsString($actual, $message);
        } elseif ($expected === 'int' || $expected === 'integer') {
            $this->assertIsInt($actual, $message);
        } else {
            $this->assertTrue(gettype($actual) === $expected, $message);
        }
    }
}