<?php

namespace Kushki\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\Encryption\EncryptorInterface;

class CcConfigProvider implements ConfigProviderInterface
{
    /**
     * @var string[]
     */
    protected $methodCode = \Kushki\Payment\Model\KushkiPay::CODE;

    /**
     * @var Checkmo
     */
    protected $method;

    /**
     * @var Escaper
     */
    protected $escaper;

    /**
     * @var \Kushki\Payment\Helper\Data
     */
    protected $kushkiHelper;

    /**
     * @var  \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var  \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $coreUrl;

    /**
     * @param PaymentHelper                   $paymentHelper  
     * @param \Magento\Framework\UrlInterface $coreUrl        
     * @param \Kushki\Payment\Helper\Data     $kushkiHelper   
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Customer\Model\Session $customerSession
     * @param Escaper                         $escaper        
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        \Magento\Framework\UrlInterface $coreUrl,
        \Kushki\Payment\Helper\Data $kushkiHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey $formKey,
        Escaper $escaper,
        EncryptorInterface $enc
    ) {
        $this->escaper = $escaper;
        $this->coreUrl = $coreUrl;
        $this->kushkiHelper = $kushkiHelper;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->method = $paymentHelper->getMethodInstance($this->methodCode);
        $this->_enc = $enc;
         $this->formKey = $formKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig()
    {
        $config=[];
        if($this->method->isAvailable())
        {
            $config['payment']=[
                'kushki_pay' => [
                    'merchant_id'=>$this->_enc->decrypt($this->kushkiHelper->getConfig(\Kushki\Payment\Helper\Data::XML_PATH_KUSHKI_PUBLIC_MERCHANT_ID)),
                    'mode' => (boolean)$this->kushkiHelper->getConfig(\Kushki\Payment\Helper\Data::XML_PATH_KUSHKI_MODE),                    
                    'form_key'=>$this->formKey->getFormKey()
                ]
            ];        
            if($this->checkoutSession->getKushkiErrorMessage() && $this->checkoutSession->getKushkiErrorMessage() !='')
            {
                $config['payment']['kushki_pay']['kuski_error_message']=$this->checkoutSession->getKushkiErrorMessage();
                $this->checkoutSession->unsKushkiErrorMessage();
            }
        }
        return $config;
        
    }

    
}
