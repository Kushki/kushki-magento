<?php

namespace Kushki\Payment\Model\Api;

class HttpRest extends Http
{
    /**
     * @param \Kushki\Payment\Model\Api\Curl                 $curl
     * @param \Kushki\Payment\Api\Data\HttpResponseInterface $returnData
     */
    public function __construct(
        \Kushki\Payment\Model\Api\Curl $curl,
        \Kushki\Payment\Api\Data\HttpResponseInterface $returnData
    ) {
        parent::__construct($curl, $returnData);
        $this->setContentType("application/json");
    }

    /**
     * @return \Kushki\Payment\Api\Data\HttpResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function processResponse()
    {
        $data = preg_split('/^\r?$/m', $this->getResponseData(), 2);
        $data = json_decode(trim($data[1]), true);
        $this->getReturnData()->setStatus($this->getResponseCode());
        $this->getReturnData()->setResponseData($data);
        return $this->getReturnData();
    }
}
