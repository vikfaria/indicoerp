<?php

namespace Workdo\Iyzipay\Iyzipay\Request;

use Workdo\Iyzipay\Iyzipay\JsonBuilder;
use Workdo\Iyzipay\Iyzipay\Request;
use Workdo\Iyzipay\Iyzipay\RequestStringBuilder;

class RetrieveCheckoutFormRequest extends Request
{
    private $token;

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getJsonObject()
    {
        return JsonBuilder::create()
            ->add("token", $this->getToken())
            ->getObject();
    }

    public function toPKIRequestString()
    {
        return RequestStringBuilder::create()
            ->append("token", $this->getToken())
            ->getRequestString();
    }
}