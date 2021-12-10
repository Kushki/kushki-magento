<?php
namespace Kushki\Payment\Controller\Payment;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Controller\ResultFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Model\QuoteIdMaskFactory;
use kushki\lib\Kushki;
class Async extends \Magento\Framework\App\Action\Action
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
        \Magento\Sales\Model\OrderFactory $orderFactory
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
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('checkout/onepage/success/');
    }
}