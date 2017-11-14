<?php
/**
 * Created by PhpStorm.
 * User: patricio
 * Date: 10/23/17
 * Time: 3:06 PM
 */

<<<<<<< HEAD
class PaymentControllerKushki
=======
class Paymentcontrollerkushki
>>>>>>> [KV-0] New version
{
    public function __construct($orderId, $order, $tax_iva, $tax_ice, $tax_propina, $tax_tasa_aeroportuaria, $tax_agencia_viaje, $tax_iac)
    {
        $this->orderId = $orderId;
        $this->order = $order;
        $this->tax_iva = $tax_iva;
        $this->tax_ice = $tax_ice;
        $this->tax_propina = $tax_propina;
        $this->tax_tasa_aeroportuaria = $tax_tasa_aeroportuaria;
        $this->tax_agencia_viaje = $tax_agencia_viaje;
        $this->tax_iac = $tax_iac;
    }

    public function getProducts(){
        $orderItems = $this->order->getItemsCollection()
            ->addAttributeToSelect('*')
            ->load();
        return $orderItems->getData();
    }

    public function getTaxDetails(){
        $orderItems = $this->getProducts();
        $arrResp = array();
        $cont = 0;
        foreach ($orderItems as $item){
            $mage = new Mage();
            $product = $mage::getModel('catalog/product')->load($item['product_id']);
            $productTaxClassId = $product->getTaxClassId();
            $customerGroupId = $this->order['customer_group_id'];
            $customerTaxClassId = $mage::getModel('customer/group')->load($customerGroupId)['tax_class_id'];
            $taxCalculation = $mage::getModel('tax/calculation')->getCollection()
                ->addFieldToFilter('product_tax_class_id', $productTaxClassId)
                ->addFieldToFilter('customer_tax_class_id',$customerTaxClassId)->getData();
            $arrCal = array();
            $contCal = 0;
            foreach ($taxCalculation as $calculation){
                $arrCal[$contCal] = [
                    $calculation['tax_calculation_rate_id']
                ];
                $contCal++;
            }
            $arrResp[$cont] = [
                'productId' => $item['product_id'],
                'price' => $item['base_price'],
                'totalTax' => $item['tax_percent'],
                'quantity' => $item['qty_ordered'],
                'tax' => $arrCal
            ];
            $cont++;
        }
        return $arrResp;

    }

    public function getTaxAmount($productTaxes)
    {
        $subtotalIva = 0;
        $subtotalIva0 = 0;
        $iva = 0;
        $ice = 0;
        $propina = null;
        $tasaAeroportuaria = null;
        $agenciaDeViaje = null;
        $iac = null;
        $i = 0;

        $countryId = $this->order->getShippingAddress()->getData()['country_id'];
        foreach($productTaxes as $product){
            $totalAmount = $product['price'];
            $taxExcempt = true;
            foreach($product['tax'] as $tax){
                $mage = new Mage();
                $taxCalculation = $mage::getModel('tax/calculation_rate')->getCollection()
                    ->addFieldToFilter('tax_country_id', $countryId)
                    ->addFieldToFilter('tax_calculation_rate_id', $tax[$i])->getData();
                if($taxCalculation != null && $taxCalculation[0]['code'] != null) {
                    $percentage = $taxCalculation[0]['rate'];
                    $taxName = $taxCalculation[0]['code'];
                    switch ($taxName) {
                        case $this->tax_iva:
                            $iva += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            $subtotalIva += $totalAmount * $product['quantity'];
                            $taxExcempt = false;
                            break;
                        case $this->tax_ice:
                            $ice += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            break;
                        case $this->tax_propina:
                            $propina += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            break;
                        case $this->tax_tasa_aeroportuaria:
                            $tasaAeroportuaria += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            break;
                        case $this->tax_agencia_viaje:
                            $agenciaDeViaje += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            break;
                        case $this->tax_iac:
                            $iac += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            break;
                    }
                }
            }
            if ($taxExcempt){
                $subtotalIva0 += $totalAmount * $product['quantity'];
            }
        }
        $amount = [
            'subtotalIva' => $subtotalIva,
            'subtotalIva0' => $subtotalIva0,
            'iva' => $iva,
            'ice' => $ice,
            'extraTaxes' => [
                'propina' => $propina,
                'tasaAeroportuaria' => $tasaAeroportuaria,
                'agenciaDeViaje' => $agenciaDeViaje,
                'iac' => $iac
            ]
        ];
        return $amount;
    }
}
