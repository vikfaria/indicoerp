<?php

namespace Workdo\Iyzipay\Iyzipay\Model;

use Workdo\Iyzipay\Iyzipay\IyzipayResource;
use Workdo\Iyzipay\Iyzipay\Options;
use Workdo\Iyzipay\Iyzipay\Request\RetrieveCheckoutFormRequest;

class CheckoutForm extends IyzipayResource
{
    private $token;
    private $callbackUrl;
    private $paymentStatus;

    public static function retrieve(RetrieveCheckoutFormRequest $request, Options $options)
    {
        try {
            $rawResult = parent::httpClient()->post($options->getBaseUrl() . "/payment/iyzipos/checkoutform/auth/ecom/detail", parent::getHttpHeaders($request, $options), $request->toJsonString());
            $jsonResult = json_decode($rawResult, true);
            
            $checkoutForm = new CheckoutForm();
            $checkoutForm->setStatus($jsonResult['status'] ?? 'failure');
            $checkoutForm->setPaymentStatus($jsonResult['paymentStatus'] ?? 'FAILURE');
            $checkoutForm->setToken($jsonResult['token'] ?? null);
            $checkoutForm->setCallbackUrl($jsonResult['callbackUrl'] ?? null);
            
            return $checkoutForm;
        } catch (\Exception $e) {
            $checkoutForm = new CheckoutForm();
            $checkoutForm->setStatus('failure');
            $checkoutForm->setPaymentStatus('FAILURE');
            return $checkoutForm;
        }
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;
    }
}