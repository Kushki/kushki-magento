<?php

namespace Kushki\Payment\Model\Api;

abstract class Http
{
    /** @var string */
    private $basicAuth;

    /** @var string */
    private $merchantId;

    /** @var string */
    private $contentType;

    /** @var string */
    private $responseData;

    /** @var string */
    private $destinationUrl;

    /** @var  \Kushki\Payment\Api\Data\HttpResponseInterface */
    private $returnData;

    /** @var integer */
    private $responseCode;

    /** @var \Magento\Framework\HTTP\Adapter\Curl */
    private $curl;


    public function __construct(
        \Kushki\Payment\Model\Api\Curl $curl,
        \Kushki\Payment\Api\Data\HttpResponseInterface $returnData
    ) {
        $this->curl        = $curl;
        $this->returnData  = $returnData;
    }


    /**
     * @return integer
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * @return \Kushki\Payment\Api\Data\HttpResponseInterface
     */
    public function getReturnData()
    {
        return $this->returnData;
    }


    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function setUrl($url)
    {
        $this->destinationUrl = $url;
    }

    public function initialize()
    {
        $config = [
            'timeout'    => 120,
            'verifyhost' => 2,
        ];
        $this->curl->setConfig($config);
    }

    /**
     * @param $body
     * @return \Kushki\Payment\Api\Data\HttpResponseInterface
     */
    public function executePost($body)
    {
        $this->initialize();

        $headers=[];
        $headers[]='Content-type: ' . $this->contentType;
        $headers[] = 'Private-Merchant-Id: '.$this->merchantId;

        $this->curl->write(
            \Zend_Http_Client::POST,
            $this->destinationUrl,
            '1.0',
            $headers,
            $body
        );
        $this->responseData = $this->curl->read();

        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();

        return $this->processResponse();
    }

    public function executeDelete($body)
    {
        $this->initialize();

        $headers=[];
        $headers[]='Content-type: ' . $this->contentType;
        $headers[] = 'Private-Merchant-Id: '.$this->merchantId;
        $this->curl->write(
            \Zend_Http_Client::DELETE,
            $this->destinationUrl,
            '1.0',
            $headers,
            $body
        );
        $this->responseData = $this->curl->read();
        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();

        return $this->processResponse();
    }

    /**
     * @return \Kushki\Payment\Api\Data\HttpResponseInterface
     */
    public function executeGet()
    {
        $this->initialize();
        $headers=[];
        $headers[]='Content-type: ' . $this->contentType;
        if ($this->basicAuth !== null) {
            $headers[] = 'Authorization: Basic '.$this->basicAuth;
        }
        if ($this->bearerAuth !== null) {
            $headers[] = 'Authorization: Bearer '.$this->bearerAuth;
        }
        $this->curl->write(
            \Zend_Http_Client::GET,
            $this->destinationUrl,
            '1.0',
            $headers
        );
        $this->responseData = $this->curl->read();
        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();
        return $this->processResponse();
    }

    /**
     * @return \Kushki\Payment\Api\Data\HttpResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract public function processResponse();
}
