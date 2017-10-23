<?php

class Kushki_KushkiPayment_PaymentController extends Mage_Core_Controller_Front_Action {
	public function gatewayAction() {
		$merchantId = Mage::helper( 'core' )->decrypt( Mage::getStoreConfig( 'payment/kushkipayment/commerceprivate' ) );
		$idioma     = kushki\lib\KushkiLanguage::ES;
		$moneda     = kushki\lib\KushkiCurrency::USD;
		$entorno    = kushki\lib\KushkiEnvironment::TESTING;
		if ( ! Mage::getStoreConfig( 'payment/kushkipayment/testing' ) ) {
			$entorno = kushki\lib\KushkiEnvironment::PRODUCTION;
		}

        $orderId = $this->getRequest()->get( "orderId" );
        $order = Mage::getModel('sales/order')->loadByIncrementId($orderId)->getData();
        $orderItems = $this->getProducts();
        $iva = 0.0;
        $subtotalIva0 = 0.0;
        $subtotalIva = 0.0;
        foreach ($orderItems as $item){
            if($item['tax_amount'] > 0){
                $subtotalIva += $item['price'];
                $iva += $item['tax_amount'];
            }else {
                $subtotalIva0 += $item['price'];
            }
        }

        $collection = $this->getTaxRules();
		$kushki = new kushki\lib\Kushki( $merchantId, $idioma, $moneda, $entorno );

		$token        = $this->getRequest()->get( "kushkiToken" );
		$meses        = $this->getRequest()->get( "kushkiDeferred" );
		$subtotalIva  = round( $subtotalIva, 2 );
		$iva          = round( $iva, 2 );
        $subtotalIva0 = round( $subtotalIva0, 2 );
		$ice          = 0.0;
		$monto        = new kushki\lib\Amount( $subtotalIva, $iva, $subtotalIva0, $ice );

		if ( $meses > 0 ) {
			$transaccion = $kushki->deferredCharge( $token, $monto, $meses, $order);
		} else {
			$transaccion = $kushki->charge( $token, $monto, $order);
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

	public function getTaxRules(){
        //     collection is an array that has all the tax rules Todo erase comment in the KV-2059
//     each element in the array has tax_calculation_rule_id, code, priority, position and calculate_subtotal
        $collection = Mage::getModel('tax/calculation_rule')->getCollection()->getData();
        $collection = array_filter($collection);

        if (!empty($collection)) {
            foreach($collection as $rule) {
                echo('');
            }
            return $collection;
        }
        return false;
    }

    public function getProducts(){
        $orderId = $this->getRequest()->get( "orderId" );
        $order = Mage::getModel('sales/order')->load($orderId, 'increment_id');
        $orderItems = $order->getItemsCollection()
            ->addAttributeToSelect('*')
            ->load();
        return $orderItems->getData();
    }
}