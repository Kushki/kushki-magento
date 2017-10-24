<?php

use kushki\lib\ExtraTaxes;

class Kushki_KushkiPayment_PaymentController extends Mage_Core_Controller_Front_Action {
	public function gatewayAction() {
		$merchantId = Mage::helper( 'core' )->decrypt( Mage::getStoreConfig( 'payment/kushkipayment/commerceprivate' ) );
		$idioma     = kushki\lib\KushkiLanguage::ES;
		$moneda     = kushki\lib\KushkiCurrency::USD;
		$entorno    = kushki\lib\KushkiEnvironment::TESTING;
		if ( ! Mage::getStoreConfig( 'payment/kushkipayment/testing' ) ) {
			$entorno = kushki\lib\KushkiEnvironment::PRODUCTION;
		}

        $orderItems = $this->getProducts();
        $countryCode = Mage::getStoreConfig('general/country/default');
        $orderId = $this->getRequest()->get( "orderId" );
		$taxCalculation = $this->getTaxDetails();
		$taxes = $this->getTaxAmount($taxCalculation, $orderId);
		$kushki = new kushki\lib\Kushki( $merchantId, $idioma, $moneda, $entorno );

		$token        = $this->getRequest()->get( "kushkiToken" );
		$meses        = $this->getRequest()->get( "kushkiDeferred" );
//		$subtotalIva  = round( $subtotalIva, 2 );
//		$iva          = round( $iva, 2 );
//      $subtotalIva0 = round( $subtotalIva0, 2 );

        if($countryCode == 'CO'){
            $monto = new kushki\lib\Amount( $taxes['subtotalIva'], $taxes['iva'], $taxes['subtotalIva0'], $taxes['extraTaxes'] );
        }
        if($countryCode == 'US'){
            $monto = new kushki\lib\Amount( $taxes['subtotalIva'], $taxes['iva'], $taxes['subtotalIva0'], $taxes['ice'] );
        }

		if ( $meses > 0 ) {
			$transaccion = $kushki->deferredCharge( $token, $monto, $meses);
		} else {
			$transaccion = $kushki->charge( $token, $monto);
		}
		if ( $this->getRequest()->get( "orderId" ) && $transaccion->isSuccessful() ) {
			$arr_querystring = array(
				'flag'     => 1,
				'orderId'  => $this->getRequest()->get( "orderId" ),
				'ticketId' => $transaccion->getTicketNumber()
			);

			Mage_Core_Controller_Varien_Action::_redirect( 'kushkipayment/payment/response', array(
				'_secure' => false,
				'_query'  => $arr_querystring
			) );
		} else {
			Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/failure', array( '_secure' => false ) );
		}
	}

	public function redirectAction() {
		$this->loadLayout();
		$block = $this->getLayout()->createBlock( 'Mage_Core_Block_Template', 'kushkipayment', array( 'template' => 'kushkipayment/redirect.phtml' ) );
		$this->getLayout()->getBlock( 'content' )->append( $block );
		$this->renderLayout();
	}

	public function responseAction() {
		if ( $this->getRequest()->get( "flag" ) == "1" && $this->getRequest()->get( "orderId" ) ) {
			$orderId = $this->getRequest()->get( "orderId" );
			$ticket = $this->getRequest()->get( "ticketId" );
			$order   = Mage::getModel( 'sales/order' )->loadByIncrementId( $orderId );
			$order->setExtOrderId($ticket);
			$order->setState( Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, true, 'Payment Success.' );
			$order->save();

			Mage::getSingleton( 'checkout/session' )->unsQuoteId();
			Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/success', array( '_secure' => false ) );
		} else {
			Mage_Core_Controller_Varien_Action::_redirect( 'checkout/onepage/error', array( '_secure' => false ) );
		}
	}

    public function getProducts(){
        $orderId = $this->getRequest()->get( "orderId" );
        $order = Mage::getModel('sales/order')->load($orderId, 'increment_id');
        $orderItems = $order->getItemsCollection()
            ->addAttributeToSelect('*')
            ->load();
        return $orderItems->getData();
    }

    public function getTaxDetails(){
        $orderId = $this->getRequest()->get( "orderId" );
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $orderItems = $this->getProducts();
        $arrResp = array();
        $cont = 0;
        foreach ($orderItems as $item){
            $product = Mage::getModel('catalog/product')->load($item['product_id']);
            $productTaxClassId = $product->getTaxClassId();
            $customerGroupId = $order['customer_group_id'];
            $customerTaxClassId = Mage::getModel('customer/group')->load($customerGroupId)['tax_class_id'];
            $taxCalculation = Mage::getModel('tax/calculation')->getCollection()
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

    public function getTaxAmount($productTaxes, $orderId)
    {
        $tax_iva = Mage::getStoreConfig('payment/kushkipayment/taxiva');


        $tax_ice = Mage::getStoreConfig('payment/kushkipayment/taxice');
        $tax_propina = Mage::getStoreConfig('payment/kushkipayment/taxpropina');
        $tax_tasa_aeroportuaria = Mage::getStoreConfig('payment/kushkipayment/taxaeroportuaria');
        $tax_agencia_viaje = Mage::getStoreConfig('payment/kushkipayment/taxagenciadeviaje');
        $tax_iac = Mage::getStoreConfig('payment/kushkipayment/taxiac');

        $subtotalIva = 0;
        $subtotalIva0 = 0;
        $iva = 0;
        $ice = 0;
        $propina = null;
        $tasaAeroportuaria = null;
        $agenciaDeViaje = null;
        $iac = null;
        $i = 0;

        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
        $countryId = $order->getShippingAddress()->getData()['country_id'];
        foreach($productTaxes as $product){
            $totalAmount = $product['price'];
            $taxExcempt = true;
            foreach($product['tax'] as $tax){
                $taxCalculation = Mage::getModel('tax/calculation_rate')->getCollection()
                    ->addFieldToFilter('tax_country_id', $countryId)
                    ->addFieldToFilter('tax_calculation_rate_id', $tax[$i])->getData();
                if($taxCalculation != null && $taxCalculation[0]['code'] != null) {
                    $percentage = $taxCalculation[0]['rate'];
                    $taxName = $taxCalculation[0]['code'];
                    switch ($taxName) {
                        case $tax_iva:
                            $iva += (($totalAmount * $percentage) / 100) * $product['quantity'];
                            $subtotalIva += $totalAmount * $product['quantity'];
                            $taxExcempt = false;
                            break;
                        case $tax_ice:
                            $ice += $totalAmount * $percentage * $product->quantity;
                            break;
                        case $tax_propina:
                            $propina += $totalAmount * $percentage * $product->quantity;
                            break;
                        case $tax_tasa_aeroportuaria:
                            $tasaAeroportuaria += $totalAmount * $percentage * $product->quantity;
                            break;
                        case $tax_agencia_viaje:
                            $agenciaDeViaje += $totalAmount * $percentage * $product->quantity;
                            break;
                        case $tax_iac:
                            $iac += $totalAmount * $percentage * $product->quantity;
                            break;
                    }
                }
                $i ++;
            }
            if ($taxExcempt){
                $subtotalIva0 += $totalAmount * $product->quantity;
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
