<?php
/**
 * Created by PhpStorm.
 * User: patricio
 * Date: 10/23/17
 * Time: 3:06 PM
 */

class PaymentControllerKushki
{
    public function getTaxAmount($productTaxes, $orderId)
    {
        $tax_iva = Mage::getStoreConfig('payment/kushkipayment/taxiva');
        $tax_ice = Mage::getStoreConfig('payment/kushkipayment/taxice');
        $tax_propina = Mage::getStoreConfig('payment/kushkipayment/taxpropina');
        $tax_tasa_aeroportuaria = Mage::getStoreConfig('payment/kushkipayment/taxaeroportuaria');
        $tax_agencia_viaje = Mage::getStoreConfig('payment/kushkipayment/taxagenciadeviaje');
        $tax_iac = Mage::getStoreConfig('payment/kushkipayment/taxiac');

        $iva = 0;
        $ice = 0;
        $propina = null;
        $tasaAeroportuaria = null;
        $agenciaDeViaje = null;
        $iac = null;

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);

        foreach($productTaxes as $product){
            foreach($product->tax as $tax){
                $countryId = $order->getShippingAddress()->getData()['country_id'];
                $totalAmount = $product->price;
                $taxCalculation = Mage::getModel('tax/calculation')->getCollection()
                    ->addFieldToFilter('tax_country_id', $countryId)
                    ->addFieldToFilter('tax_calculation_rate_id',$tax->id)->getData();
                $percentage = $taxCalculation->rate;
                $taxName = $taxCalculation->code;
                switch ($taxName) {
                    case $tax_iva:
                        $iva += ($totalAmount / $percentage) * $product->quantity;
                        break;
                    case $tax_ice:
                        $ice += ($totalAmount / $percentage) * $product->quantity;
                        break;
                    case $tax_propina:
                        $propina += ($totalAmount / $percentage) * $product->quantity;
                        break;
                    case $tax_tasa_aeroportuaria:
                        $tasaAeroportuaria += ($totalAmount / $percentage) * $product->quantity;
                        break;
                    case $tax_agencia_viaje:
                        $agenciaDeViaje += ($totalAmount / $percentage) * $product->quantity;
                        break;
                    case $tax_iac:
                        $iac += ($totalAmount / $percentage) * $product->quantity;
                        break;
                }
            }
        }
        return[$iva, $ice, $propina, $tasaAeroportuaria, $agenciaDeViaje, $iac];
    }
}
