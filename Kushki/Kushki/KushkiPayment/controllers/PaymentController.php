<?php

class Kushki_KushkiPayment_PaymentController extends Mage_Core_Controller_Front_Action {
	public function gatewayAction() {
		$merchantId = Mage::helper( 'core' )->decrypt( Mage::getStoreConfig( 'payment/kushkipayment/commerceprivate' ) );
		$idioma     = kushki\lib\KushkiLanguage::ES;
		$moneda     = Mage::app()->getStore()->getCurrentCurrencyCode() == 'USD'? kushki\lib\KushkiCurrency::USD : kushki\lib\KushkiCurrency::COP;
//		$moneda     = kushki\lib\KushkiCurrency::USD;
		$entorno    = kushki\lib\KushkiEnvironment::TESTING;
		if ( ! Mage::getStoreConfig( 'payment/kushkipayment/testing' ) ) {
			$entorno = kushki\lib\KushkiEnvironment::PRODUCTION;
		}


		$kushki = new kushki\lib\Kushki( $merchantId, $idioma, $moneda, $entorno );

		$token        = $this->getRequest()->get( "kushkiToken" );
		$meses        = $this->getRequest()->get( "kushkiDeferred" );
		$total        = doubleval( $this->getRequest()->get( "grandTotal" ) );
		$subtotalIva  = round( $total / 1.12, 2 );
		$iva          = round( $total - $subtotalIva, 2 );
		$subtotalIva0 = 0.0;
		$ice          = 0.0;
		$monto        = new kushki\lib\Amount( $subtotalIva, $iva, $subtotalIva0, $ice );

		if ( $meses > 0 ) {
			$transaccion = $kushki->deferredCharge( $token, $monto, $meses );
		} else {
			$transaccion = $kushki->charge( $token, $monto );
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
}