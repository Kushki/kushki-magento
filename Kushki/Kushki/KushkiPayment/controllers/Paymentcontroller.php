<?php

include_once 'Paymentcontrollerkushki.php';
use kushki\lib\ExtraTaxes;

class Kushki_kushkipayment_paymentcontroller extends Mage_Core_Controller_Front_Action {
	public function gatewayAction() {
		$merchantId = Mage::helper( 'core' )->decrypt( Mage::getStoreConfig( 'payment/kushkipayment/commerceprivate' ) );
		$idioma     = kushki\lib\KushkiLanguage::ES;
        $moneda     = Mage::app()->getStore()->getCurrentCurrencyCode() == 'USD'? kushki\lib\KushkiCurrency::USD : kushki\lib\KushkiCurrency::COP;
//		$moneda     = kushki\lib\KushkiCurrency::USD;
		$entorno    = kushki\lib\KushkiEnvironment::TESTING;
		if ( ! Mage::getStoreConfig( 'payment/kushkipayment/testing' ) ) {
			$entorno = kushki\lib\KushkiEnvironment::PRODUCTION;
		}
        $tax_iva = Mage::getStoreConfig('payment/kushkipayment/taxiva');
        $tax_ice = Mage::getStoreConfig('payment/kushkipayment/taxice');
        $tax_propina = Mage::getStoreConfig('payment/kushkipayment/taxpropina');
        $tax_tasa_aeroportuaria = Mage::getStoreConfig('payment/kushkipayment/taxaeroportuaria');
        $tax_agencia_viaje = Mage::getStoreConfig('payment/kushkipayment/taxagenciadeviaje');
        $tax_iac = Mage::getStoreConfig('payment/kushkipayment/taxiac');

        $countryCode = Mage::getStoreConfig('general/country/default');
        $orderId = $this->getRequest()->get( "orderId" );
        $order = Mage::getModel('sales/order')->load($orderId, 'increment_id');
        $kushkiPaymentController = new Paymentcontrollerkushki($orderId, $order, $tax_iva, $tax_ice, $tax_propina, $tax_tasa_aeroportuaria, $tax_agencia_viaje, $tax_iac);
        $taxCalculation = $kushkiPaymentController->getTaxDetails();
        //$taxCalculation = $this->getTaxDetails();
        $taxes = $kushkiPaymentController->getTaxAmount($taxCalculation, $orderId);
		//$taxes = $this->getTaxAmount($taxCalculation, $orderId);
		$kushki = new kushki\lib\Kushki( $merchantId, $idioma, $moneda, $entorno );

		if ( (float)$order['base_shipping_tax_amount'] > 0 ){
            $taxes['subtotalIva'] += (float)$order['base_shipping_amount'];
            $taxes['iva'] += (float)$order['base_shipping_tax_amount'];
        }
        else{
		    $taxes['subtotalIva0'] += (float)$order['base_shipping_amount'];
        }
		$token        = $this->getRequest()->get( "kushkiToken" );
		$meses        = $this->getRequest()->get( "kushkiDeferred" );
//		$subtotalIva  = round( $subtotalIva, 2 );
//		$iva          = round( $iva, 2 );
//      $subtotalIva0 = round( $subtotalIva0, 2 );
        if($countryCode == 'CO'){
            $extraTaxes =
                new kushki\lib\ExtraTaxes($taxes['extraTaxes']['propina'], $taxes['extraTaxes']['tasaAeroportuaria'],
                    $taxes['extraTaxes']['agenciaDeViaje'], $taxes['extraTaxes']['iac']);
            $monto = new kushki\lib\Amount( $taxes['subtotalIva'], $taxes['iva'], $taxes['subtotalIva0'], $extraTaxes );
        }
        else {
            $monto = new kushki\lib\Amount( $taxes['subtotalIva'], $taxes['iva'], $taxes['subtotalIva0'], $taxes['ice'] );
        }


		if ( $meses > 0 ) {
			$transaccion = $kushki->deferredCharge( $token, $monto, $meses);
		} else {
			$transaccion = $kushki->charge( $token, $monto);
            //TODO[@pmoreanoj] uncomment when metadata is getting decrypted in Aurus
            //$transaccion = $kushki->charge( $token, $monto, $order->getData());
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
