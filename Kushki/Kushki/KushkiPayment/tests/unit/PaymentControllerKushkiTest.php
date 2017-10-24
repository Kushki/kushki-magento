<?php
/**
 * Created by PhpStorm.
 * User: patricio
 * Date: 10/24/17
 * Time: 5:02 PM
 */

use PHPUnit_Framework_TestCase;

class PaymentControllerKushkiTest extends PHPUnit_Framework_TestCase
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

        $stub = $this->createMock(Kushki_KushkiPayment_PaymentControllerKushki::class);
        $stub->method('getTaxDetails')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getTaxDetails());
    }

}
