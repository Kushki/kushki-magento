<?php

namespace Kushki\Payment\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\InfoInterface;
use Magento\Paypal\Model\Payflow\Service\Response\Handler\HandlerInterface;
use Magento\Sales\Model\Order\Payment;

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
	 *
	 * @var bool
	 */
	protected $_canRefundInvoicePartial = false;

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
		array $data = []
	) {
		$this->kushkiHelper = $kushkiHelper;
		$this->httpTextFactory = $httpTextFactory;
		$this->_enc = $enc;
		$this->ccTypeSource = $ccTypeSource;
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
	 * @throws \Magento\Framework\Exception\LocalizedException
	 * @throws \Magento\Framework\Exception\State\InvalidTransitionException
	 */
	public function authorize(\Magento\Payment\Model\InfoInterface $payment, $amount) {
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

		$order = $payment->getOrder();
		$info = $this->getInfoInstance();
		$request = $this->_buildRequest($payment, $amount);
		//$defferedMonths = $info->getAdditionalInformation('kushki_deffered');
		//[kushkiPaymentMethod] => card
    //[kushkiDeferredType] => 001
    //[kushkiDeferred] => 3
    //[kushkiMonthsOfGrace] => 6

		//if($defferedMonths)
		//{
		//	$request['deferred']=["graceMonths"=>"00","creditType"=>"01","months"=> (int)$defferedMonths];
		//}

		$baseUrl = $this->kushkiHelper->getAPiUrl();
		$url = $baseUrl . "charges";
		$response = $this->callCurl($url, $request);
		$response = json_decode($response, true);

		if (isset($response['code']) && (string) $response['code'] == "K004") {
			$data = [];
			$data['request'] = $request;
			$data['response'] = $response;
			$this->_debug($data);
			throw new \Magento\Framework\Exception\LocalizedException(
				__('INVALID ACCOUNT DATA INFO')
			);
		}
		if (isset($response['code']) && (string) $response['code'] == "K005") {
			$data = [];
			$data['request'] = $request;
			$data['response'] = $response;
			$this->_debug($data);
			throw new \Magento\Framework\Exception\LocalizedException(
				__('INVALID ACCOUNT NUMBER')
			);
		}
		if (!isset($response['details']) ||
			!isset($response['details']['transactionStatus']) ||
			$response['details']['transactionStatus'] != 'APPROVAL' ||
			!isset($response['details']['responseCode']) ||
			(string) $response['details']['responseCode'] != "000") {
			$data = [];
			$data['request'] = $request;
			$data['response'] = $response;
			$this->_debug($data);
			throw new \Magento\Framework\Exception\LocalizedException(
				__('PROCESSOR DECLINED')
			);
		}
		$this->setTransStatus($payment, $response, true);
		$info->setAdditionalInformation("capture_ticket_number", $response['ticketNumber']);
		$info->setAdditionalInformation("capture_at", $response['details']['created']);

		$payment->setCcLast4($response['details']['lastFourDigits']);
		$payment->setCcOwner($response['details']['cardHolderName']);
		$payment->setCcType($this->getCcType($response['details']['paymentBrand']));

		return $this;
	}

	/**
	 * @param DataObject $payment
	 * @param DataObject $response
	 * @param boolean $capture
	 * @return Object
	 * @throws \Magento\Framework\Exception\LocalizedException
	 */
	public function setTransStatus($payment, $response, $capture = false) {
		if (isset($response['details']['transactionId'])) {
			$payment->setTransactionId($response['details']['transactionId']);
			if (!$capture) {
				$payment->setIsTransactionClosed(0);
			}

		}
		return $payment;
	}

	/**
	 * Do not validate payment form using server methods
	 *
	 * @return bool
	 */
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
	protected function callCurl($url, $params) {
		$json = json_encode($params); //JSON_NUMERIC_CHECK
		$rest = $this->httpTextFactory->create();
		$rest->setMerchantId($this->_enc->decrypt($this->kushkiHelper->getConfig(\Kushki\Payment\Helper\Data::XML_PATH_KUSHKI_PRIVATE_MERCHANT_ID)));
		$rest->setContentType("application/json");
		$rest->setUrl($url);
		$result = $rest->executePost($json);
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
			$baseUrl = $this->kushkiHelper->getAPiUrl();
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
		if (!$payment->getOrder()->getInvoiceCollection()->count()) {
			return $this->void($payment);
		}

		return false;
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
		$request = [];
		$info = $this->getInfoInstance();
		$ticketNumber = $info->getAdditionalInformation('capture_ticket_number');
		if ($ticketNumber) {
			$baseUrl = $this->kushkiHelper->getAPiUrl();
			$baseUrl = str_replace('card/', '', $baseUrl);
			$url = $baseUrl . 'refund/' . $ticketNumber;
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
			if (isset($response['details']) && ((string) $response['details']['responseCode'] != "000")) {
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
			$info->setAdditionalInformation("refund_ticket_number", $response['ticketNumber']);
			return $this;
		}
		throw new \Magento\Framework\Exception\LocalizedException(__('Transaction Id did not found to refund'));
		return $this;
	}

	/**
	 * Build request array
	 *
	 * @param object $payment
	 * @return array
	 */
	protected function _buildRequest($payment, $amount) {
		$request = [];
		$order = $payment->getOrder();
		$info = $this->getInfoInstance();

		$token = $info->getAdditionalInformation('kushki_token');
		if (isset($token) && is_string($token)) {
			$request['token'] = $token;

		}
		$request['amount'] = [
			"subtotalIva" => 0,
			"subtotalIva0" => (float) $order->getBaseGrandTotal(),
			"ice" => 0,
			"iva" => 0,
			"currency" => $order->getOrderCurrencyCode(),
		];
		if($info->getAdditionalInformation('kushki_deffered') || $info->getAdditionalInformation('kushki_deffered_type') || $info->getAdditionalInformation('kushki_months_of_grace'))
		{
			$request['deferred'] = [];
			if($info->getAdditionalInformation('kushki_deffered') ){
				if($info->getAdditionalInformation('kushki_deffered') == ''){
					$request['deferred']['months'] =  (int) 0;
				}else{
					$request['deferred']['months'] = (int) $info->getAdditionalInformation('kushki_deffered') ;
				}

			}else{
				$request['deferred']['months'] = (int) 0;
			}

			if($info->getAdditionalInformation('kushki_deffered_type')){
				if($info->getAdditionalInformation('kushki_deffered_type') == '' || $info->getAdditionalInformation('kushki_deffered_type') == 'all'){
					$request['deferred']['creditType'] = '000';
				}else{
					$request['deferred']['creditType'] = $info->getAdditionalInformation('kushki_deffered_type');
				}

			}else{
				$request['deferred']['creditType'] = '000';
			}

			if($info->getAdditionalInformation('kushki_months_of_grace')){
				if($info->getAdditionalInformation('kushki_months_of_grace') == ''){
					$request['deferred']['graceMonths'] = '00';
				}else{
					$request['deferred']['graceMonths'] = $info->getAdditionalInformation('kushki_months_of_grace');
				}
			}else{
				$request['deferred']['graceMonths'] = '00';
			}

		}


		$request['metadata'] = [];
		$request['metadata']['plugin'] = 'magento2';

		$request["fullResponse"] = (bool) true;
		//$request["fullResponse"] = (bool) true;
		return $request;
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
		if ($data->getAdditionalData('kushki_months_of_grace')) {
    	$info->setAdditionalInformation("kushki_months_of_grace", $data->getAdditionalData('kushki_months_of_grace'));
    }
		return $this;
	}
}
