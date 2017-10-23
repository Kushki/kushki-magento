<?php
/**
 * Created by PhpStorm.
 * User: david
 * Date: 10/22/17
 * Time: 4:47 PM
 */

use PHPUnit_Framework_TestCase;

class PaymentControllerTest extends PHPUnit_Framework_TestCase {
    public function testGetTaxRules() {
        $expectedResult = array(
            [
                "tax_calculation_rule_id"=>"4",
                "code"=>"Retail Customer - Taxable Good - Rate 1",
                "priority"=>"1",
                "position"=>"0",
                "calculate_subtotal"=>"0"
            ]);

        $stub = $this->createMock(Kushki_KushkiPayment_PaymentController::class);
        $stub->method('getTaxRules')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getTaxRules());
    }
}