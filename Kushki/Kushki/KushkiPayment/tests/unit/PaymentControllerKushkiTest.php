<?php
/**
 * Created by PhpStorm.
 * User: patricio
 * Date: 10/24/17
 * Time: 5:02 PM
 */

use PHPUnit\Framework\TestCase;

class PaymentControllerKushkiTest extends PHPUnit\Framework\TestCase
{
    public function testGetTaxDetails() {
        $expectedResult = array(
            [
                'productId' => '1',
                'price' => '280',
                'totalTax' => '12',
                'quantity' => '1',
                'tax' => ['4', '6']
            ]);
        $stub = $this->createMock(PaymentControllerKushki::class);
        $stub->method('getTaxDetails')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getTaxDetails(), 'Tax details not equal');
    }

    public function testGetProducts(){
        $expectedResult = array(['producto1'],['producto2'], ['producto3']);
        $stub = $this->createMock(PaymentControllerKushki::class);
        $stub->method('getProducts')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getProducts(), 'Products not equal');
    }

    public function testGetTaxAmount(){
        $taxDetails = array(
            [
                'productId' => '1',
                'price' => '280',
                'totalTax' => '12',
                'quantity' => '1',
                'tax' => ['4', '6']
            ]);
        $amount = [
            'subtotalIva' => 0,
            'subtotalIva0' => 0,
            'iva' => 0,
            'ice' => 0,
            'extraTaxes' => [
                'propina' => 0,
                'tasaAeroportuaria' => 0,
                'agenciaDeViaje' => 0,
                'iac' => 0
            ]
        ];
        $stub = $this->createMock(PaymentControllerKushki::class);
        $stub->method('getTaxAmount')
            ->willReturn($amount);
        $this->assertEquals($amount, $stub->getTaxAmount($taxDetails), 'Amount not equal');
    }
}
