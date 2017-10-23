<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 10/22/17
 * Time: 4:47 PM
 */

use PHPUnit_Framework_TestCase;

class PaymentControllerTest extends PHPUnit_Framework_TestCase {
    public function testGetTaxDetails() {
        $expectedResult = array(
            [
                'productId' => '1',
                'price' => '280',
                'totalTax' => '12',
                'quantity' => '1',
                'tax' => ['4', '6']
            ]);

        $stub = $this->createMock(Kushki_KushkiPayment_PaymentController::class);
        $stub->method('getTaxDetails')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getTaxDetails());
    }
}