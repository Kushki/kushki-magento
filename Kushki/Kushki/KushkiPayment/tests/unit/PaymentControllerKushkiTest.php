<?php
/**
 * Created by PhpStorm.
 * User: patricio
 * Date: 10/24/17
 * Time: 5:02 PM
 */

namespace kushki\Kushk\Kushki\KushkiPayment\tests\lib\unit;

use kushki\Kushk\Kushki\KushkiPayment\tests\lib\Utils;

use Paymentcontrollerkushki;
use PHPUnit_Framework_TestCase;

require_once __DIR__ . '/../lib/Utils.php';

class PaymentControllerKushkiTest extends PHPUnit_Framework_TestCase
{
    private $taxDetails;

    public function __construct()
    {
        $this->taxDetails = array(
            [
                'productId' => Utils::randomNumberString(1, 5),
                'price' => Utils::randomNumberString(1, 5),
                'totalTax' => Utils::randomNumberString(1, 2),
                'quantity' => '1',
                'tax' => [Utils::randomNumberString(1, 2), Utils::randomNumberString(1, 2)]
            ]);
    }

    public function testGetTaxDetails()
    {
        $expectedResult = $this->taxDetails;
        $stub = $this->createMock(Paymentcontrollerkushki::class);
        $stub->method('getTaxDetails')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getTaxDetails(), 'Tax details not equal');
    }

    public function testGetProducts()
    {
        $expectedResult = array(['producto1'], ['producto2'], ['producto3']);
        $stub = $this->createMock(Paymentcontrollerkushki::class);
        $stub->method('getProducts')
            ->willReturn($expectedResult);
        $this->assertEquals($expectedResult, $stub->getProducts(), 'Products not equal');
    }

    public function testGetTaxAmount()
    {
        $taxDetails = $this->taxDetails;
        $amount = [
            'subtotalIva' => Utils::getRandomDouble(0, 2),
            'subtotalIva0' => Utils::getRandomDouble(0, 2),
            'iva' => Utils::getRandomDouble(0, 2),
            'ice' => Utils::getRandomDouble(0, 2),
            'extraTaxes' => [
                'propina' => Utils::getRandomDouble(0, 2),
                'tasaAeroportuaria' => Utils::getRandomDouble(0, 2),
                'agenciaDeViaje' => Utils::getRandomDouble(0, 2),
                'iac' => Utils::getRandomDouble(0, 2)
            ]
        ];
        $stub = $this->createMock(Paymentcontrollerkushki::class);
        $stub->method('getTaxAmount')
            ->willReturn($amount);
        $this->assertEquals($amount, $stub->getTaxAmount($taxDetails), 'Amount not equal');
    }
}
