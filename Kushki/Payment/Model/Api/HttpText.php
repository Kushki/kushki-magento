<?php

namespace Kushki\Payment\Model\Api;

class HttpText extends Http
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

        $this->setContentType("application/x-www-form-urlencoded");
    }

    public function processResponse()
    {
        $data = preg_split('/^\r?$/m', $this->getResponseData(), 2);
        $data = urldecode(trim($data[1]));
        $this->getReturnData()->setStatus($this->getResponseCode());
        $this->getReturnData()->setResponseData($data);
        return $this->getReturnData();
    }
}
