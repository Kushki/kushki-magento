<?php


namespace Kushki\Payment\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use kushki\lib\Kushki;
use Magento\Sales\Api\OrderRepositoryInterface;

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
        \Magento\Quote\Api\Data\AddressInterface $address,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Framework\DB\TransactionFactory $transactionFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender
    ) {
    	$this->formKeyValidator = $formKeyValidator;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->paymentInformationManagement = $paymentInformationManagement;
        $this->guestPaymentInformationManagement = $guestPaymentInformationManagement;
        $this->paymentMethod = $paymentMethod;
        $this->address = $address;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderFactory = $orderFactory;
        $this->_orderRepository = $orderRepository;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->messageManager = $messageManager;
        $this->invoiceSender = $invoiceSender;
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

                if($this->getRequest()->getParam('kushkiPaymentMethod'))
                {
                    $additionalData['kushki_payment_method'] = $this->getRequest()->getParam('kushkiPaymentMethod');
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
                    if ($this->getRequest()->getParam('kushkiPaymentMethod') == "card") {
                        $this->generateCaptureInvoice($orderId);
                    } else {
                        $state = \Magento\Sales\Model\Order::STATE_NEW;
                        $this->_setOrderStatus($orderId, "pending", $state);
                    }
                    if( $this->getRequest()->getParam('kushkiPaymentMethod') == "transfer" || $this->getRequest()->getParam('kushkiPaymentMethod') == "card_async" ){
                        $order = $this->orderFactory->create()->load($orderId);
                        $payment = $order->getPayment();
                        return $resultRedirect->setUrl($payment->getAdditionalInformation('redirectUrl'));
                    }
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

    private function _setOrderStatus($orderId, $status, $state): bool
    {
        try{
            $order = $this->orderFactory->create()->load($orderId);
            $order
                ->setState($state, true)
                ->setStatus($status, true)
                ->addStatusToHistory($status, "Kushki payment is " . $status);
            $this->_orderRepository->save($order);
            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    public function generateCaptureInvoice($orderId){
        try {
            $order = $this->_orderRepository->get($orderId);

            if (!$order->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The order no longer exists.'));
            }
            if(!$order->canInvoice()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The order does not allow an invoice to be created.')
                );
            }

            $invoice = $this->invoiceService->prepareInvoice($order);
            if (!$invoice) {
                throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save the invoice right now.'));
            }
            if (!$invoice->getTotalQty()) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('You can\'t create an invoice without products.')
                );
            }
            $payment = $order->getPayment();
            $paymentId = $payment->getTransactionId();
            $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
            $invoice->setTransactionId($paymentId);
            $invoice->register();
            $invoice->getOrder()->setCustomerNoteNotify(false);
            $invoice->getOrder()->setIsInProcess(true);
            $order->addStatusHistoryComment('Kushki payment completed and automatically invoiced.', false);
            $transactionSave = $this->transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
            $transactionSave->save();
            $payment->capture($invoice);
            $this->_orderRepository->save($order);

            // send invoice emails, If you want to stop mail comment below try/catch code
            try {
                $this->invoiceSender->send($invoice);
            } catch (\Exception $e) {
                $this->messageManager->addError(__('We can\'t send the invoice email right now.'));
            }
        } catch (\Exception $e) {

            $this->messageManager->addError($e->getMessage());
        }
    }
}
