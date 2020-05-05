<?php
namespace Kushki\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Cache\TypeListInterface;


/**
 * Class Data
 * @package Salecto\Message\Helper
 */
class Data extends AbstractHelper {

	const XML_PATH_KUSHKI_PUBLIC_MERCHANT_ID = 'payment/kushki_pay/public_merchant_id';
	const XML_PATH_KUSHKI_PRIVATE_MERCHANT_ID = 'payment/kushki_pay/private_merchant_id';
	const XML_PATH_KUSHKI_API_URL = 'payment/kushki_pay/api_url';
	const XML_PATH_KUSHKI_MODE = 'payment/kushki_pay/mode';

	const TEST_MODE_API_URL = 'https://api-uat.kushkipagos.com/card/v1/';
	const API_URL = 'https://api.kushkipagos.com/card/v1/';

	/**
	 * @var AdminSession
	 */
	protected $authSession;

	/**
   	 * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

	/**
	 * @var \Magento\Framework\HTTP\Client\Curl
	 */
	protected $_curl;

	/**
	 * @var TimezoneInterface
	 */
	protected $_date;

	/**
	 * @var WriterInterface
	 */
	protected $_configWriter;

	/**
	 * @var TypeListInterface
	 */
	protected $_cacheTypeList;

	/**
	 * @param AdminSession         $authSession   
	 * @param Curl                 $curl          
	 * @param MessageService       $messageService
	 * @param TimezoneInterface    $date          
	 * @param WriterInterface      $configWriter  
	 * @param ScopeConfigInterface $scopeConfig   
	 */
	public function __construct(
    	AdminSession $authSession,
    	Curl $curl,
    	TimezoneInterface $date,
    	WriterInterface $configWriter,
    	ScopeConfigInterface $scopeConfig,
    	TypeListInterface $cacheTypeList
	) {
		$this->authSession = $authSession;
		$this->_curl = $curl;
		$this->_date = $date;
		$this->_configWriter = $configWriter;
		$this->scopeConfig = $scopeConfig;
		$this->_cacheTypeList = $cacheTypeList;		
	}

	/**
	 * @return object
	 */
	public function getAdminSession()
	{
		return $this->authSession;
	}

	/**
     * @param $config_path
     * @return mixed
     */
    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            'default'
        );
    }

    public function getAPiUrl()
	{
		if((boolean) $this->getConfig(self::XML_PATH_KUSHKI_MODE))
		{
			return self::TEST_MODE_API_URL;
		}	
		return self::API_URL;
	}

}