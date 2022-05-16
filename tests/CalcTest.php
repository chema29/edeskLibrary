<?php

namespace Tests;

require_once "./vendor/autoload.php";

use Edesk\Prueba\Calc;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * @test
     */
    public function test_sum()
    {
        $calc = new Calc();
        $result = $calc->suma(1,2);
        $this->assertEquals(3,$result);
    }
}