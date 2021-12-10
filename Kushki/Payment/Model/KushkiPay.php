<?php

namespace Kushki\Payment\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Paypal\Model\Payflow\Service\Response\Handler\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Status\HistoryFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use HttpException;
use HttpRequest;

class KushkiPay extends  \Magento\Payment\Model\Method\AbstractMethod {

	const CODE = 'kushki_pay';

	/**
	 * Payment method code
	 *
	 * @var string
	 */
	protected $_code = self::CODE;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canAuthorize = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canCapture = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canRefund = true;

	/**
	 * Availability option
	 * Ivan
	 * @var bool
	 */
	protected $_canRefundInvoicePartial = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canVoid = true;

	/**
	 * Availability option
	 *
	 * @var bool
	 */
	protected $_canUseCheckout = true;

	/**
	 * Payment Method feature
	 *
	 * @var bool
	 */
	protected $_canReviewPayment = true;

	/**
	 * @var \Kushki\Payment\Helper\Data
	 */
	protected $kushkiHelper;

	/**
	 * @var HandlerInterface
	 */
	private $errorHandler;

	/**
	 * @var \Kushki\Payment\Model\Api\HttpTextFactory
	 */
	private $httpTextFactory;

	/**
	 * @var EncryptorInterface
	 */
	protected $_enc;

    /**
     * @var HistoryFactory
     */
    protected $orderHistoryFactory;

    protected $_orderRepository;

    public $_storeManager;

    protected $_session;

	/**
	 * @param \Magento\Framework\Model\Context                             $context
	 * @param \Magento\Framework\Registry                                  $registry
	 * @param \Magento\Framework\Api\ExtensionAttributesFactory            $extensionFactory
	 * @param \Magento\Framework\Api\AttributeValueFactory                 $customAttributeFactory
	 * @param \Magento\Payment\Helper\Data                                 $paymentData
	 * @param \Magento\Framework\App\Config\ScopeConfigInterface           $scopeConfig
	 * @param \Magento\Payment\Model\Method\Logger                         $logger
	 * @param \Magento\Framework\Module\ModuleListInterface                $moduleList
	 * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface         $localeDate
	 * @param \Kushki\Payment\Helper\Data                                  $kushkiHelper
	 * @param \Kushki\Payment\Model\Api\HttpTextFactory                    $httpTextFactory
	 * @param EncryptorInterface                                           $enc
	 * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
	 * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
	 * @param array                                                        $data
	 */
	function __construct(
		\Magento\Framework\Model\Context $context,
		\Magento\Framework\Registry $registry,
		\Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
		\Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
		\Magento\Payment\Helper\Data $paymentData,
		\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
		\Magento\Payment\Model\Method\Logger $logger,
		\Magento\Framework\Module\ModuleListInterface $moduleList,
		\Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
		\Kushki\Payment\Helper\Data $kushkiHelper,
		\Kushki\Payment\Model\Api\HttpTextFactory $httpTextFactory,
		EncryptorInterface $enc,
		\Magento\Payment\Model\Source\Cctype $ccTypeSource,
		\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
		\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        HistoryFactory $orderHistoryFactory,
        OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $session,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
		array $data = []
	) {
        $this->orderHistoryFactory = $orderHistoryFactory;
        $this->_orderRepository = $orderRepository;
		$this->kushkiHelper = $kushkiHelper;
		$this->httpTextFactory = $httpTextFactory;
		$this->_enc = $enc;
		$this->ccTypeSource = $ccTypeSource;
        $this->_session = $session;
        $this->_storeManager=$storeManager;
		parent::__construct(
			$context,
			$registry,
			$extensionFactory,
			$customAttributeFactory,
			$paymentData,
			$scopeConfig,
			$logger,
			$resource,
			$resourceCollection,
			$data
		);
	}

    /**
     * Authorize a payment.
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float $amount
     * @return $this
     */
    public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {

        $info = $this->getInfoInstance();
        $baseUrl = $this->kushkiHelper->getPluginApiUrl();
        $pluginMethod = $info->getAdditionalInformation('kushki_payment_method');

        switch ($pluginMethod) {
            case "preauth":
                $this-> executePreAuthPayment($payment,$info);
                return $this;
            case "card":
                $request = $this->_buildRequestGeneric($payment, $pluginMethod);
                $this->executeUniquePayment($pluginMethod, $baseUrl, $request, $payment, $info);
                return $this;
            case "transfer":
                $requestTransfer = $this->_buildRequestGeneric($payment, $pluginMethod);
                $this->executeUniquePayment($pluginMethod, $baseUrl, $requestTransfer, $payment, $info);
                return  $this;
            case "cash":
                $cashRequest = $this->_buildRequestGeneric($payment, $pluginMethod);
                $this->executeUniquePayment($pluginMethod, $baseUrl, $cashRequest, $payment, $info);
                return $this;
            case "card_async":
                $cardAsyncRequest = $this->_buildRequestGeneric($payment, $pluginMethod);
                $this->executeUniquePayment($pluginMethod, $baseUrl, $cardAsyncRequest, $payment, $info);
                return $this;
        }

        return $this;
	}

	/**
	 * Capture Payment.
	 *
	 * @param \Magento\Payment\Model\InfoInterface $payment
	 * @param float $amount
	 * @return $this
	 */
	public function capture(\Magento\Payment\Model\InfoInterface $payment, $amount) {
		$info = $this->getInfoInstance();
		$isPreauth = $info->getAdditionalInformation('kushki_payment_method') == 'preauth' && $info->getAdditionalInformation('preauth_ticket_number');

		if ($isPreauth) {

		    $order = $payment->getOrder();
            $preauth = $payment->getAdditionalInformation('preauth');

            $captureRequest = $this->_buildCaptureRequest($preauth, $order->getIncrementId());

            $baseUrl = $this->kushkiHelper->getPluginApiUrl();
            $url = $baseUrl."capture";

            $captureResponse = $this->callCurl($url, $captureRequest);
            $captureResponse = json_decode($captureResponse, true);

            if (isset($captureResponse['ticketNumber'])) {
                $payment->setTransactionId($captureResponse['ticketNumber']);
                $payment->setAdditionalInformation("capture_ticket_number", $captureResponse['ticketNumber']);
                $payment->setAdditionalInformation("capture_at", $captureResponse['details']['created']);
                $payment->setCcLast4($captureResponse['details']['lastFourDigits']);
                $payment->setCcOwner($captureResponse['details']['cardHolderName']);
                $payment->setCcType($this->getCcType($captureResponse['details']['paymentBrand']));
                $payment->setIsTransactionClosed(1)->setShouldCloseParentTransaction(1);
                $this->_addComentHistory($order, "Kushki capture ticket number", $captureResponse['ticketNumber']);
            }
		}

        return $this;
	}

	public function validate() {
		// confirm token saved in additional data
		return true;
	}

	/**
	 * Execute API request
	 *
	 * @param string $url
	 * @param array $params
	 * @return return string
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	protected function callCurl($url, $params, $post = true) {
		$json = json_encode($params); //JSON_NUMERIC_CHECK
		$rest = $this->httpTextFactory->create();
		$rest->setMerchantId($this->_enc->decrypt($this->kushkiHelper->getConfig(\Kushki\Payment\Helper\Data::XML_PATH_KUSHKI_PRIVATE_MERCHANT_ID)));
		$rest->setContentType("application/json");
		$rest->setUrl($url);
		if ($post) {
            $result = $rest->executePost($json);
        } else {
            $result = $rest->executeDelete($json);
        }
		$response = $result->getResponseData();
		return $response;
	}

	/**
	 * Void payment
	 *
	 * @param InfoInterface|Payment|Object $payment
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @throws \Magento\Framework\Exception\State\InvalidTransitionException
	 */
	public function void(\Magento\Payment\Model\InfoInterface $payment) {
		$request = [];
		$info = $this->getInfoInstance();
		$ticketNumber = $info->getAdditionalInformation('auth_ticket_number');
		if ($ticketNumber) {
			$baseUrl = $this->kushkiHelper->getCardAPiUrl();
			$baseUrl = str_replace('card/', '', $baseUrl);
			$url = $baseUrl . 'charges/' . $ticketNumber;
			$params['fullResponse'] = true;
			$rest = $this->httpTextFactory->create();
			$rest->setMerchantId($this->_enc->decrypt($this->kushkiHelper->getConfig(\Kushki\Payment\Helper\Data::XML_PATH_KUSHKI_PRIVATE_MERCHANT_ID)));
			$rest->setContentType("application/json");
			$rest->setUrl($url);
			$json = json_encode($params);
			$result = $rest->executeDelete($json);
			$response = $result->getResponseData();
			$response = json_decode($response, true);



			if (isset($response['code']) && (string) $response['code'] == "K004") {
				throw new \Magento\Framework\Exception\LocalizedException(
					__('INVALID ACCOUNT DATA INFO')
				);
			}
			if (isset($response['code']) && (string) $response['code'] == "K005") {
				throw new \Magento\Framework\Exception\LocalizedException(
					__('INVALID ACCOUNT NUMBER')
				);
			}
			if (!isset($response['details']) || ((string) $response['details']['responseCode'] != "000")) {
				throw new \Magento\Framework\Exception\LocalizedException(
					__('PROCESSOR DECLINED')
				);
			}

			$payment->setTransactionId(
				$response['details']['transactionId']
			)->setIsTransactionClosed(
				1
			)->setShouldCloseParentTransaction(
				1
			);
			$info->setAdditionalInformation("void_ticket_number", $response['ticketNumber']);

			return $this;
		}
		throw new \Exception("Transaction Id did not found to void autorization");
		return $this;
	}

	/**
	 * Check void availability
	 * @return bool
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function canVoid() {
		if ($this->getInfoInstance()->getAmountPaid()) {
			$this->_canVoid = false;
		}

		return $this->_canVoid;
	}

	/**
	 * Attempt to void the authorization on cancelling
	 *
	 * @param InfoInterface|Object $payment
	 * @return $this
	 */
	public function cancel(\Magento\Payment\Model\InfoInterface $payment) {
        try {
            $order = $payment->getOrder();
            if (!$order->getId()) {
                throw new \Magento\Framework\Exception\LocalizedException(__('The order no longer exists.'));
            }
            $status = \Magento\Sales\Model\Order::STATE_CANCELED;
            $this->_addComentHistory($order, "Kushki payment canceled.", "Ok", $status);

            return $this;

        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
        }
	}

	protected function getCcType($brandName)
	{
		$ccTypes = $this->ccTypeSource->toOptionArray();
		$ccTypeCode = '';
        foreach ($ccTypes as $ccType) {

	        if($brandName == 'Master Card')
	        {
	        	$brandName = 'MasterCard';
	        }

            if(strtolower($ccType['label']) == strtolower($brandName))
            {
                $ccTypeCode = $ccType['value'];
                break;
            }
        }
        return $ccTypeCode;
	}

	/**
	 * Refund capture
	 *
	 * @param InfoInterface|Payment|Object $payment
	 * @param float $amount
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @throws \Magento\Framework\Exception\State\InvalidTransitionException
	 */
	public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
		$params = [];
		$info = $this->getInfoInstance();
		$ticketNumber = $info->getAdditionalInformation('capture_ticket_number');
		if ($ticketNumber) {
			$baseUrl = $this->kushkiHelper->getPluginApiUrl();
			$url = $baseUrl . 'refund/' . $ticketNumber;
            $params['channel'] = "MAGENTO";
            $params['amount']= $amount;
			$response = $this->callCurl($url, $params, false);
			$response = json_decode($response, true);

            if (isset($response['code'])) {
                $data = [];
                $data['request'] = $params;
                $data['response'] = $response;
                $this->_debug($data);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __($response['message'])
                );
            }
			if (isset($response['details']) && ((string) $response['details']['responseCode'] != "000")) {
				throw new \Magento\Framework\Exception\LocalizedException(
					__('PROCESSOR DECLINED')
				);
			}
			$order = $payment->getOrder();
			$this->_setTransactionInfo($payment, true, $response['ticketNumber']);
			$info->setAdditionalInformation("refund_ticket_number", $response['ticketNumber']);
            $this->_addComentHistory($order, "Kushki refund", "Sucessful");
            $this->_addComentHistory($order, "Kushki refund ticket number", $response['ticketNumber']);

            return $this;
		}
		throw new \Magento\Framework\Exception\LocalizedException(__('Transaction not valid to request Kushki refund'));
		return $this;
	}

    /**
     * Get amount values from order
     *
     * @param object order
     * @return array
     */
    public function getAmountValues($order): array {
        $subtotalIva = 0;
        $subtotalIva0 = 0;
        $iva = 0;
        $items = $order->getItems();
        $shippingTax = $order->getBaseShippingTaxAmount();
        $shippingAmount = $order->getBaseShippingAmount();
        foreach ($items as $item) {
            if($item->getBaseTaxAmount() > 0) {
                $subtotalIva += $item->getRowTotal();
                $iva += $item->getBaseTaxAmount();
            } else {
                $subtotalIva0 += $item->getRowTotal();
            }
        }

        if ($shippingTax > 0) {
            $subtotalIva += $shippingAmount;
            $iva += $shippingTax;
        } else {
            $subtotalIva0 += $shippingAmount;
        }

        $subtotalIva  = round( floatval( $subtotalIva ), 2 );
        $subtotalIva0 = round( floatval( $subtotalIva0 ), 2 );
        $iva          = round( floatval( $iva ), 2 );

        return array(
            "subtotalIva"  => $subtotalIva,
            "subtotalIva0" => $subtotalIva0,
            "iva"          => $iva
        );
    }

	/**
	 * Assign data to info model instancesetMerchantId
	 *
	 * @param \Magento\Framework\DataObject|mixed $data
	 * @return $this
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function assignData(\Magento\Framework\DataObject $data) {
		parent::assignData($data);
		$info = $this->getInfoInstance();

		if ($data->getAdditionalData('kushki_token')) {
			$info->setAdditionalInformation("kushki_token", $data->getAdditionalData('kushki_token'));
		}
		if ($data->getAdditionalData('kushki_deffered')) {
			$info->setAdditionalInformation("kushki_deffered", $data->getAdditionalData('kushki_deffered'));
		}
		if ($data->getAdditionalData('kushki_deffered_type')) {
			$info->setAdditionalInformation("kushki_deffered_type", $data->getAdditionalData('kushki_deffered_type'));
		}
        if ($data->getAdditionalData('kushki_payment_method')) {
            $info->setAdditionalInformation("kushki_payment_method", $data->getAdditionalData('kushki_payment_method'));
        }
		if ($data->getAdditionalData('kushki_months_of_grace')) {
         	$info->setAdditionalInformation("kushki_months_of_grace", $data->getAdditionalData('kushki_months_of_grace'));
        }
		return $this;
	}

    /**
     * @param InfoInterface $payment
     * @param $info
     */
    private function executePreAuthPayment(  InfoInterface $payment, $info)
    {
        if(is_null($payment->getParentTransactionId())) {
            //$authResponse = $this->authorize($payment, 0);
            $request = $this->_buildRequestGeneric($payment, "preauth");
            $response = $this->makeAuthRequest($request);

            if(isset($response['ticketNumber'])) {
                $payment->setParentTransactionId($response['ticketNumber']);
            }

            $this->_setTransactionInfo($payment, false, $response['ticketNumber']);

            $order = $payment->getOrder();
            $this->_addComentHistory($order, "Kushki payment with", "Pre authorization card");
            $this->_addComentHistory($order, "Kushki pre authorization ticket number", $response['ticketNumber']);
            $info->setAdditionalInformation("preauth", $response);
            $info->setAdditionalInformation("preauth_ticket_number", $response['ticketNumber']);
        }
    }

    /**
     * @param $request
     * @return array
     */
    private function makeAuthRequest(array $request)
    {
        $baseUrl = $this->kushkiHelper->getPluginApiUrl();
        $url = $baseUrl."preAuth";

        $response = $this->callCurl($url, $request);
        $response = json_decode($response, true);

        if (isset($response['code']) || isset($response['message'])) {
            $data = [];
            $data['request'] = $request;
            $data['response'] = $response;
            $this->_debug($data);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('PROCESS DECLINED: ' . $response['message'])
            );
        }
        if (!isset($response['details']) ||
            !isset($response['details']['transactionStatus']) ||
            $response['details']['transactionStatus'] != 'APPROVAL' ||
            !isset($response['details']['responseCode']) ||
            (string)$response['details']['responseCode'] != "000") {
            $data = [];
            $data['request'] = $request;
            $data['response'] = $response;
            $this->_debug($data);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('PROCESSOR DECLINED')
            );
        }

        return $response;
    }


    private function executeUniquePayment($paymentType, $baseUrl, array $request, InfoInterface $payment, $info) {
        $order = $payment->getOrder();
        $url = $baseUrl . "charge";
        $response = $this->callCurl($url, $request);
        $response = json_decode($response, true);

        if (isset($response['code']) || isset($response['message'])) {
            $data = [];
            $data['request'] = $request;
            $data['response'] = $response;
            $this->_debug($data);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('PROCESS DECLINED: ' . $response['message'])
            );
        }
        if ($paymentType == "card") {
            $status = \Magento\Sales\Model\Order::STATE_PROCESSING;
        } else {
            $status = "pending";
        }

        switch ($paymentType) {
            case "cash":
                $this->_setTransactionInfo($payment, false, $response['ticketNumber']);
                $info->setAdditionalInformation("capture_ticket_number", $response['ticketNumber']);
                $info->setAdditionalInformation("cash_pdf", $response['pdfUrl']);
                $this->_addComentHistory($order, "Kushki payment with", "Cash", $status);
                $this->_addComentHistory($order, "Kushki ticket number", $response['ticketNumber'], $status);
                $this->_addComentHistory($order, "Kushki pdf order", $response['pdfUrl'], $status);
                break;
            case "transfer":
                $this->_setTransactionInfo($payment, false, $response["transactionReference"]);
                $info->setAdditionalInformation("transactionReference", $response["transactionReference"] );
                $info->setAdditionalInformation("redirectUrl",$response["redirectUrl"] );
                $this->_addComentHistory($order, "Kushki payment with", "Transfer", $status);
                $this->_addComentHistory($order, "Kushki transaction reference", $response["transactionReference"], $status);
                break;
            case "card_async":
                $this->_setTransactionInfo($payment, false, $response["transactionReference"]);
                $info->setAdditionalInformation("capture_at", $response['details']['created']);
                $info->setAdditionalInformation("redirectUrl", $response['redirectUrl']);
                $info->setAdditionalInformation("transactionReference", $response['transactionReference']);
                $this->_addComentHistory($order, "Kushki payment with", "Debit card", $status);
                $this->_addComentHistory($order, "Kushki transaction reference", $response["transactionReference"], $status);
                break;
            default:
                $this->_setTransactionInfo($payment, true, $response["ticketNumber"]);
                $info->setAdditionalInformation("capture_ticket_number", $response['ticketNumber']);
                $info->setAdditionalInformation("capture_at", $response['details']['created']);
                $this->_addComentHistory($order, "Kushki payment with", "Credit card", $status);
                $this->_addComentHistory($order, "Kushki ticket number", $response['ticketNumber'], $status);

                $payment->setCcLast4($response['details']['binInfo']['lastFourDigits']);
                $payment->setCcOwner($response['details']['cardHolderName']);
                $payment->setCcType($this->getCcType($response['details']['paymentBrand']));
        }
    }

    private function _addComentHistory($order, $name, $comment, $status = false): bool
    {
        try {
            $history = $this->orderHistoryFactory
                ->create()
                ->setStatus(!empty($status) ? $status : $order->getStatus())
                ->setEntityName(\Magento\Sales\Model\Order::ENTITY)
                ->setComment(
                    __($name . ": %1.", $comment)
                );
            $order->addStatusHistory($history);
            $this->_orderRepository->save($order);
            return true;
        } catch (\Exception $e){
            return false;
        }
    }

    private function _buildRequestGeneric($payment, $paymentType): array
    {
        $request = [];
        $order = $payment->getOrder();
        $info = $this->getInfoInstance();
        $token = $info->getAdditionalInformation('kushki_token');
        if (isset($token) && is_string($token)) {
            $request['token'] = $token;
        }
        $amount = $this->getAmountValues($order);
        $request['amount'] = [
            "subtotalIva" => $amount['subtotalIva'],
            "subtotalIva0" => $amount['subtotalIva0'],
            "ice" => 0,
            "iva" => $amount['iva'],
            "currency" => $order->getOrderCurrencyCode(),
        ];
        $request['orderId'] = $order->getIncrementId();
        $request['channel'] = "MAGENTO";

        switch ($paymentType) {
            case "card":
                $request['activationMethod'] = "singlePayment";
                $request['paymentMethod'] = "creditCard";
                break;
            case "transfer":
                $request['activationMethod'] = "transferPayment";
                $request['paymentMethod'] = "transfer";
                break;
            case "cash":
                $request['activationMethod'] = "cashPayment";
                $request['paymentMethod'] = "cash";
                break;
            case "card_async":
                $request['activationMethod'] = "cardAsyncPayment";
                $request['paymentMethod'] = "creditCard";
                break;
        }

        if($info->getAdditionalInformation('kushki_deffered') || $info->getAdditionalInformation('kushki_deffered_type') || $info->getAdditionalInformation('kushki_months_of_grace'))
        {
            $request['deferred'] = [];
            if($info->getAdditionalInformation('kushki_deffered') && $info->getAdditionalInformation('kushki_deffered') != ''){
                $request['deferred']['months'] = (int) $info->getAdditionalInformation('kushki_deffered');
            }else{
                $request['deferred']['months'] = (int) 0;
            }

            if($info->getAdditionalInformation('kushki_deffered_type') && !($info->getAdditionalInformation('kushki_deffered_type') == 'all' || $info->getAdditionalInformation('kushki_deffered_type') == '')){
                $request['deferred']['creditType'] = $info->getAdditionalInformation('kushki_deffered_type');
            }else{
                $request['deferred']['creditType'] = '000';
            }

            if($info->getAdditionalInformation('kushki_months_of_grace') && $info->getAdditionalInformation('kushki_months_of_grace') != ''){
                $request['deferred']['graceMonths'] = strval($info->getAdditionalInformation('kushki_months_of_grace'));
            }else{
                $request['deferred']['graceMonths'] = '00';
            }

        }

        $metadata = $this->_getOrderMetadata($order);
        $request = array_merge($request, $metadata);

        if ($paymentType == "card" || $paymentType == "preauth") {
            $sift = $this->_getSiftFields($order);
            $request = array_merge($request, $sift);
        }

        return $request;
    }

    private function _getOrderMetadata($order): array
    {
        $request = [];
        $request['metadata'] = [];
        $request['metadata']['plugin'] = 'magento2';
        $request['metadata']["city"] = $order->getBillingAddress()->getCity();
        $request['metadata']["country"] = $order->getBillingAddress()->getCountryId();
        $request['metadata']["postalCode"] = $order->getBillingAddress()->getPostcode();
        $request['metadata']["billingAddressPhone"] = $order->getBillingAddress()->getTelephone();
        $request['metadata']["province"] = $order->getBillingAddress()->getRegion() ?: "NA";
        $request['metadata']["billingAddress"] = $order->getBillingAddress()->getStreet()[0];
        $request['metadata']["email"] = $order->getBillingAddress()->getEmail();
        $request['metadata']["name"] = $order->getBillingAddress()->getFirstname();
        $request['metadata']["currency"] = $order->getBaseCurrencyCode();
        $request['metadata']["totalAmount"] = $order->getGrandTotal();
        $request['metadata']["ip"] = $order->getRemoteIp();
        $request['metadata']['orderId'] = $order->getIncrementId();
        return $request;
    }

    private function _getSiftFields($order): array
    {
        $orderDetails = array(
            "siteDomain" => $this->_storeManager->getStore()->getBaseUrl(),
            "billingDetails" => array(
                "firstName" => $order->getBillingAddress()->getFirstname(),
                "lastName" => $order->getBillingAddress()->getLastname(),
                "phone" => $order->getBillingAddress()->getTelephone(),
                "address" => $order->getBillingAddress()->getStreet()[0],
                "city" => $order->getBillingAddress()->getCity(),
                "region" => $order->getBillingAddress()->getRegion() ?: "NA",
                "country" => $order->getBillingAddress()->getCountryId(),
                "zipCode" => $order->getBillingAddress()->getPostcode()
            ),
            "shippingDetails" => array(
                "firstName" => $order->getShippingAddress()->getFirstname(),
                "lastName" => $order->getShippingAddress()->getLastname(),
                "phone" => $order->getShippingAddress()->getTelephone(),
                "address" => $order->getShippingAddress()->getStreet()[0],
                "city" => $order->getShippingAddress()->getCity(),
                "region" => $order->getShippingAddress()->getRegion() ?: "NA",
                "country" => $order->getShippingAddress()->getCountryId(),
                "zipCode" => $order->getShippingAddress()->getPostcode()
            )
        );

        $items = $this->_session->getQuote()->getAllVisibleItems();

        foreach($items as $product) {
            $products [] = array(
                "id" => $product->getProductId(),
                "title" => $product->getName(),
                "price" => floatval($product->getPrice()),
                "sku" => $product->getSku(),
                "quantity" => $product->getQty()
            );
        }

        return array(
            "orderDetails" => $orderDetails,
            "productDetails" => array(
                "products" => $products
            )
        );
    }

    public function _setTransactionInfo(\Magento\Payment\Model\InfoInterface $payment, $closed, $id): InfoInterface
    {
        $payment
            ->setTransactionId($id)
            ->setIsTransactionClosed($closed ? 1 : 0)
            ->setShouldCloseParentTransaction($closed ? 1 : 0);
        return $payment;
    }

    private function _buildCaptureRequest($authResponse, $orderId) {
        $response = [];
        $response['ticketNumber'] = $authResponse['ticketNumber'];
        $response['orderId'] = $orderId;

        return $response;
    }

}
