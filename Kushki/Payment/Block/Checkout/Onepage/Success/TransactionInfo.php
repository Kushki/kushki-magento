<?php

namespace Kushki\Payment\Block\Checkout\Onepage\Success;

/**
 * Transaction information on Order success page
 *
 * @api
 * @since 100.0.2
 */
class TransactionInfo extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var bigint
     */
    protected $createAt;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
    * @return string
    */
    public function getTicketNumber()
    {
        $ticketNumber='';
        $this->createAt ='';
        $orderId = $this->_checkoutSession->getLastOrderId();
        if($orderId)
        {
            $order = $this->orderFactory->create()->load($orderId);
            $payment = $order->getPayment();
            if($payment->getMethod() == \Kushki\Payment\Model\KushkiPay::CODE)
            {
                $ticketNumber = $payment->getAdditionalInformation('capture_ticket_number');
                if (!$ticketNumber) {
                    $ticketNumber = $payment->getAdditionalInformation('preauth_ticket_number');
                }
                $this->createAt =  $payment->getAdditionalInformation('capture_at');
            }
        }
        return $ticketNumber;

    }

    public function getCashUrl(){
        $cashUrl="";
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);
        $payment = $order->getPayment();
        if($payment->getMethod() == \Kushki\Payment\Model\KushkiPay::CODE)
        {
            $cashUrl = $payment->getAdditionalInformation('cash_pdf');
        }
        return $cashUrl;
    }

    public function getTransactionReference(){
        $transactionReference="";
        $orderId = $this->_checkoutSession->getLastOrderId();
        $order = $this->orderFactory->create()->load($orderId);
        $payment = $order->getPayment();
        if($payment->getMethod() == \Kushki\Payment\Model\KushkiPay::CODE)
        {
            $transactionReference = $payment->getAdditionalInformation('transactionReference');
        }
        return $transactionReference;
    }

    /**
    * @return string
    */
    public function getTransactionDate()
    {
        if ($this->createAt == "") return "";
        $d = substr($this->createAt, 0, -3);
        return date("F d, Y h:i:s A",(int) $d);

    }

    public function clearCart() {
        return "
            <script>
                localStorage.removeItem('mage-cache-storage');
            </script>
            ";
    }
}
