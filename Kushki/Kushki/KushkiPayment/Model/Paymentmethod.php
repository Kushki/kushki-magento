<?php
class Kushki_kushkipayment_model_paymentmethod extends Mage_Payment_Model_Method_Abstract {
  protected $_code  = 'kushkipayment';
  protected $_formBlockType = 'kushkipayment/form_kushkipayment';
  protected $_infoBlockType = 'kushkipayment/info_kushkipayment';
 
  public function assignData($data)
  {
    $info = $this->getInfoInstance();
 
    return $this;
  }
 
  public function validate()
  {
    parent::validate();
    $info = $this->getInfoInstance();
 
    if ($errorMsg) 
    {
      Mage::throwException($errorMsg);
    }
 
    return $this;
  }
 
  public function getOrderPlaceRedirectUrl()
  {
    return Mage::getUrl('kushkipayment/payment/redirect', array('_secure' => false));
  }
}