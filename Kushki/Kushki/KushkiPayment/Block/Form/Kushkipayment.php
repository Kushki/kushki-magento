<?php

class Kushki_kushkipayment_block_form_kushkipayment extends Mage_Payment_Block_Form
{
  protected function _construct()
  {
    parent::_construct();
    $this->setTemplate('kushkipayment/form/kushkipayment.phtml');
  }
}