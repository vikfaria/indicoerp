<?php

namespace Workdo\Iyzipay\Services;

use Workdo\Iyzipay\Iyzipay\Options;
use Workdo\Iyzipay\Iyzipay\Model\CheckoutFormInitialize;
use Workdo\Iyzipay\Iyzipay\Model\CheckoutForm;
use Workdo\Iyzipay\Iyzipay\Request\CreateCheckoutFormInitializeRequest;
use Workdo\Iyzipay\Iyzipay\Request\RetrieveCheckoutFormRequest;
use Workdo\Iyzipay\Iyzipay\Model\Address;
use Workdo\Iyzipay\Iyzipay\Model\BasketItem;
use Workdo\Iyzipay\Iyzipay\Model\BasketItemType;
use Workdo\Iyzipay\Iyzipay\Model\Buyer;
use Workdo\Iyzipay\Iyzipay\Model\PaymentGroup;
use Exception;
use InvalidArgumentException;

class IyzipayService
{
    protected $config;
    protected $options;
    protected $requiredConfigVars = ['iyzipay_api_key' => true, 'iyzipay_secret_key' => true];
    protected $configVars = ['iyzipay_api_key' => null, 'iyzipay_secret_key' => null, 'iyzipay_mode' => 'sandbox'];

    public function __construct($config = [])
    {
        $this->config = $config;
        foreach ($this->requiredConfigVars as $param => $required) {
            if (array_key_exists($param, $config)) {
                $this->configVars[$param] = $config[$param];
            } elseif ($required) {
                throw new InvalidArgumentException(__("Missing required parameter: :param", ['param' => $param]));
            }
        }

        if (array_key_exists('iyzipay_mode', $config)) {
            $this->configVars['iyzipay_mode'] = $config['iyzipay_mode'];
        }

        $this->initializeOptions();
    }

    protected function initializeOptions()
    {
        $this->options = new Options();
        $this->options->setApiKey($this->configVars['iyzipay_api_key']);
        $this->options->setSecretKey($this->configVars['iyzipay_secret_key']);
        $baseUrl = ($this->configVars['iyzipay_mode'] == 'sandbox') 
            ? 'https://sandbox-api.iyzipay.com' 
            : 'https://api.iyzipay.com';
        $this->options->setBaseUrl($baseUrl);
    }

    public function createCheckoutForm($data)
    {
        $this->validateCheckoutData($data);

        $request = new CreateCheckoutFormInitializeRequest();
        $request->setLocale('en');
        $request->setPrice($data['price']);
        $request->setPaidPrice($data['price']);
        $request->setCurrency($data['currency']);
        $request->setCallbackUrl($data['callback_url']);
        $request->setEnabledInstallments(array(1));
        $request->setPaymentGroup(PaymentGroup::PRODUCT);

        // Set buyer information
        $buyer = new Buyer();
        $buyer->setId($data['buyer']['id']);
        $buyer->setName($data['buyer']['name']);
        $buyer->setSurname($data['buyer']['surname']);
        $buyer->setEmail($data['buyer']['email']);
        
        // Set optional fields with defaults
        $buyer->setGsmNumber($data['buyer']['phone'] ?? '+905555555555');
        $buyer->setIdentityNumber($data['buyer']['identity_number'] ?? '11111111111');
        $buyer->setLastLoginDate($data['buyer']['last_login_date'] ?? '2023-03-05 12:43:35');
        $buyer->setRegistrationDate($data['buyer']['registration_date'] ?? '2023-04-21 15:12:09');
        $buyer->setRegistrationAddress($data['buyer']['address'] ?? __('Default Address'));
        $buyer->setIp($data['buyer']['ip'] ?? request()->ip());
        $buyer->setCity($data['buyer']['city'] ?? __('Istanbul'));
        $buyer->setCountry($data['buyer']['country'] ?? __('Turkey'));
        $buyer->setZipCode($data['buyer']['zip_code'] ?? '34000');
        $request->setBuyer($buyer);

        // Set shipping address
        $shippingAddress = new Address();
        $shippingAddress->setContactName($data['buyer']['name']);
        $shippingAddress->setCity($data['buyer']['city'] ?? __('Istanbul'));
        $shippingAddress->setCountry($data['buyer']['country'] ?? __('Turkey'));
        $shippingAddress->setAddress($data['buyer']['address'] ?? __('Default Address'));
        $shippingAddress->setZipCode($data['buyer']['zip_code'] ?? '34000');
        $request->setShippingAddress($shippingAddress);

        // Set billing address
        $billingAddress = new Address();
        $billingAddress->setContactName($data['buyer']['name']);
        $billingAddress->setCity($data['buyer']['city'] ?? __('Istanbul'));
        $billingAddress->setCountry($data['buyer']['country'] ?? __('Turkey'));
        $billingAddress->setAddress($data['buyer']['address'] ?? __('Default Address'));
        $billingAddress->setZipCode($data['buyer']['zip_code'] ?? '34000');
        $request->setBillingAddress($billingAddress);

        // Set basket items
        $basketItems = array();
        $basketItem = new BasketItem();
        $basketItem->setId("BI101");
        $basketItem->setName($data['product_name']);
        $basketItem->setCategory1($data['category1'] ?? __('Product'));
        $basketItem->setCategory2($data['category2'] ?? __('Payment'));
        $basketItem->setItemType(BasketItemType::PHYSICAL);
        $basketItem->setPrice($data['price']);
        $basketItems[0] = $basketItem;
        $request->setBasketItems($basketItems);

        $checkoutFormInitialize = CheckoutFormInitialize::create($request, $this->options);
        
        if (!$checkoutFormInitialize->getPaymentPageUrl()) {
            throw new Exception(__('Failed to create Iyzipay checkout form'));
        }

        return $checkoutFormInitialize;
    }

    public function getPaymentUrl($checkoutFormInitialize)
    {
        return $checkoutFormInitialize->getPaymentPageUrl();
    }

    public function retrieveCheckoutForm($token)
    {
        $request = new RetrieveCheckoutFormRequest();
        $request->setToken($token);
        
        return CheckoutForm::retrieve($request, $this->options);
    }

    protected function validateCheckoutData($data)
    {
        $required = ['price', 'currency', 'callback_url', 'product_name', 'buyer'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new InvalidArgumentException(__("Missing required field: :field", ['field' => $field]));
            }
        }

        $buyerRequired = ['id', 'name', 'surname', 'email'];
        foreach ($buyerRequired as $field) {
            if (!isset($data['buyer'][$field])) {
                throw new InvalidArgumentException(__("Missing required buyer field: :field", ['field' => $field]));
            }
        }

        if ($data['price'] <= 0) {
            throw new InvalidArgumentException(__("Price must be greater than 0"));
        }
    }
}