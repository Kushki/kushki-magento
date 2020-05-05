<?php


namespace Kushki\Payment\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use kushki\lib\Kushki;

class Confirm extends \Magento\Framework\App\Action\Action
{
	/**
     * @var Validator
     */
    protected $formKeyValidator;

     /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @param QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

     /**
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface
     */
    protected $paymentInformationManagement;

     /**
     * @param \Magento\Checkout\Api\GuestPaymentInformationManagementInterface
     */
    protected $guestPaymentInformationManagement;

	/**
     * Constructor
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement
     * @param \Magento\Checkout\Api\GuestPaymentInformationManagementInterface $paymentInformationManagement
     */
    public function __construct(
        Context $context,
        Validator $formKeyValidator,
        CheckoutSession $checkoutSession,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement,
        \Magento\Checkout\Api\GuestPaymentInformationManagementInterface $guestPaymentInformationManagement,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $address
    ) {
    	$this->formKeyValidator = $formKeyValidator;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->guestPaymentInformationManagement = $guestPaymentInformationManagement;
        $this->paymentMethod = $paymentMethod;
        $this->address = $address;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        parent::__construct($context);

    }

    /**
     * Adding new item
     *
     * @return \Magento\Framework\Controller\Result\Json
     * @throws NotFoundException
     */
    public function execute()
    {
    	/** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        if (!$this->formKeyValidator->validate($this->getRequest())) {
        	$this->messageManager->addWarningMessage(__('Invalid method call'));
            return $resultRedirect->setPath('checkout');
        }

    	$requestParams = $this->getRequest()->getParams();

        if (!$requestParams || $this->getRequest()->getMethod() !== 'POST') {
            $this->messageManager->addWarningMessage(__('Invalid method call'));
            return $resultRedirect->setPath('checkout');
        }

        try{
            if ($this->checkoutSession->getQuote()->getId()) {
                $quote = $this->quoteRepository->get($this->checkoutSession->getQuote()->getId());
                $this->paymentMethod->setMethod($this->getRequest()->getParam('payment_method'));
                $additionalData=[];
                $additionalData['kushki_token']=$this->getRequest()->getParam('kushkiToken');
                if($this->getRequest()->getParam('kushkiDeferredType'))
                {
                    $additionalData['kushki_deffered_type']=$this->getRequest()->getParam('kushkiDeferredType');
                }
                if($this->getRequest()->getParam('kushkiDeferred'))
                {
                    $additionalData['kushki_deffered']=$this->getRequest()->getParam('kushkiDeferred');
                }
                if($this->getRequest()->getParam('kushkiMonthsOfGrace'))
                {
                    $additionalData['kushki_months_of_grace']=$this->getRequest()->getParam('kushkiMonthsOfGrace');
                }
                $this->paymentMethod->setAdditionalData($additionalData);

                $this->address->setData(json_decode($this->getRequest()->getParam('billing_address'),true));

                if (!$quote->getCustomer()->getId()) {
                    /** @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask */
                    $quoteIdMask = $this->quoteIdMaskFactory->create();
                    $quoteId = $quoteIdMask->load(
                        $this->checkoutSession->getQuote()->getId(),
                        'quote_id'
                    )->getMaskedId();
                    $orderId = $this->guestPaymentInformationManagement->savePaymentInformationAndPlaceOrder(
                        $quoteId,
                        $this->getRequest()->getParam('guest_email'),
                        $this->paymentMethod,
                        $this->address
                    );
                }
                else{
                    $orderId = $this->paymentInformationManagement->savePaymentInformationAndPlaceOrder(
                        $this->checkoutSession->getQuote()->getId(),
                        $this->paymentMethod,
                        $this->address
                    );
                }
                if($orderId)
                {
                    return $resultRedirect->setPath('checkout/onepage/success/');
                }

            }
        }
        catch(\Exception $e)
        {
            $this->checkoutSession->setKushkiErrorMessage($e->getMessage());
            return $resultRedirect->setPath('checkout');
        }
        $this->messageManager->addWarningMessage(__('Something went wrong while creating order'));
        return $resultRedirect->setPath('checkout');

    }
}
