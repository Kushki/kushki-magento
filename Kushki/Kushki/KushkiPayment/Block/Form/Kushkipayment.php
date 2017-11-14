<?php

class Kushki_KushkiPayment_Block_Form_KushkiPayment extends Mage_Payment_Block_Form
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('kushkipayment/form/kushkipayment.phtml');
  }
}