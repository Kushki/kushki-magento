<?php

class Kushki_kushkipayment_helper_data extends Mage_Core_Helper_Abstract
{
  function getPaymentGatewayUrl() 
  {
    return Mage::getUrl('kushkipayment/payment/gateway', array('_secure' => false));
  }
}